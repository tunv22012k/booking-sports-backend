<?php

namespace App\Services;

use App\Repositories\BookingRepository;
use App\Events\BookingPendingEvent;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class BookingService
{
    protected $bookingRepository;

    const PENDING_TIMEOUT_MINUTES = 10;

    public function __construct(BookingRepository $bookingRepository)
    {
        $this->bookingRepository = $bookingRepository;
    }

    public function getUserBookings($user, $limit, $status)
    {
        return $this->bookingRepository->getByUser($user->id, $limit, $status);
    }

    public function initiateBooking($user, $data)
    {
        // First, expire any old pending bookings
        $this->expireOldPendingBookings();

        // Check availability
        $exists = $this->bookingRepository->findPendingOrConfirmed(
            $data['court_id'],
            $data['date'],
            $data['start_time'],
            $data['end_time']
        );

        if ($exists) {
            throw new Exception('Khung giờ này đã có người đặt hoặc đang được giữ chỗ', 409);
        }

        // Generate unique payment code for QR
        $paymentCode = 'BK' . strtoupper(Str::random(8)) . time();

        $bookingData = array_merge($data, [
            'user_id' => $user->id,
            'status' => 'pending',
            'is_paid' => false,
            'pending_expires_at' => now()->addMinutes(self::PENDING_TIMEOUT_MINUTES),
            'payment_code' => $paymentCode,
        ]);

        $booking = $this->bookingRepository->create($bookingData);

        // Load relationships
        $booking->load('court.venue');

        // Broadcast event
        broadcast(new BookingPendingEvent($booking, 'created'))->toOthers();

        return [
            'booking' => $booking,
            'payment_code' => $paymentCode,
            'expires_at' => $booking->pending_expires_at,
            'timeout_minutes' => self::PENDING_TIMEOUT_MINUTES,
        ];
    }

    public function confirmBooking($id, $user)
    {
        $booking = $this->bookingRepository->findPendingByIdAndUser($id, $user->id);

        if (!$booking) {
            throw new Exception('Không tìm thấy đơn đặt sân hoặc đã hết hạn', 404);
        }

        // Check if still within timeout
        if ($booking->pending_expires_at && Carbon::parse($booking->pending_expires_at)->isPast()) {
            $booking->update(['status' => 'cancelled']);
            broadcast(new BookingPendingEvent($booking, 'expired'))->toOthers();
            throw new Exception('Đơn đặt sân đã hết thời gian giữ chỗ', 410);
        }

        // Confirm the booking
        $this->bookingRepository->update($booking->id, [
            'status' => 'confirmed',
            'is_paid' => true,
            'pending_expires_at' => null,
        ]);

        $booking->refresh()->load('court.venue');

        broadcast(new BookingPendingEvent($booking, 'confirmed'))->toOthers();

        return $booking;
    }

    public function cancelBooking($id, $user)
    {
        $booking = $this->bookingRepository->findPendingByIdAndUser($id, $user->id);

        if (!$booking) {
            throw new Exception('Không tìm thấy đơn đặt sân', 404);
        }

        $this->bookingRepository->update($booking->id, [
            'status' => 'cancelled',
            'pending_expires_at' => null,
        ]);

        broadcast(new BookingPendingEvent($booking, 'cancelled'))->toOthers();

        return true;
    }

    public function getPendingSlots($venueId, $date)
    {
        return $this->bookingRepository->getPendingSlotsForVenue($venueId, $date);
    }

    public function getBookingDetails($id, $user)
    {
        $booking = $this->bookingRepository->findByIdAndUser($id, $user->id);
        
        if (!$booking) {
            throw new Exception('Không tìm thấy đơn đặt sân', 404);
        }

        return $booking;
    }

    public function transferBooking($id, $user)
    {
        $booking = $this->bookingRepository->find($id);

        if (!$booking) {
             throw new Exception('Booking not found', 404);
        }

        // Authorization check
        if ($booking->user_id !== $user->id) {
            throw new Exception('Unauthorized', 403);
        }

        if ($booking->status !== 'confirmed') {
            throw new Exception('Only confirmed bookings can be transferred', 400);
        }

        if ($booking->transfer_status === 'available') {
            throw new Exception('Booking is already for transfer', 400);
        }

        $this->bookingRepository->update($booking->id, [
            'transfer_status' => 'available'
        ]);

        return $booking->refresh();
    }

    protected function expireOldPendingBookings()
    {
        $expired = $this->bookingRepository->getPendingExpired();

        foreach ($expired as $booking) {
            $booking->update(['status' => 'cancelled']);
            broadcast(new BookingPendingEvent($booking, 'expired'))->toOthers();
        }
    }
}
