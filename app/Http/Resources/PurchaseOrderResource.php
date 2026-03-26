<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'po_number' => $this->po_number,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'total_amount' => (float) $this->total_amount,
            'total_amount_formatted' => '$' . number_format($this->total_amount, 2),
            'revision_number' => $this->revision_number,
            'is_latest' => $this->is_latest,
            'notes' => $this->notes,
            'rejection_reason' => $this->rejection_reason,

            // Relationships
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'items' => PoItemResource::collection($this->whenLoaded('items')),
            'status_logs' => PoStatusLogResource::collection($this->whenLoaded('statusLogs')),
            'parent_po' => new PurchaseOrderResource($this->whenLoaded('parentPo')),

            // Timestamps
            'submitted_at' => $this->submitted_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Computed
            'can_edit' => $this->canBeEdited(),
            'can_submit' => $this->canBeSubmitted(),
            'is_pending' => $this->isPending(),
            'is_approved' => $this->isApproved(),
            'is_rejected' => $this->isRejected(),
        ];
    }

    protected function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'pending_manager' => 'Pending Manager',
            'pending_director' => 'Pending Director',
            'pending_finance' => 'Pending Finance',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }
}
