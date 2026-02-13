<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourtSchedulesBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedules' => 'required|array|min:1',
            'schedules.*.day_of_week' => 'required|integer|between:0,6',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i',
            'schedules.*.price' => 'required|numeric|min:0|max:1000000000',
            'schedules.*.effective_from' => 'required|date|date_format:Y-m-d',
            'schedules.*.effective_to' => 'nullable|date|date_format:Y-m-d',
            'schedules.*.is_active' => 'sometimes|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($this->input('schedules', []) as $index => $schedule) {
                // Validate end_time after start_time
                if (isset($schedule['start_time']) && isset($schedule['end_time'])) {
                    $startTime = \Carbon\Carbon::createFromFormat('H:i', $schedule['start_time']);
                    $endTime = \Carbon\Carbon::createFromFormat('H:i', $schedule['end_time']);
                    if ($endTime->lte($startTime)) {
                        $validator->errors()->add("schedules.{$index}.end_time", 'Giờ kết thúc phải sau giờ bắt đầu');
                    }
                }

                // Validate effective_to after effective_from
                if (isset($schedule['effective_from']) && isset($schedule['effective_to']) && $schedule['effective_to']) {
                    $effectiveFrom = \Carbon\Carbon::parse($schedule['effective_from']);
                    $effectiveTo = \Carbon\Carbon::parse($schedule['effective_to']);
                    if ($effectiveTo->lt($effectiveFrom)) {
                        $validator->errors()->add("schedules.{$index}.effective_to", 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'schedules.required' => 'Danh sách khung giờ là bắt buộc',
            'schedules.array' => 'Danh sách khung giờ phải là mảng',
            'schedules.min' => 'Phải có ít nhất một khung giờ',
            'schedules.*.day_of_week.required' => 'Ngày trong tuần là bắt buộc',
            'schedules.*.day_of_week.integer' => 'Ngày trong tuần phải là số nguyên',
            'schedules.*.day_of_week.between' => 'Ngày trong tuần phải từ 0 (Chủ nhật) đến 6 (Thứ 7)',
            'schedules.*.start_time.required' => 'Giờ bắt đầu là bắt buộc',
            'schedules.*.start_time.date_format' => 'Giờ bắt đầu phải đúng định dạng HH:mm',
            'schedules.*.end_time.required' => 'Giờ kết thúc là bắt buộc',
            'schedules.*.end_time.date_format' => 'Giờ kết thúc phải đúng định dạng HH:mm',
            'schedules.*.price.required' => 'Giá tiền là bắt buộc',
            'schedules.*.price.numeric' => 'Giá tiền phải là số',
            'schedules.*.price.min' => 'Giá tiền không được nhỏ hơn 0',
            'schedules.*.price.max' => 'Giá tiền không được vượt quá 1 tỷ VNĐ',
            'schedules.*.effective_from.required' => 'Ngày áp dụng từ là bắt buộc',
            'schedules.*.effective_from.date' => 'Ngày áp dụng từ không hợp lệ',
            'schedules.*.effective_from.date_format' => 'Ngày áp dụng từ phải đúng định dạng Y-m-d',
            'schedules.*.effective_to.date' => 'Ngày áp dụng đến không hợp lệ',
            'schedules.*.effective_to.date_format' => 'Ngày áp dụng đến phải đúng định dạng Y-m-d',
        ];
    }
}
