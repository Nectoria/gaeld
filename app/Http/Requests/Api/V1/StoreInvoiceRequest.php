<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to create invoices
        return $this->user()
            ->tokenCan('invoices:create') || $this->user()->tokenCan('*');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact_id' => 'required|exists:contacts,id',
            'status' => 'sometimes|in:draft,sent,viewed,partial,paid,overdue,cancelled',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'tax_inclusive' => 'sometimes|boolean',
            'currency' => 'sometimes|string|max:3',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'footer' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|integer|min:0',
            'items.*.tax_rate' => 'required|numeric|min:0|max:100',
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
            'contact_id.required' => 'A customer contact is required',
            'contact_id.exists' => 'Selected customer does not exist',
            'invoice_date.required' => 'Invoice date is required',
            'due_date.required' => 'Due date is required',
            'due_date.after_or_equal' => 'Due date must be on or after invoice date',
            'items.required' => 'At least one invoice item is required',
            'items.min' => 'At least one invoice item is required',
            'items.*.name.required' => 'Item name is required',
            'items.*.quantity.required' => 'Item quantity is required',
            'items.*.unit_price.required' => 'Item unit price is required',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Ensure contact belongs to the current company
        if ($this->has('contact_id')) {
            $company = $this->route('company');
            $contact = $company->contacts()->find($this->contact_id);

            if (! $contact) {
                $this->merge(['contact_id' => null]);
            }
        }
    }
}
