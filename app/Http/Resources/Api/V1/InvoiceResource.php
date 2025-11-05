<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'contact_id' => $this->contact_id,
            'contact' => $this->when(
                $this->relationLoaded('contact'),
                fn () => new ContactResource($this->contact)
            ),
            'invoice_number' => $this->invoice_number,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'subtotal_amount' => $this->subtotal_amount,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'currency' => $this->currency,
            'tax_inclusive' => $this->tax_inclusive,
            'qr_reference' => $this->qr_reference,
            'qr_iban' => $this->qr_iban,
            'qr_additional_info' => $this->qr_additional_info,
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'footer' => $this->footer,
            'pdf_url' => $this->when($this->pdf_path, fn () => url($this->pdf_path)),
            'qr_code_url' => $this->when($this->qr_code_path, fn () => url($this->qr_code_path)),
            'items' => $this->when(
                $this->relationLoaded('items'),
                fn () => InvoiceItemResource::collection($this->items)
            ),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
