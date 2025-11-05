<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'vat_number' => $this->vat_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'iban' => $this->iban,
            'qr_iban' => $this->qr_iban,
            'swift_bic' => $this->swift_bic,
            'tax_id' => $this->tax_id,
            'business_id' => $this->business_id,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'date_format' => $this->date_format,
            'time_format' => $this->time_format,
            'brand_color' => $this->brand_color,
            'logo_url' => $this->when($this->logo, fn () => $this->getFirstMediaUrl('logo')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
