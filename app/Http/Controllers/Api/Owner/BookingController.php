<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreOwnerBookingRequest;
use App\Models\Booking;
use App\Models\Court;
use App\Services\OwnerVenueService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        protected OwnerVenueService $ownerVenueService
    ) {}

    /**
     * GET /owner/bookings
     * Get all bookings for owner's venues
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $perPage = $request->input('per_page', 20);
        $status = $request->input('status');
        $search = $request->input('search');
        $venueId = $request->input('venue_id');
        $courtId = $request->input('court_id');

        // Build court IDs query
        $courtQuery = Court::whereHas('venue', function ($query) use ($user) {
            $query->where('owner_id', $user->id);
        });

        // Filter by specific venue
        if ($venueId && $venueId !== 'all') {
            $courtQuery->where('venue_id', $venueId);
        }

        // Filter by specific court
        if ($courtId && $courtId !== 'all') {
            $courtQuery->where('id', $courtId);
        }

        $courtIds = $courtQuery->pluck('id');

        $query = Booking::whereIn('court_id', $courtIds)
            ->with([
                'user:id,name,phone,email',
                'court:id,name,venue_id',
                'court.venue:id,name'
            ])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc');

        // Filter by status
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Search by customer name or phone (user or guest)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhere('guest_name', 'like', "%{$search}%")
                ->orWhere('guest_phone', 'like', "%{$search}%");
            });
        }

        $bookings = $query->paginate($perPage);

        return response()->json($bookings);
    }

    /**
     * GET /owner/bookings/{id}
     * Get booking detail
     */
    public function show(int $id): JsonResponse
    {
        $user = auth()->user();

        // Get all court IDs belonging to owner's venues
        $courtIds = Court::whereHas('venue', function ($query) use ($user) {
            $query->where('owner_id', $user->id);
        })->pluck('id');

        $booking = Booking::whereIn('court_id', $courtIds)
            ->with([
                'user:id,name,phone,email',
                'court:id,name,venue_id',
                'court.venue:id,name,address,phone'
            ])
            ->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Không tìm thấy lượt đặt'], 404);
        }

        return response()->json($booking);
    }

    /**
     * PUT /owner/bookings/{id}/status
     * Update booking status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();

        $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        // Get all court IDs belonging to owner's venues
        $courtIds = Court::whereHas('venue', function ($query) use ($user) {
            $query->where('owner_id', $user->id);
        })->pluck('id');

        $booking = Booking::whereIn('court_id', $courtIds)->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Không tìm thấy lượt đặt'], 404);
        }

        $booking->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Đã cập nhật trạng thái',
            'booking' => $booking->load([
                'user:id,name,phone,email',
                'court:id,name,venue_id',
                'court.venue:id,name,address,phone'
            ])
        ]);
    }

    /**
     * POST /owner/bookings
     * Create a booking (walk-in / đặt sân tại quầy).
     */
    public function store(StoreOwnerBookingRequest $request): JsonResponse
    {
        try {
            $booking = $this->ownerVenueService->createOwnerBooking(auth()->user(), $request->validated());
            return response()->json($booking, 201);
        } catch (Exception $e) {
            $code = (int) $e->getCode();
            if ($code < 400 || $code >= 600) {
                $code = 400;
            }
            return response()->json(['message' => $e->getMessage()], $code);
        }
    }
}
