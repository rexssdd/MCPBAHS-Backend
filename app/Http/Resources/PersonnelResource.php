<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonnelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'personnel_id_number' => $this->personnel_id_number,

            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,

            'full_name' => $this->full_name,

            'email' => $this->email,
            'phone_number' => $this->phone_number,

            'date_of_birth' => $this->date_of_birth,
            'sex' => $this->sex,

            'position' => $this->position,
            'employment_status' => $this->employment_status,

            'teaching_load' => $this->teaching_load,
            'department' => $this->department,

            'address' => [
                'country' => $this->country,
                'region' => $this->region,
                'province' => $this->province,
                'city' => $this->city,
                'postal_code' => $this->postal_code,
                'street' => $this->brgy_street_address,
            ],

            'user' => $this->whenLoaded('user'),
        ];
    }
}
