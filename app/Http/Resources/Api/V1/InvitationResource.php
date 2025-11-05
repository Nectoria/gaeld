<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
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
            'email' => $this->email,
            'role' => $this->role,
            'token' => $this->when(
                $request->routeIs('api.v1.invitations.store'),
                fn () => $this->token
            ),
            'invitation_url' => $this->when(
                $request->routeIs('api.v1.invitations.store'),
                fn () => url("/invitations/{$this->token}/accept")
            ),
            'company' => $this->when(
                $this->relationLoaded('company'),
                fn () => [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                ]
            ),
            'is_expired' => $this->isExpired(),
            'is_accepted' => $this->isAccepted(),
            'expires_at' => $this->expires_at->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
