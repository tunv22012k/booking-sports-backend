<?php

namespace App\Services;

use App\Repositories\OwnerVenueRepository;
use App\Repositories\CourtScheduleRepository;
use App\Repositories\OwnerExtraRepository;
use App\Repositories\BookingRepository;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\VenueAmenity;
use App\Models\Review;
use App\Models\Booking;
use App\Jobs\UploadVenueImageJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class OwnerVenueService
{
    protected $venueRepository;
    protected $scheduleRepository;
    protected $extraRepository;
    protected $bookingRepository;

    public function __construct(
        OwnerVenueRepository $venueRepository,
        CourtScheduleRepository $scheduleRepository,
        OwnerExtraRepository $extraRepository,
        BookingRepository $bookingRepository
    ) {
        $this->venueRepository = $venueRepository;
        $this->scheduleRepository = $scheduleRepository;
        $this->extraRepository = $extraRepository;
        $this->bookingRepository = $bookingRepository;
    }

    // ── Venue ──

    public function getOwnerVenues($user, int $perPage = 10)
    {
        return $this->venueRepository->getByOwnerPaginated($user->id, $perPage);
    }

    public function getVenueDetail($id, $user)
    {
        $venue = $this->venueRepository->findByOwner($id, $user->id);

        if (!$venue) {
            throw new Exception('Không tìm thấy địa điểm', 404);
        }

        return $venue;
    }

    public function createVenue($user, array $data, ?UploadedFile $imageFile = null)
    {
        $data['owner_id'] = $user->id;
        
        // Extract amenities before creating venue (it's a relationship, not a column)
        $amenities = $data['amenities'] ?? [];
        unset($data['amenities']);
        unset($data['image_file']); // Remove file from data
        
        // If there's an image file, save it temporarily first
        $tempImagePath = null;
        if ($imageFile) {
            // Generate unique filename
            $filename = now()->format('Ymd_His') . '_' . Str::random(8) . '.' . $imageFile->getClientOriginalExtension();
            $tempImagePath = 'venues/temp/' . $filename;
            
            // Store temporarily - set as placeholder URL until queue processes
            $imageFile->storeAs('venues/temp', $filename, 'public');
            $data['image'] = Storage::disk('public')->url($tempImagePath);
        }
        
        $venue = $this->venueRepository->create($data);

        // Create amenities if provided
        if (!empty($amenities)) {
            foreach ($amenities as $amenity) {
                $venue->amenities()->create([
                    'name' => $amenity['name'],
                    'icon' => $amenity['icon'] ?? null,
                ]);
            }
        }

        // Dispatch job to upload image to Google Drive
        if ($tempImagePath) {
            UploadVenueImageJob::dispatch($venue->id, $tempImagePath);
        }

        return $venue->load('amenities');
    }

    public function updateVenue($id, $user, array $data, ?UploadedFile $imageFile = null)
    {
        $venue = $this->venueRepository->findByOwner($id, $user->id);

        if (!$venue) {
            throw new Exception('Không tìm thấy địa điểm', 404);
        }

        // Extract amenities before updating venue (it's a relationship, not a column)
        $amenities = null;
        if (array_key_exists('amenities', $data)) {
            $amenities = $data['amenities'];
            unset($data['amenities']);
        }
        unset($data['image_file']); // Remove file from data

        // If there's an image file, save it temporarily first
        $tempImagePath = null;
        if ($imageFile) {
            // Generate unique filename
            $filename = now()->format('Ymd_His') . '_' . Str::random(8) . '.' . $imageFile->getClientOriginalExtension();
            $tempImagePath = 'venues/temp/' . $filename;
            
            // Store temporarily - set as placeholder URL until queue processes
            $imageFile->storeAs('venues/temp', $filename, 'public');
            $data['image'] = Storage::disk('public')->url($tempImagePath);
        }

        $venue->update($data);

        // Sync amenities if provided
        if ($amenities !== null) {
            $venue->amenities()->delete();
            foreach ($amenities as $amenity) {
                $venue->amenities()->create([
                    'name' => $amenity['name'],
                    'icon' => $amenity['icon'] ?? null,
                ]);
            }
        }

        // Dispatch job to upload image to Google Drive
        if ($tempImagePath) {
            UploadVenueImageJob::dispatch($venue->id, $tempImagePath);
        }

        return $venue->refresh()->load('amenities');
    }

    public function deleteVenue($id, $user)
    {
        $venue = $this->venueRepository->findByOwner($id, $user->id);

        if (!$venue) {
            throw new Exception('Không tìm thấy địa điểm', 404);
        }

        $venue->delete();
        return true;
    }

    // ── Court ──

    public function getVenueCourts($venueId, $user)
    {
        $venue = $this->venueRepository->findByOwner($venueId, $user->id);

        if (!$venue) {
            throw new Exception('Không tìm thấy địa điểm', 404);
        }

        return $venue->courts()->with(['schedules', 'extras'])->get();
    }

    public function createCourt($venueId, $user, array $data)
    {
        $venue = $this->venueRepository->findByOwner($venueId, $user->id);

        if (!$venue) {
            throw new Exception('Không tìm thấy địa điểm', 404);
        }

        $extraIds = $data['extra_ids'] ?? [];
        unset($data['extra_ids']);

        $court = $venue->courts()->create($data);

        // Attach selected extras from the owner catalog
        if (!empty($extraIds)) {
            $court->extras()->sync($extraIds);
        }

        return $court->load('extras');
    }

    public function getCourtDetail($courtId, $user)
    {
        $court = Court::with(['venue', 'schedules', 'extras'])
            ->whereHas('venue', fn($q) => $q->where('owner_id', $user->id))
            ->find($courtId);

        if (!$court) {
            throw new Exception('Không tìm thấy sân', 404);
        }

        return $court;
    }

    public function updateCourt($courtId, $user, array $data)
    {
        $court = Court::whereHas('venue', fn($q) => $q->where('owner_id', $user->id))
            ->find($courtId);

        if (!$court) {
            throw new Exception('Không tìm thấy sân', 404);
        }

        $extraIds = $data['extra_ids'] ?? null;
        unset($data['extra_ids']);

        $court->update($data);

        // Sync extras if provided
        if ($extraIds !== null) {
            $court->extras()->sync($extraIds);
        }

        return $court->refresh()->load(['schedules', 'extras']);
    }

    public function deleteCourt($courtId, $user)
    {
        $court = Court::whereHas('venue', fn($q) => $q->where('owner_id', $user->id))
            ->find($courtId);

        if (!$court) {
            throw new Exception('Không tìm thấy sân', 404);
        }

        $court->delete();
        return true;
    }

    // ── Court Schedules ──

    public function getCourtSchedules($courtId, $user)
    {
        $this->ensureCourtOwnership($courtId, $user);
        return $this->scheduleRepository->getByCourt($courtId);
    }

    public function createSchedule($courtId, $user, array $data)
    {
        $this->ensureCourtOwnership($courtId, $user);

        // Check overlap
        $hasOverlap = $this->scheduleRepository->hasOverlap(
            $courtId,
            $data['day_of_week'],
            $data['start_time'],
            $data['end_time'],
            $data['effective_from'],
            $data['effective_to'] ?? null
        );

        if ($hasOverlap) {
            throw new Exception('Khung giờ bị trùng với lịch đã có', 409);
        }

        $data['court_id'] = $courtId;
        return $this->scheduleRepository->create($data);
    }

    public function createSchedulesBatch($courtId, $user, array $schedulesData)
    {
        $this->ensureCourtOwnership($courtId, $user);

        $createdSchedules = [];
        $errors = [];

        foreach ($schedulesData as $index => $data) {
            try {
                // Validate required fields
                if (!isset($data['day_of_week']) || !isset($data['start_time']) || !isset($data['end_time']) || !isset($data['effective_from'])) {
                    $errors[] = "Khung giờ thứ " . ($data['day_of_week'] ?? 'N/A') . " thiếu thông tin bắt buộc";
                    continue;
                }

                \Log::info('Data: ' . json_encode($data));

                // Check overlap
                $hasOverlap = $this->scheduleRepository->hasOverlap(
                    $courtId,
                    (int) $data['day_of_week'],
                    $data['start_time'],
                    $data['end_time'],
                    $data['effective_from'],
                    $data['effective_to'] ?? null
                );

                if ($hasOverlap) {
                    $errors[] = "Khung giờ thứ {$data['day_of_week']} ({$data['start_time']} - {$data['end_time']}) bị trùng với lịch đã có";
                    continue;
                }

                $data['court_id'] = $courtId;
                $created = $this->scheduleRepository->create($data);
                $createdSchedules[] = $created;
            } catch (\Exception $e) {
                \Log::error('Error creating schedule batch', [
                    'index' => $index,
                    'data' => $data,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errors[] = "Lỗi khi tạo khung giờ thứ " . ($data['day_of_week'] ?? 'N/A') . " ({$data['start_time']} - {$data['end_time']}): " . $e->getMessage();
            }
        }

        if (count($errors) > 0 && count($createdSchedules) === 0) {
            throw new Exception('Không thể tạo khung giờ nào: ' . implode(', ', $errors), 400);
        }

        return [
            'schedules' => $createdSchedules,
            'errors' => $errors,
            'created_count' => count($createdSchedules),
            'failed_count' => count($errors),
        ];
    }

    public function updateSchedule($scheduleId, $user, array $data)
    {
        $schedule = $this->scheduleRepository->find($scheduleId);

        if (!$schedule) {
            throw new Exception('Không tìm thấy lịch', 404);
        }

        $this->ensureCourtOwnership($schedule->court_id, $user);

        // Check overlap (excluding current schedule)
        if (isset($data['start_time']) || isset($data['end_time']) || isset($data['day_of_week'])) {
            $effectiveFrom = $data['effective_from'] ?? $this->toDateString($schedule->effective_from);
            $effectiveTo = isset($data['effective_to']) ? (string) $data['effective_to'] : $this->toDateString($schedule->effective_to);
            $hasOverlap = $this->scheduleRepository->hasOverlap(
                $schedule->court_id,
                $data['day_of_week'] ?? $schedule->day_of_week,
                $data['start_time'] ?? $schedule->start_time,
                $data['end_time'] ?? $schedule->end_time,
                $effectiveFrom,
                $effectiveTo ?: null,
                $scheduleId
            );

            if ($hasOverlap) {
                throw new Exception('Khung giờ bị trùng với lịch đã có', 409);
            }
        }

        $this->scheduleRepository->update($scheduleId, $data);
        return $schedule->refresh();
    }

    /**
     * Convert effective_from/effective_to (string or Carbon) to Y-m-d string. Model does not cast to date.
     */
    private function toDateString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_object($value) && method_exists($value, 'toDateString')) {
            return $value->toDateString();
        }
        $str = (string) $value;
        if ($str === '') {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($str)->toDateString();
        } catch (\Throwable $e) {
            return substr($str, 0, 10) ?: null;
        }
    }

    public function deleteSchedule($scheduleId, $user)
    {
        $schedule = $this->scheduleRepository->find($scheduleId);

        if (!$schedule) {
            throw new Exception('Không tìm thấy lịch', 404);
        }

        $this->ensureCourtOwnership($schedule->court_id, $user);
        $this->scheduleRepository->delete($scheduleId);
        return true;
    }

    // ── Owner Extras (Catalog - shared across all venues/courts) ──

    public function getOwnerExtras($user)
    {
        return $this->extraRepository->getByOwner($user->id);
    }

    public function createOwnerExtra($user, array $data)
    {
        $data['user_id'] = $user->id;
        return $this->extraRepository->create($data);
    }

    public function updateOwnerExtra($extraId, $user, array $data)
    {
        $extra = $this->extraRepository->find($extraId);
        if (!$extra) {
            throw new Exception('Không tìm thấy option', 404);
        }
        if ($extra->user_id !== $user->id) {
            throw new Exception('Bạn không có quyền', 403);
        }
        $this->extraRepository->update($extraId, $data);
        return $extra->refresh();
    }

    public function deleteOwnerExtra($extraId, $user)
    {
        $extra = $this->extraRepository->find($extraId);
        if (!$extra) {
            throw new Exception('Không tìm thấy option', 404);
        }
        if ($extra->user_id !== $user->id) {
            throw new Exception('Bạn không có quyền', 403);
        }
        $this->extraRepository->delete($extraId);
        return true;
    }

    /**
     * Sync extras to a court from the owner catalog.
     */
    public function syncCourtExtras($courtId, $user, array $extraIds)
    {
        $this->ensureCourtOwnership($courtId, $user);
        $court = Court::find($courtId);
        $court->extras()->sync($extraIds);
        return $court->load('extras');
    }

    // ── Venue Reviews ──

    public function getVenueReviews($venueId, $user, int $perPage = 10)
    {
        $venue = $this->venueRepository->findByOwner($venueId, $user->id);

        if (!$venue) {
            throw new Exception('Không tìm thấy địa điểm', 404);
        }

        return Review::where('venue_id', $venueId)
            ->with(['user:id,name,avatar'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    // ── Venue Bookings ──

    public function getVenueBookings($venueId, $user, int $perPage = 10)
    {
        $venue = $this->venueRepository->findByOwner($venueId, $user->id);

        if (!$venue) {
            throw new Exception('Không tìm thấy địa điểm', 404);
        }

        // Get court IDs belonging to this venue
        $courtIds = Court::where('venue_id', $venueId)->pluck('id');

        return Booking::whereIn('court_id', $courtIds)
            ->with(['user:id,name,phone', 'court:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    // ── Owner create booking (walk-in / tại quầy) ──

    /**
     * Create a booking by owner (walk-in or on behalf of customer).
     */
    public function createOwnerBooking($user, array $data): Booking
    {
        $courtId = (int) $data['court_id'];
        $this->ensureCourtOwnership($courtId, $user);

        $date = $this->toDateString($data['date']) ?? $data['date'];
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];

        $exists = $this->bookingRepository->findPendingOrConfirmed($courtId, $date, $startTime, $endTime);
        if ($exists) {
            throw new Exception('Khung giờ này đã có người đặt', 409);
        }

        $payload = [
            'court_id' => $courtId,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'total_price' => (int) round((float) $data['total_price']),
            'status' => 'confirmed',
            'is_paid' => true,
            'payment_code' => null,
            'pending_expires_at' => null,
        ];

        if (!empty($data['user_id'])) {
            $payload['user_id'] = (int) $data['user_id'];
            $payload['guest_name'] = null;
            $payload['guest_phone'] = null;
        } else {
            $payload['user_id'] = null;
            $payload['guest_name'] = $data['guest_name'] ?? null;
            $payload['guest_phone'] = $data['guest_phone'] ?? null;
        }

        $booking = $this->bookingRepository->create($payload);
        return $booking->load(['court:id,name,venue_id', 'court.venue:id,name', 'user:id,name,phone,email']);
    }

    // ── Court availability (free vs booked slots) ──

    /**
     * Get availability for a court on a date: list of slots from court_schedules with status free/booked.
     */
    public function getCourtAvailability(int $courtId, string $date, $user): array
    {
        $this->ensureCourtOwnership($courtId, $user);

        $dateStr = $this->toDateString($date) ?? now()->toDateString();
        $dayOfWeek = (int) date('w', strtotime($dateStr)); // 0=Sun, 6=Sat

        $schedules = CourtSchedule::where('court_id', $courtId)
            ->forDay($dayOfWeek)
            ->effectiveOn($dateStr)
            ->orderBy('start_time')
            ->get();

        $bookings = $this->bookingRepository->getOccupiedBookingsForCourtDate($courtId, $dateStr);

        $slots = [];
        foreach ($schedules as $schedule) {
            $sStart = substr((string) $schedule->start_time, 0, 5);
            $sEnd = substr((string) $schedule->end_time, 0, 5);
            $booking = $bookings->first(function ($b) use ($schedule) {
                $bStart = substr((string) $b->start_time, 0, 5);
                $bEnd = substr((string) $b->end_time, 0, 5);
                return $schedule->start_time < $b->end_time && $schedule->end_time > $b->start_time;
            });

            $slots[] = [
                'schedule_id' => $schedule->id,
                'start_time' => $sStart,
                'end_time' => $sEnd,
                'price' => (int) $schedule->price,
                'status' => $booking ? 'booked' : 'free',
                'booking_id' => $booking?->id,
                'booking' => $booking ? [
                    'id' => $booking->id,
                    'status' => $booking->status,
                    'guest_name' => $booking->guest_name,
                    'guest_phone' => $booking->guest_phone,
                ] : null,
            ];
        }

        return [
            'date' => $dateStr,
            'day_of_week' => $dayOfWeek,
            'slots' => $slots,
        ];
    }

    // ── Helpers ──

    protected function ensureCourtOwnership(int $courtId, $user): void
    {
        $court = Court::whereHas('venue', fn($q) => $q->where('owner_id', $user->id))
            ->find($courtId);

        if (!$court) {
            throw new Exception('Không tìm thấy sân hoặc bạn không có quyền', 403);
        }
    }
}
