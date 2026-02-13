<?php

namespace App\Http\Requests\Owner;

use App\Repositories\CourtScheduleRepository;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourtScheduleRequest extends StoreCourtScheduleRequest
{
    public function __construct(
        protected CourtScheduleRepository $scheduleRepository
    ) {
        parent::__construct();
    }

    public function withValidator($validator)
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            $scheduleId = (int) $this->route('id');
            $schedule = $this->scheduleRepository->find($scheduleId);
            if (! $schedule) {
                return;
            }

            $courtId = (int) $schedule->court_id;
            $dayOfWeek = (int) ($this->input('day_of_week') ?? $schedule->day_of_week);
            $startTime = $this->input('start_time') ?? $schedule->start_time;
            $endTime = $this->input('end_time') ?? $schedule->end_time;
            $effectiveFrom = $this->input('effective_from');
            $effectiveTo = $this->input('effective_to');

            if (! $effectiveFrom) {
                return;
            }

            $effectiveFromStr = \Carbon\Carbon::parse($effectiveFrom)->format('Y-m-d');
            $effectiveToStr = $effectiveTo ? \Carbon\Carbon::parse($effectiveTo)->format('Y-m-d') : null;

            $hasOverlap = $this->scheduleRepository->hasOverlap(
                $courtId,
                $dayOfWeek,
                $startTime,
                $endTime,
                $effectiveFromStr,
                $effectiveToStr,
                $scheduleId
            );

            if ($hasOverlap) {
                $validator->errors()->add(
                    'overlap',
                    'Khung giờ trùng với lịch đã có (cùng thứ và trùng khoảng ngày).'
                );
            }
        });
    }
}
