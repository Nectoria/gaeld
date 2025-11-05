<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        // Cannot update paid or cancelled invoices
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return false;
        }

        // Check if user has permission to update invoices
        return $this->user()
            ->tokenCan('invoices:update') || $this->user()->tokenCan('*');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact_id' => 'sometimes|exists:contacts,id',
            'invoice_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after_or_equal:invoice_date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.name' => 'required_with:items|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required_with:items|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required_with:items|integer|min:0',
            'items.*.tax_rate' => 'required_with:items|numeric|min:0|max:100',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contact_id.exists' => 'Selected customer does not exist',
            'due_date.after_or_equal' => 'Due date must be on or after invoice date',
            'items.min' => 'At least one invoice item is required',
            'items.*.name.required_with' => 'Item name is required',
            'items.*.quantity.required_with' => 'Item quantity is required',
            'items.*.unit_price.required_with' => 'Item unit price is required',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'items.*.name' => 'item name',
            'items.*.quantity' => 'item quantity',
            'items.*.unit_price' => 'item unit price',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        $invoice = $this->route('invoice');

        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Cannot update invoices with status: '.$invoice->status
            );
        }

        parent::failedAuthorization();
    }
}
