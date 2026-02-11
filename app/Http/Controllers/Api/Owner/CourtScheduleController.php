<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreCourtScheduleRequest;
use App\Services\OwnerVenueService;
use Illuminate\Http\JsonResponse;
use Exception;

class CourtScheduleController extends Controller
{
    protected $ownerVenueService;

    public function __construct(OwnerVenueService $ownerVenueService)
    {
        $this->ownerVenueService = $ownerVenueService;
    }

    /**
     * GET /owner/courts/{courtId}/schedules
     */
    public function index(int $courtId): JsonResponse
    {
        try {
            $schedules = $this->ownerVenueService->getCourtSchedules($courtId, auth()->user());
            return response()->json($schedules);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }

    /**
     * POST /owner/courts/{courtId}/schedules
     */
    public function store(StoreCourtScheduleRequest $request, int $courtId): JsonResponse
    {
        try {
            $schedule = $this->ownerVenueService->createSchedule(
                $courtId,
                auth()->user(),
                $request->validated()
            );
            return response()->json($schedule, 201);
        } catch (Exception $e) {
            $code = (int) $e->getCode();
            return response()->json(
                ['message' => $e->getMessage()],
                in_array($code, [400, 403, 404, 409]) ? $code : 500
            );
        }
    }

    /**
     * PUT /owner/schedules/{id}
     */
    public function update(StoreCourtScheduleRequest $request, int $id): JsonResponse
    {
        try {
            $schedule = $this->ownerVenueService->updateSchedule(
                $id,
                auth()->user(),
                $request->validated()
            );
            return response()->json($schedule);
        } catch (Exception $e) {
            $code = (int) $e->getCode();
            return response()->json(
                ['message' => $e->getMessage()],
                in_array($code, [400, 403, 404, 409]) ? $code : 500
            );
        }
    }

    /**
     * DELETE /owner/schedules/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->ownerVenueService->deleteSchedule($id, auth()->user());
            return response()->json(['message' => 'Đã xóa lịch thành công']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }
}
