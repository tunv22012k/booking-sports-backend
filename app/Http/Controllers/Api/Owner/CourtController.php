<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreCourtRequest;
use App\Services\OwnerVenueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class CourtController extends Controller
{
    protected $ownerVenueService;

    public function __construct(OwnerVenueService $ownerVenueService)
    {
        $this->ownerVenueService = $ownerVenueService;
    }

    /**
     * GET /owner/venues/{venueId}/courts
     */
    public function index(int $venueId): JsonResponse
    {
        try {
            $courts = $this->ownerVenueService->getVenueCourts($venueId, auth()->user());
            return response()->json($courts);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }

    /**
     * POST /owner/venues/{venueId}/courts
     */
    public function store(StoreCourtRequest $request, int $venueId): JsonResponse
    {
        try {
            $court = $this->ownerVenueService->createCourt(
                $venueId,
                auth()->user(),
                $request->validated()
            );
            return response()->json($court, 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }

    /**
     * GET /owner/courts/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $court = $this->ownerVenueService->getCourtDetail($id, auth()->user());
            return response()->json($court);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }

    /**
     * PUT /owner/courts/{id}
     */
    public function update(StoreCourtRequest $request, int $id): JsonResponse
    {
        try {
            $court = $this->ownerVenueService->updateCourt(
                $id,
                auth()->user(),
                $request->validated()
            );
            return response()->json($court);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }

    /**
     * DELETE /owner/courts/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->ownerVenueService->deleteCourt($id, auth()->user());
            return response()->json(['message' => 'Đã xóa sân thành công']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }

    /**
     * POST /owner/courts/{courtId}/sync-extras
     * Attach/detach venue extras to a specific court.
     */
    public function syncExtras(Request $request, int $courtId): JsonResponse
    {
        try {
            $extraIds = $request->validate([
                'extra_ids' => 'required|array',
                'extra_ids.*' => 'integer|exists:owner_extras,id',
            ])['extra_ids'];

            $court = $this->ownerVenueService->syncCourtExtras(
                $courtId,
                auth()->user(),
                $extraIds
            );
            return response()->json($court);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }
}
