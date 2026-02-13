<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOwnerBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'string', 'date_format:H:i'],
            'end_time' => ['required', 'string', 'date_format:H:i', 'after:start_time'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'guest_name' => ['nullable', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $userId = $this->input('user_id');
            $guestName = $this->input('guest_name');
            $guestPhone = $this->input('guest_phone');

            if (empty($userId) && empty(trim((string) $guestName ?? ''))) {
                $validator->errors()->add('guest_name', 'Vui lòng nhập tên khách hoặc chọn khách hàng có tài khoản.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'court_id.required' => 'Vui lòng chọn sân.',
            'court_id.exists' => 'Sân không tồn tại.',
            'date.required' => 'Vui lòng chọn ngày.',
            'date.after_or_equal' => 'Ngày đặt phải từ hôm nay trở đi.',
            'start_time.required' => 'Vui lòng chọn giờ bắt đầu.',
            'end_time.required' => 'Vui lòng chọn giờ kết thúc.',
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
            'total_price.required' => 'Vui lòng nhập giá tiền.',
        ];
    }
}
