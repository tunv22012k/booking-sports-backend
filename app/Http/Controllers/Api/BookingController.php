<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    // Pending timeout in minutes
    const PENDING_TIMEOUT_MINUTES = 10;

    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $status = $request->input('status');

        $query = Booking::with(['court.venue'])
            ->where('user_id', $request->user()->id);

        if ($status) {
            $statuses = explode(',', $status);
            $query->whereIn('status', $statuses);
        }

        $bookings = $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate($limit);
            
        return response()->json($bookings);
    }

    /**
     * Initiate a booking (creates pending booking with 10-minute hold)
     */
    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'court_id' => 'required|exists:courts,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'total_price' => 'required|numeric',
            'extras' => 'nullable|array',
        ]);

        // First, expire any old pending bookings
        $this->expireOldPendingBookings();

        // Check availability (including pending bookings that haven't expired)
        $exists = Booking::where('court_id', $validated['court_id'])
            ->where('date', $validated['date'])
            ->where(function($q) {
                $q->where('status', '!=', 'cancelled')
                  ->where(function($sub) {
                      // Confirmed/completed bookings
                      $sub->whereIn('status', ['confirmed', 'completed'])
                          // OR pending bookings that haven't expired
                          ->orWhere(function($pending) {
                              $pending->where('status', 'pending')
                                      ->where('pending_expires_at', '>', now());
                          });
                  });
            })
            ->where(function($q) use ($validated) {
                $q->where('start_time', '<', $validated['end_time'])
                  ->where('end_time', '>', $validated['start_time']);
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Khung giờ này đã có người đặt hoặc đang được giữ chỗ'
            ], 409);
        }

        // Generate unique payment code for QR
        $paymentCode = 'BK' . strtoupper(Str::random(8)) . time();

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'court_id' => $validated['court_id'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'total_price' => $validated['total_price'],
            'status' => 'pending',
            'is_paid' => false,
            'pending_expires_at' => now()->addMinutes(self::PENDING_TIMEOUT_MINUTES),
            'payment_code' => $paymentCode,
        ]);

        // Load relationships for response
        $booking->load('court.venue');

        // Broadcast event for real-time updates
        broadcast(new \App\Events\BookingPendingEvent($booking, 'created'))->toOthers();

        return response()->json([
            'booking' => $booking,
            'payment_code' => $paymentCode,
            'expires_at' => $booking->pending_expires_at,
            'timeout_minutes' => self::PENDING_TIMEOUT_MINUTES,
        ], 201);
    }

    /**
     * Confirm payment for a pending booking
     */
    public function confirm(Request $request, $id)
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Không tìm thấy đơn đặt sân hoặc đã hết hạn'
            ], 404);
        }

        // Check if still within timeout
        if ($booking->pending_expires_at && Carbon::parse($booking->pending_expires_at)->isPast()) {
            $booking->update(['status' => 'cancelled']);
            
            broadcast(new \App\Events\BookingPendingEvent($booking, 'expired'))->toOthers();
            
            return response()->json([
                'message' => 'Đơn đặt sân đã hết thời gian giữ chỗ'
            ], 410);
        }

        // Confirm the booking
        $booking->update([
            'status' => 'confirmed',
            'is_paid' => true,
            'pending_expires_at' => null,
        ]);

        $booking->load('court.venue');

        // Broadcast confirmation
        broadcast(new \App\Events\BookingPendingEvent($booking, 'confirmed'))->toOthers();

        return response()->json([
            'message' => 'Đặt sân thành công!',
            'booking' => $booking,
        ]);
    }

    /**
     * Cancel a pending booking
     */
    public function cancel(Request $request, $id)
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Không tìm thấy đơn đặt sân'
            ], 404);
        }

        $booking->update([
            'status' => 'cancelled',
            'pending_expires_at' => null,
        ]);

        // Broadcast cancellation for real-time updates
        broadcast(new \App\Events\BookingPendingEvent($booking, 'cancelled'))->toOthers();

        return response()->json([
            'message' => 'Đã hủy đặt sân'
        ]);
    }

    /**
     * Get pending slots for a venue (for showing to other users)
     */
    public function getPendingSlots(Request $request, $venueId)
    {
        $date = $request->query('date', now()->toDateString());

        // Get all pending bookings for this venue's courts that haven't expired
        $pendingBookings = Booking::whereHas('court', function($q) use ($venueId) {
                $q->where('venue_id', $venueId);
            })
            ->where('date', $date)
            ->where('status', 'pending')
            ->where('pending_expires_at', '>', now())
            ->get(['id', 'court_id', 'user_id', 'start_time', 'end_time', 'pending_expires_at']);

        return response()->json($pendingBookings);
    }

    /**
     * Get booking details by ID
     */
    public function show(Request $request, $id)
    {
        $booking = Booking::with(['court.venue', 'user'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Không tìm thấy đơn đặt sân'
            ], 404);
        }

        return response()->json($booking);
    }

    /**
     * Legacy store method - redirects to initiate
     */
    public function store(Request $request)
    {
        return $this->initiate($request);
    }

    /**
     * Expire old pending bookings
     */
    private function expireOldPendingBookings()
    {
        $expired = Booking::where('status', 'pending')
            ->where('pending_expires_at', '<', now())
            ->get();

        foreach ($expired as $booking) {
            $booking->update(['status' => 'cancelled']);
            broadcast(new \App\Events\BookingPendingEvent($booking, 'expired'))->toOthers();
        }
    }
    public function transfer(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        // Authorization check
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'confirmed') {
            return response()->json(['message' => 'Only confirmed bookings can be transferred'], 400);
        }

        if ($booking->transfer_status === 'available') {
            return response()->json(['message' => 'Booking is already for transfer'], 400);
        }

        $booking->update([
            'transfer_status' => 'available'
        ]);

        return response()->json(['message' => 'Booking marked for transfer successfully', 'data' => $booking]);
    }
}
