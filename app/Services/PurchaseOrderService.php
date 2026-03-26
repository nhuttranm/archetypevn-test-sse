<?php

namespace App\Services;

use App\Events\PurchaseOrderApproved;
use App\Events\PurchaseOrderRejected;
use App\Events\PurchaseOrderSubmitted;
use App\Models\PoItem;
use App\Models\PoStatusLog;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Workflows\WorkflowService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Purchase Order Service Layer
 * 
 * Handles all business logic for purchase orders.
 * Controllers delegate here; no business logic in controllers.
 */
class PurchaseOrderService
{
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

    /**
     * Create a new Purchase Order with items.
     */
    public function create(array $data, User $user): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $user) {
            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePoNumber(),
                'department_id' => $user->department_id,
                'vendor_id' => $data['vendor_id'],
                'created_by' => $user->id,
                'total_amount' => 0,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
            ]);

            if (!empty($data['items'])) {
                $this->syncItems($po, $data['items']);
            }

            $this->logStatusChange($po, $user, null, PurchaseOrder::STATUS_DRAFT, 'Purchase order created');

            $this->clearDashboardCache();

            return $po->load(['items', 'vendor', 'department', 'creator']);
        });
    }

    /**
     * Update an existing Purchase Order.
     */
    public function update(PurchaseOrder $po, array $data, User $user): PurchaseOrder
    {
        if (!$po->canBeEdited()) {
            throw ValidationException::withMessages([
                'status' => 'Purchase order cannot be edited in its current status.',
            ]);
        }

        return DB::transaction(function () use ($po, $data, $user) {
            $po->update([
                'vendor_id' => $data['vendor_id'] ?? $po->vendor_id,
                'notes' => $data['notes'] ?? $po->notes,
            ]);

            if (isset($data['items'])) {
                $this->syncItems($po, $data['items']);
            }

            $this->clearDashboardCache();

            return $po->fresh(['items', 'vendor', 'department', 'creator']);
        });
    }

    /**
     * Delete a Purchase Order (soft delete).
     */
    public function delete(PurchaseOrder $po, User $user): bool
    {
        if (!$po->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Only draft purchase orders can be deleted.',
            ]);
        }

        return DB::transaction(function () use ($po) {
            $po->items()->delete();
            $po->delete();
            $this->clearDashboardCache();
            return true;
        });
    }

    /**
     * Submit a PO for approval (draft → first pending state).
     */
    public function submit(PurchaseOrder $po, User $user): PurchaseOrder
    {
        if (!$po->canBeSubmitted()) {
            throw ValidationException::withMessages([
                'status' => 'Purchase order cannot be submitted in its current status.',
            ]);
        }

        if ($po->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'Purchase order must have at least one item.',
            ]);
        }

        return DB::transaction(function () use ($po, $user) {
            $nextState = $this->workflowService->getNextState($po, $user, 'submit');

            if (!$nextState) {
                // Default fallback: draft → pending_manager
                $nextState = PurchaseOrder::STATUS_PENDING_MANAGER;
            }

            $oldStatus = $po->status;
            $po->update([
                'status' => $nextState,
                'submitted_at' => now(),
            ]);

            $this->logStatusChange($po, $user, $oldStatus, $nextState, 'Submitted for approval');

            event(new PurchaseOrderSubmitted($po, $user));

            $this->clearDashboardCache();

            return $po->fresh(['items', 'vendor', 'department', 'creator', 'statusLogs.actor']);
        });
    }

    /**
     * Approve a Purchase Order.
     */
    public function approve(PurchaseOrder $po, User $user, ?string $comment = null): PurchaseOrder
    {
        if (!$po->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Purchase order is not in a pending state.',
            ]);
        }

        if (!$this->workflowService->canUserAct($po, $user)) {
            throw ValidationException::withMessages([
                'authorization' => 'You are not authorized to approve this purchase order at its current stage.',
            ]);
        }

        return DB::transaction(function () use ($po, $user, $comment) {
            $oldStatus = $po->status;
            $nextState = $this->workflowService->getNextState($po, $user, 'approve');

            $updateData = ['status' => $nextState];

            if ($nextState === PurchaseOrder::STATUS_APPROVED) {
                $updateData['approved_at'] = now();
            }

            $po->update($updateData);

            $this->logStatusChange($po, $user, $oldStatus, $nextState, $comment ?? 'Approved');

            if ($nextState === PurchaseOrder::STATUS_APPROVED) {
                event(new PurchaseOrderApproved($po, $user));
            }

            $this->clearDashboardCache();

            return $po->fresh(['items', 'vendor', 'department', 'creator', 'statusLogs.actor']);
        });
    }

    /**
     * Reject a Purchase Order.
     */
    public function reject(PurchaseOrder $po, User $user, string $reason): PurchaseOrder
    {
        if (!$po->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Purchase order is not in a pending state.',
            ]);
        }

        if (!$this->workflowService->canUserAct($po, $user)) {
            throw ValidationException::withMessages([
                'authorization' => 'You are not authorized to reject this purchase order at its current stage.',
            ]);
        }

        return DB::transaction(function () use ($po, $user, $reason) {
            $oldStatus = $po->status;

            $po->update([
                'status' => PurchaseOrder::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'rejected_at' => now(),
            ]);

            $this->logStatusChange($po, $user, $oldStatus, PurchaseOrder::STATUS_REJECTED, $reason);

            event(new PurchaseOrderRejected($po, $user, $reason));

            $this->clearDashboardCache();

            return $po->fresh(['items', 'vendor', 'department', 'creator', 'statusLogs.actor']);
        });
    }

    /**
     * Create a new revision of a PO.
     */
    public function createRevision(PurchaseOrder $po, User $user): PurchaseOrder
    {
        if (!$po->isRejected() && !$po->isApproved()) {
            throw ValidationException::withMessages([
                'status' => 'Only rejected or approved POs can be revised.',
            ]);
        }

        return DB::transaction(function () use ($po, $user) {
            // Mark old PO as not latest
            $po->update(['is_latest' => false]);

            // Create new revision
            $newPo = $po->replicate();
            $newPo->po_number = PurchaseOrder::generatePoNumber();
            $newPo->parent_po_id = $po->id;
            $newPo->status = PurchaseOrder::STATUS_DRAFT;
            $newPo->revision_number = $po->revision_number + 1;
            $newPo->is_latest = true;
            $newPo->rejection_reason = null;
            $newPo->submitted_at = null;
            $newPo->approved_at = null;
            $newPo->rejected_at = null;
            $newPo->save();

            // Copy items
            foreach ($po->items as $item) {
                $newItem = $item->replicate();
                $newItem->purchase_order_id = $newPo->id;
                $newItem->save();
            }

            $this->logStatusChange($newPo, $user, null, PurchaseOrder::STATUS_DRAFT, 
                "Revision created from PO #{$po->po_number}");

            $this->clearDashboardCache();

            return $newPo->load(['items', 'vendor', 'department', 'creator']);
        });
    }

    /**
     * Sync line items for a PO and recalculate total.
     */
    protected function syncItems(PurchaseOrder $po, array $items): void
    {
        $po->items()->delete();

        $totalAmount = 0;
        foreach ($items as $index => $itemData) {
            $totalPrice = $itemData['quantity'] * $itemData['unit_price'];
            $totalAmount += $totalPrice;

            PoItem::create([
                'purchase_order_id' => $po->id,
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'total_price' => $totalPrice,
                'unit' => $itemData['unit'] ?? 'pcs',
                'sort_order' => $index,
            ]);
        }

        $po->update(['total_amount' => $totalAmount]);
    }

    /**
     * Log a status change for audit trail.
     */
    protected function logStatusChange(
        PurchaseOrder $po,
        User $user,
        ?string $fromStatus,
        string $toStatus,
        ?string $comment = null
    ): PoStatusLog {
        return PoStatusLog::create([
            'purchase_order_id' => $po->id,
            'acted_by' => $user->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
            'metadata' => [
                'user_role' => $user->role,
                'user_department' => $user->department_id,
                'po_amount' => $po->total_amount,
                'ip_address' => request()->ip(),
            ],
        ]);
    }

    /**
     * Clear dashboard cache.
     */
    protected function clearDashboardCache(): void
    {
        // Without tags, we can either clear specific keys or flush
        // We use flush here for simplicity to ensure all user dashboards update
        try {
            Cache::flush();
        } catch (\Exception $e) {
            // Ignore
        }
    }

    /**
     * Get dashboard statistics (cached).
     */
    public function getDashboardStats(?User $user = null): array
    {
        $cacheKey = 'dashboard_stats_' . ($user?->id ?? 'global');

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $query = PurchaseOrder::withoutGlobalScopes()->latest();

            if ($user && !in_array($user->role, ['director', 'finance'])) {
                $query->where('department_id', $user->department_id);
            }

            return [
                'total' => (clone $query)->count(),
                'draft' => (clone $query)->byStatus('draft')->count(),
                'pending' => (clone $query)->whereIn('status', [
                    'pending_manager', 'pending_director', 'pending_finance'
                ])->count(),
                'approved' => (clone $query)->byStatus('approved')->count(),
                'rejected' => (clone $query)->byStatus('rejected')->count(),
                'total_amount' => (clone $query)->byStatus('approved')->sum('total_amount'),
            ];
        });
    }
}
