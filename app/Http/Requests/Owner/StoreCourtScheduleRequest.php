<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourtScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'price' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'day_of_week.required' => 'Ngày trong tuần là bắt buộc',
            'day_of_week.between' => 'Ngày trong tuần phải từ 0 (CN) đến 6 (T7)',
            'start_time.required' => 'Giờ bắt đầu là bắt buộc',
            'end_time.required' => 'Giờ kết thúc là bắt buộc',
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu',
            'price.required' => 'Giá tiền là bắt buộc',
            'effective_from.required' => 'Ngày áp dụng là bắt buộc',
            'effective_to.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu',
        ];
    }
}
