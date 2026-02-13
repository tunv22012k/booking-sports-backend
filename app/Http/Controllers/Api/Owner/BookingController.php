<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Venue;
use App\Models\Court;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
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

        // Search by customer name or phone
        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
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
}
