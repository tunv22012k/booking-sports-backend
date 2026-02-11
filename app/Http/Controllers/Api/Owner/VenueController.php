<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreVenueRequest;
use App\Http\Requests\Owner\UpdateVenueRequest;
use App\Services\OwnerVenueService;
use Illuminate\Http\JsonResponse;
use Exception;

class VenueController extends Controller
{
    protected $ownerVenueService;

    public function __construct(OwnerVenueService $ownerVenueService)
    {
        $this->ownerVenueService = $ownerVenueService;
    }

    /**
     * GET /owner/venues
     */
    public function index(): JsonResponse
    {
        $venues = $this->ownerVenueService->getOwnerVenues(auth()->user());
        return response()->json($venues);
    }

    /**
     * POST /owner/venues
     */
    public function store(StoreVenueRequest $request): JsonResponse
    {
        try {
            $venue = $this->ownerVenueService->createVenue(
                auth()->user(),
                $request->validated()
            );
            return response()->json($venue, 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /owner/venues/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $venue = $this->ownerVenueService->getVenueDetail($id, auth()->user());
            return response()->json($venue);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }

    /**
     * PUT /owner/venues/{id}
     */
    public function update(UpdateVenueRequest $request, int $id): JsonResponse
    {
        try {
            $venue = $this->ownerVenueService->updateVenue(
                $id,
                auth()->user(),
                $request->validated()
            );
            return response()->json($venue);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }

    /**
     * DELETE /owner/venues/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->ownerVenueService->deleteVenue($id, auth()->user());
            return response()->json(['message' => 'Đã xóa địa điểm thành công']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }
}
