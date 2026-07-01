<?php

namespace App\Http\Requests\TvlOffers;

use Illuminate\Foundation\Http\FormRequest;

class StoreTvlOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:10'],
            'certifications' => ['nullable', 'array'],
            'certifications.*' => ['string'],
            'display_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
