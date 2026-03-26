<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApprovalRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'current_state' => 'required|string|in:draft,pending_manager,pending_director,pending_finance',
            'next_state' => 'required|string|in:pending_manager,pending_director,pending_finance,approved',
            'required_role' => 'required|string|in:staff,manager,director,finance',
            'condition_expression' => 'nullable|array',
            'condition_expression.min_amount' => 'nullable|numeric|min:0',
            'condition_expression.max_amount' => 'nullable|numeric|min:0',
            'condition_expression.department_match' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }
}
