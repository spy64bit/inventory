<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiConfirmRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in([
                'create_purchase_order',
                'create_sales_order',
                'add_product',
                'edit_product',
                'check_stock',
            ])],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.product_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'product' => ['nullable', 'array'],
            'product.id' => ['nullable', 'integer', 'exists:products,id'],
            'product.name' => ['nullable', 'string', 'max:255'],
            'product.sku' => ['nullable', 'string', 'max:50'],
            'product.cost_price' => ['nullable', 'numeric', 'min:0'],
            'product.selling_price' => ['nullable', 'numeric', 'min:0'],
            'product.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'product.unit' => ['nullable', 'string', 'max:50'],
            'stock_check_ids' => ['nullable', 'array'],
            'stock_check_ids.*' => ['integer', 'exists:products,id'],
        ];
    }
}
