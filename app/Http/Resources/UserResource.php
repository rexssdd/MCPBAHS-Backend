<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->roleLabel(),
            'staffId' => substr($this->uuid, 0, 8),
            'status' => $this->account_status?->value === 'active' ? 'Active' : 'Inactive',
            'account_status' => $this->account_status,
            'invitation_sent_at' => $this->invitation_sent_at,
            'invitation_accepted_at' => $this->invitation_accepted_at,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function roleLabel(): string
    {
        if (! empty($this->role)) {
            return ucfirst((string) $this->role);
        }

        $text = strtolower(($this->name ?? '') . ' ' . ($this->email ?? ''));

        foreach (['principal', 'registrar', 'teacher', 'guidance', 'admin'] as $role) {
            if (str_contains($text, $role)) {
                return ucfirst($role);
            }
        }

        return 'Admin';
    }
}
