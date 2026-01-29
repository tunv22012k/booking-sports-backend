<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\Venue::query();

        // Search filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
        }
        
        // Type filter
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        // Location-based Sorting (PostGIS)
        if ($request->has('lat') && $request->has('lng')) {
            $lat = $request->input('lat');
            $lng = $request->input('lng');

            // Use PostGIS for distance (in KM) and sorting
            // ST_MakePoint(lng, lat) because PostGIS uses (x, y) order
            $pointSql = "ST_SetSRID(ST_MakePoint(?, ?), 4326)";
            
            $query->selectRaw("*, (ST_Distance(coordinates, $pointSql) / 1000) as distance", [$lng, $lat])
                  ->orderByRaw("coordinates <-> $pointSql", [$lng, $lat]);
        }

        $limit = $request->input('limit', 12);

        // Eager load necessary relations and paginate
        return response()->json($query->with(['courts', 'extras', 'reviews'])->paginate($limit));
    }

    public function show($id)
    {
        $venue = \App\Models\Venue::with(['courts', 'extras', 'reviews.user'])
            ->findOrFail($id);
            
        // Note: 'courts.slots' doesn't exist as a relation in Court model yet?
        // We removed 'slots' table plan. So we don't load slots.
        // But frontend expects 'slots'.
        // We need to generating slots dynamically or return bookings?
        // The frontend VenueDetail uses 'slots' from mock data.
        // We should fix frontend to fetch bookings and generate slots, OR backend generates slots.
        // For now, let's just return venue. Frontend refactor is Phase 2.
        
        return response()->json($venue);
    }

    public function getBookings($id, \Illuminate\Http\Request $request)
    {
        // Get CONFIRMED bookings for all courts in this venue for a specific date
        // Pending bookings are handled separately via getPendingSlots
        $date = $request->input('date', now()->toDateString());
        
        $bookings = \App\Models\Booking::whereHas('court', function($q) use ($id) {
            $q->where('venue_id', $id);
        })
        ->where('date', $date)
        ->whereIn('status', ['confirmed', 'completed']) // Only confirmed bookings
        ->get(['court_id', 'start_time', 'end_time']);

        return response()->json($bookings);
    }
}
