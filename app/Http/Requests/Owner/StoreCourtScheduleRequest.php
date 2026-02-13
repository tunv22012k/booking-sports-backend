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
            'end_time' => 'required|date_format:H:i',
            'price' => 'required|numeric|min:0|max:1000000000',
            'effective_from' => 'required|date|date_format:Y-m-d',
            'effective_to' => 'nullable|date|date_format:Y-m-d',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate end_time after start_time
            if ($this->has('start_time') && $this->has('end_time')) {
                $startTime = \Carbon\Carbon::createFromFormat('H:i', $this->input('start_time'));
                $endTime = \Carbon\Carbon::createFromFormat('H:i', $this->input('end_time'));
                if ($endTime->lte($startTime)) {
                    $validator->errors()->add('end_time', 'Giờ kết thúc phải sau giờ bắt đầu');
                }
            }

            // Validate effective_to after effective_from
            if ($this->has('effective_from') && $this->has('effective_to') && $this->input('effective_to')) {
                $effectiveFrom = \Carbon\Carbon::parse($this->input('effective_from'));
                $effectiveTo = \Carbon\Carbon::parse($this->input('effective_to'));
                if ($effectiveTo->lt($effectiveFrom)) {
                    $validator->errors()->add('effective_to', 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'day_of_week.required' => 'Ngày trong tuần là bắt buộc',
            'day_of_week.integer' => 'Ngày trong tuần phải là số nguyên',
            'day_of_week.between' => 'Ngày trong tuần phải từ 0 (Chủ nhật) đến 6 (Thứ 7)',
            'start_time.required' => 'Giờ bắt đầu là bắt buộc',
            'start_time.date_format' => 'Giờ bắt đầu phải đúng định dạng HH:mm',
            'end_time.required' => 'Giờ kết thúc là bắt buộc',
            'end_time.date_format' => 'Giờ kết thúc phải đúng định dạng HH:mm',
            'price.required' => 'Giá tiền là bắt buộc',
            'price.numeric' => 'Giá tiền phải là số',
            'price.min' => 'Giá tiền không được nhỏ hơn 0',
            'price.max' => 'Giá tiền không được vượt quá 1 tỷ VNĐ',
            'effective_from.required' => 'Ngày áp dụng từ là bắt buộc',
            'effective_from.date' => 'Ngày áp dụng từ không hợp lệ',
            'effective_from.date_format' => 'Ngày áp dụng từ phải đúng định dạng Y-m-d',
            'effective_to.date' => 'Ngày áp dụng đến không hợp lệ',
            'effective_to.date_format' => 'Ngày áp dụng đến phải đúng định dạng Y-m-d',
        ];
    }
}
