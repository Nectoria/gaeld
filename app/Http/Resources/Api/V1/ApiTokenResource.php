<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiTokenResource extends JsonResource
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
            'company_id' => $this->company_id ?? null,
            'abilities' => $this->abilities,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'expires_at' => $this->when(
                isset($this->expires_at),
                fn () => $this->expires_at?->toIso8601String()
            ),
            // Only include the plain text token on creation
            'token' => $this->when(isset($this->plainTextToken), fn () => $this->plainTextToken),
        ];
    }
}
