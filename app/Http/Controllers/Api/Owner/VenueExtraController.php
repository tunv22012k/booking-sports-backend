<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreCourtExtraRequest;
use App\Services\OwnerVenueService;
use Illuminate\Http\JsonResponse;
use Exception;

class VenueExtraController extends Controller
{
    protected $ownerVenueService;

    public function __construct(OwnerVenueService $ownerVenueService)
    {
        $this->ownerVenueService = $ownerVenueService;
    }

    /**
     * GET /owner/extras — danh sách extras của owner (dùng chung mọi venue/court)
     */
    public function index(): JsonResponse
    {
        try {
            $extras = $this->ownerVenueService->getOwnerExtras(auth()->user());
            return response()->json($extras);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }

    /**
     * POST /owner/extras — tạo extra mới
     */
    public function store(StoreCourtExtraRequest $request): JsonResponse
    {
        try {
            $extra = $this->ownerVenueService->createOwnerExtra(
                auth()->user(),
                $request->validated()
            );
            return response()->json($extra, 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }

    /**
     * PUT /owner/extras/{id} — sửa extra
     */
    public function update(StoreCourtExtraRequest $request, int $id): JsonResponse
    {
        try {
            $extra = $this->ownerVenueService->updateOwnerExtra(
                $id,
                auth()->user(),
                $request->validated()
            );
            return response()->json($extra);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }

    /**
     * DELETE /owner/extras/{id} — xoá extra
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->ownerVenueService->deleteOwnerExtra($id, auth()->user());
            return response()->json(['message' => 'Đã xóa option thành công']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], (int) $e->getCode() ?: 500);
        }
    }
}
