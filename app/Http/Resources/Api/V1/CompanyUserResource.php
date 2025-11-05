<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyUserResource extends JsonResource
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
            'email' => $this->email,
            'role' => $this->pivot->role,
            'is_active' => $this->pivot->is_active,
            'joined_at' => $this->pivot->joined_at?->toIso8601String(),
            'last_active_at' => $this->when(
                isset($this->last_active_at),
                fn () => $this->last_active_at?->toIso8601String()
            ),
        ];
    }
}
