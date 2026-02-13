<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|in:badminton,football,tennis,pickleball,basketball,swimming,gym',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'extra_ids' => 'nullable|array',
            'extra_ids.*' => 'integer|exists:owner_extras,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên sân là bắt buộc',
        ];
    }
}
