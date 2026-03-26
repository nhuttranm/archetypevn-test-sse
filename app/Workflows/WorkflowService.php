<?php

namespace App\Workflows;

use App\Models\ApprovalRule;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Dynamic Workflow Engine
 * 
 * Reads approval rules from the database and determines state transitions.
 * No hardcoded logic - all rules are configurable via the approval_rules table.
 */
class WorkflowService
{
    /**
     * Get the next available transitions for a purchase order given the current user.
     */
    public function getAvailableTransitions(PurchaseOrder $po, User $user): Collection
    {
        return ApprovalRule::active()
            ->forState($po->status)
            ->where('required_role', $user->role)
            ->orderBy('priority', 'asc')
            ->get()
            ->filter(function (ApprovalRule $rule) use ($po, $user) {
                return $this->evaluateConditions($rule, $po, $user);
            });
    }

    /**
     * Determine the next state for a PO based on approval rules and the acting user.
     */
    public function getNextState(PurchaseOrder $po, User $user, string $action = 'approve'): ?string
    {
        if ($action === 'reject') {
            return PurchaseOrder::STATUS_REJECTED;
        }

        $rule = $this->getAvailableTransitions($po, $user)->first();

        return $rule?->next_state;
    }

    /**
     * Check if a user can act on a purchase order.
     */
    public function canUserAct(PurchaseOrder $po, User $user): bool
    {
        return $this->getAvailableTransitions($po, $user)->isNotEmpty();
    }

    /**
     * Get the full approval chain for a given starting state.
     */
    public function getApprovalChain(string $startingState = 'draft'): Collection
    {
        $chain = collect();
        $currentState = $startingState;
        $visited = [];

        while ($currentState && !in_array($currentState, $visited)) {
            $visited[] = $currentState;

            $rules = ApprovalRule::active()
                ->forState($currentState)
                ->orderBy('priority', 'asc')
                ->get();

            if ($rules->isEmpty()) {
                break;
            }

            foreach ($rules as $rule) {
                $chain->push($rule);
            }

            // Follow the primary (first) rule to next state
            $currentState = $rules->first()->next_state;
        }

        return $chain;
    }

    /**
     * Evaluate condition expressions for a rule.
     * 
     * Condition expression is a JSON object like:
     * {
     *   "min_amount": 10000,
     *   "max_amount": 50000,
     *   "department_match": true
     * }
     */
    protected function evaluateConditions(ApprovalRule $rule, PurchaseOrder $po, User $user): bool
    {
        $conditions = $rule->condition_expression;

        if (empty($conditions)) {
            return true;
        }

        // Check minimum amount condition
        if (isset($conditions['min_amount']) && $po->total_amount < $conditions['min_amount']) {
            return false;
        }

        // Check maximum amount condition
        if (isset($conditions['max_amount']) && $po->total_amount > $conditions['max_amount']) {
            return false;
        }

        // Check department match condition
        if (isset($conditions['department_match']) && $conditions['department_match'] === true) {
            if ($user->department_id !== $po->department_id) {
                return false;
            }
        }

        // Check specific department condition
        if (isset($conditions['department_id']) && $po->department_id !== $conditions['department_id']) {
            return false;
        }

        return true;
    }
}
