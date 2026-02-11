<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:badminton,football,tennis,pickleball,basketball,swimming,gym,complex',
            'description' => 'nullable|string',
            'address' => 'sometimes|string|max:500',
            'lat' => 'sometimes|numeric|between:-90,90',
            'lng' => 'sometimes|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'operating_hours' => 'nullable|string|max:100',
            'image' => 'nullable|string|max:500',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'is_active' => 'sometimes|boolean',
            'amenities' => 'nullable', // Can be JSON string from FormData
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Parse amenities from JSON string if needed (when sent as FormData)
        if ($this->has('amenities') && is_string($this->amenities)) {
            $this->merge([
                'amenities' => json_decode($this->amenities, true) ?? [],
            ]);
        }
    }
}
