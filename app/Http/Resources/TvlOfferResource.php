<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TvlOfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Used by the admin TVL offers manager (AdminTvlOffersPage.jsx), so
     * unlike PublicController::tvlOffers() this includes is_active and
     * display_order for editing.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'image_url' => $this->image_url,
            'certifications' => $this->certifications ?? [],
            'duration' => $this->duration,
            'details' => $this->details ?? [],
            'display_order' => $this->display_order,
            'is_active' => $this->is_active,
        ];
    }
}