<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:badminton,football,tennis,pickleball,basketball,swimming,gym,complex',
            'description' => 'nullable|string',
            'address' => 'required|string|max:500',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'operating_hours' => 'nullable|string|max:100',
            'image' => 'nullable|string|max:500',
            'amenities' => 'nullable|array',
            'amenities.*.name' => 'required_with:amenities|string|max:100',
            'amenities.*.icon' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên địa điểm là bắt buộc',
            'type.required' => 'Loại địa điểm là bắt buộc',
            'type.in' => 'Loại địa điểm không hợp lệ',
            'address.required' => 'Địa chỉ là bắt buộc',
            'lat.required' => 'Vị trí trên bản đồ là bắt buộc',
            'lng.required' => 'Vị trí trên bản đồ là bắt buộc',
        ];
    }
}
