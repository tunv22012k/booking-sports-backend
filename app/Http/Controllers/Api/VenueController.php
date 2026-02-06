<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VenueService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VenueController extends Controller
{
    protected $venueService;

    public function __construct(VenueService $venueService)
    {
        $this->venueService = $venueService;
    }

    public function index(Request $request)
    {
        $venues = $this->venueService->listVenues($request->all());
        return $this->successResponse($venues);
    }

    public function show($id)
    {
        try {
            $venue = $this->venueService->getVenueDetails($id);
            return $this->successResponse($venue);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Venue not found', 404);
        }
    }

    public function getBookings($id, Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $bookings = $this->venueService->getVenueBookings($id, $date);
        return $this->successResponse($bookings);
    }
}
