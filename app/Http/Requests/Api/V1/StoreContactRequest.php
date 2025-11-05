<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to create contacts
        return $this->user()
            ->tokenCan('contacts:create') || $this->user()->tokenCan('*');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|in:customer,vendor,both',
            'is_active' => 'sometimes|boolean',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'street' => 'nullable|string|max:255',
            'street_number' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:2',
            'vat_number' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:34',
            'bank_name' => 'nullable|string|max:255',
            'payment_term_days' => 'nullable|integer|min:0',
            'currency' => 'nullable|string|max:3',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
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
            'type.required' => 'Contact type is required',
            'type.in' => 'Contact type must be customer, vendor, or both',
            'name.required' => 'Contact name is required',
            'email.email' => 'Please provide a valid email address',
            'website.url' => 'Please provide a valid URL',
            'iban.max' => 'IBAN must not exceed 34 characters',
        ];
    }
}
