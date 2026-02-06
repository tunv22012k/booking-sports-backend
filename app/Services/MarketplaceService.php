<?php

namespace App\Services;

use App\Repositories\MarketplaceRepository;
use Illuminate\Support\Facades\DB;

class MarketplaceService
{
    protected $marketplaceRepository;

    public function __construct(MarketplaceRepository $marketplaceRepository)
    {
        $this->marketplaceRepository = $marketplaceRepository;
    }

    public function getListings()
    {
        return $this->marketplaceRepository->getAvailableListings();
    }

    public function processPurchase($bookingId, $buyer)
    {
        $booking = $this->marketplaceRepository->find($bookingId);

        if (!$booking) {
            throw new \Exception('Booking not found', 404);
        }

        if (!$booking->is_for_transfer || $booking->transfer_status !== 'available') {
            throw new \Exception('Booking not available for transfer', 400);
        }

        if ($booking->user_id === $buyer->id) {
            throw new \Exception('Cannot buy your own booking', 400);
        }

        // Transaction logic
        DB::transaction(function() use ($booking, $buyer) {
            $booking->update([
                'user_id' => $buyer->id, // Transfer ownership
                'transfer_status' => 'transferred',
                'requested_by' => null, // Clear request if any
            ]);
            
            // In real app, create Transaction record, deduct money, etc.
        });

        return $booking;
    }
}
