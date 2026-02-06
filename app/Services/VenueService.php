<?php

namespace App\Services;

use App\Repositories\VenueRepository;
use App\Repositories\BookingRepository;

class VenueService
{
    protected $venueRepository;
    protected $bookingRepository;

    public function __construct(VenueRepository $venueRepository, BookingRepository $bookingRepository)
    {
        $this->venueRepository = $venueRepository;
        $this->bookingRepository = $bookingRepository;
    }

    public function listVenues($filters)
    {
        $limit = isset($filters['limit']) ? $filters['limit'] : null;
        return $this->venueRepository->getVenues($filters, $limit);
    }

    public function getVenueDetails($id)
    {
        return $this->venueRepository->findWithRelations($id, ['courts', 'extras', 'reviews.user']);
    }

    public function getVenueBookings($venueId, $date)
    {
        return $this->bookingRepository->getConfirmedBookingsForVenue($venueId, $date);
    }
}
