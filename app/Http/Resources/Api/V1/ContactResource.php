<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'display_name' => $this->display_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'website' => $this->website,
            'street' => $this->street,
            'street_number' => $this->street_number,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'vat_number' => $this->vat_number,
            'tax_id' => $this->tax_id,
            'iban' => $this->iban,
            'bank_name' => $this->bank_name,
            'payment_term_days' => $this->payment_term_days,
            'currency' => $this->currency,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
