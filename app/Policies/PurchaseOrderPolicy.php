<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    /**
     * Determine if the user can view any purchase orders.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view (filtered by DepartmentScope)
    }

    /**
     * Determine if the user can view the purchase order.
     */
    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // Director and Finance can view all
        if (in_array($user->role, ['director', 'finance'])) {
            return true;
        }

        // Others can only view their department's POs
        return $user->department_id === $purchaseOrder->department_id;
    }

    /**
     * Determine if the user can create purchase orders.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create
    }

    /**
     * Determine if the user can update the purchase order.
     */
    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // Only the creator can update, and only in draft/rejected status
        return $user->id === $purchaseOrder->created_by
            && $purchaseOrder->canBeEdited();
    }

    /**
     * Determine if the user can delete the purchase order.
     */
    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // Only the creator can delete, and only in draft status
        return $user->id === $purchaseOrder->created_by
            && $purchaseOrder->isDraft();
    }

    /**
     * Determine if the user can submit the purchase order.
     */
    public function submit(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->id === $purchaseOrder->created_by
            && $purchaseOrder->canBeSubmitted();
    }

    /**
     * Determine if the user can approve the purchase order.
     */
    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if (!$purchaseOrder->isPending()) {
            return false;
        }

        // Check workflow rules
        $workflowService = app(\App\Workflows\WorkflowService::class);
        return $workflowService->canUserAct($purchaseOrder, $user);
    }

    /**
     * Determine if the user can reject the purchase order.
     */
    public function reject(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->approve($user, $purchaseOrder);
    }

    /**
     * Determine if the user can create a revision.
     */
    public function revise(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->id === $purchaseOrder->created_by
            && ($purchaseOrder->isRejected() || $purchaseOrder->isApproved());
    }
}
