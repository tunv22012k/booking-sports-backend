<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use App\Http\Requests\Booking\InitiateBookingRequest;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $status = $request->input('status');

        $bookings = $this->bookingService->getUserBookings($request->user(), $limit, $status);
            
        return $this->successResponse($bookings);
    }

    /**
     * Initiate a booking (creates pending booking with 10-minute hold)
     */
    public function initiate(InitiateBookingRequest $request)
    {
        try {
            $result = $this->bookingService->initiateBooking($request->user(), $request->validated());

            return $this->successResponse($result, 'Booking initiated', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Confirm payment for a pending booking
     */
    public function confirm(Request $request, $id)
    {
        try {
            $booking = $this->bookingService->confirmBooking($id, $request->user());

            return $this->successResponse([
                'booking' => $booking,
            ], 'Đặt sân thành công!');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Cancel a pending booking
     */
    public function cancel(Request $request, $id)
    {
        try {
            $this->bookingService->cancelBooking($id, $request->user());

            return $this->successResponse(null, 'Đã hủy đặt sân');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get pending slots for a venue (for showing to other users)
     */
    public function getPendingSlots(Request $request, $venueId)
    {
        $date = $request->query('date', now()->toDateString());

        $pendingBookings = $this->bookingService->getPendingSlots($venueId, $date);

        return $this->successResponse($pendingBookings);
    }

    /**
     * Get booking details by ID
     */
    public function show(Request $request, $id)
    {
        try {
            $booking = $this->bookingService->getBookingDetails($id, $request->user());
            return $this->successResponse($booking);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Legacy store method - redirects to initiate
     */
    public function store(Request $request)
    {
        // Use CreateFrom to adapt the request if needed, or just validate manually if strict typing fails.
        // Here we assume InitiateBookingRequest resolution works or we create it.
        // For simplicity in this refactor without changing route signatures too much:
        return $this->initiate(InitiateBookingRequest::createFrom($request));
    }

    public function transfer(Request $request, $id)
    {
        try {
            $booking = $this->bookingService->transferBooking($id, $request->user());
            return $this->successResponse($booking, 'Booking marked for transfer successfully');
        } catch (\Exception $e) {
             return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
