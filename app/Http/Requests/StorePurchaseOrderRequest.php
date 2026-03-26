<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => 'required|exists:vendors,id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one line item is required.',
            'items.min' => 'At least one line item is required.',
            'items.*.description.required' => 'Each item must have a description.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.unit_price.min' => 'Unit price must be greater than 0.',
        ];
    }
}
