<?php

namespace App\Services;

use App\Repositories\OwnerVenueRepository;
use App\Repositories\CourtScheduleRepository;
use App\Repositories\OwnerExtraRepository;
use App\Models\Court;
use App\Models\VenueAmenity;
use Exception;

class OwnerVenueService
{
    protected $venueRepository;
    protected $scheduleRepository;
    protected $extraRepository;

    public function __construct(
        OwnerVenueRepository $venueRepository,
        CourtScheduleRepository $scheduleRepository,
        OwnerExtraRepository $extraRepository
    ) {
        $this->venueRepository = $venueRepository;
        $this->scheduleRepository = $scheduleRepository;
        $this->extraRepository = $extraRepository;
    }

    // ── Venue ──

    public function getOwnerVenues($user)
    {
        return $this->venueRepository->getByOwner($user->id);
    }

    public function getVenueDetail($id, $user)
    {
        $venue = $this->venueRepository->findByOwner($id, $user->id);

        if (!$venue) {
            throw new Exception('Không tìm thấy địa điểm', 404);
        }

        return $venue;
    }

    public function createVenue($user, array $data)
    {
        $data['owner_id'] = $user->id;
        $venue = $this->venueRepository->create($data);

        // Create amenities if provided
        if (!empty($data['amenities'])) {
            foreach ($data['amenities'] as $amenity) {
                $venue->amenities()->create([
                    'name' => $amenity['name'],
                    'icon' => $amenity['icon'] ?? null,
                ]);
            }
        }

        return $venue->load('amenities');
    }

    public function updateVenue($id, $user, array $data)
    {
        $venue = $this->venueRepository->findByOwner($id, $user->id);

        if (!$venue) {
            throw new Exception('Không tìm thấy địa điểm', 404);
        }

        $venue->update($data);

        // Sync amenities if provided
        if (isset($data['amenities'])) {
            $venue->amenities()->delete();
            foreach ($data['amenities'] as $amenity) {
                $venue->amenities()->create([
                    'name' => $amenity['name'],
                    'icon' => $amenity['icon'] ?? null,
                ]);
            }
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

    public function updateSchedule($scheduleId, $user, array $data)
    {
        $schedule = $this->scheduleRepository->find($scheduleId);

        if (!$schedule) {
            throw new Exception('Không tìm thấy lịch', 404);
        }

        $this->ensureCourtOwnership($schedule->court_id, $user);

        // Check overlap (excluding current schedule)
        if (isset($data['start_time']) || isset($data['end_time']) || isset($data['day_of_week'])) {
            $hasOverlap = $this->scheduleRepository->hasOverlap(
                $schedule->court_id,
                $data['day_of_week'] ?? $schedule->day_of_week,
                $data['start_time'] ?? $schedule->start_time,
                $data['end_time'] ?? $schedule->end_time,
                $data['effective_from'] ?? $schedule->effective_from->toDateString(),
                $data['effective_to'] ?? ($schedule->effective_to ? $schedule->effective_to->toDateString() : null),
                $scheduleId
            );

            if ($hasOverlap) {
                throw new Exception('Khung giờ bị trùng với lịch đã có', 409);
            }
        }

        $this->scheduleRepository->update($scheduleId, $data);
        return $schedule->refresh();
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
