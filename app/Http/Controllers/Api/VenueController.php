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

        // Bounds filter (Map View)
        // Bounds filter (Map View) - DISABLED as per request to load all data
        // if ($request->has(['north', 'south', 'east', 'west'])) {
        //     $north = (float) $request->input('north');
        //     $south = (float) $request->input('south');
        //     $east = (float) $request->input('east');
        //     $west = (float) $request->input('west');

        //     // PostGIS: ST_MakeEnvelope(xmin, ymin, xmax, ymax, srid)
        //     $envelopeSql = "ST_MakeEnvelope(?, ?, ?, ?, 4326)";
            
        //     // Check if coordinates point is within the envelope
        //     // Note: ST_MakeEnvelope handles rectangle. 
        //     // Warning: If map crosses dateline (180/-180), this simple envelope might fail, 
        //     // but for Vietnam (102-110E) it is safe.
        //     $query->whereRaw("ST_Intersects(coordinates::geometry, $envelopeSql)", [$west, $south, $east, $north]);
        // }

        // Location-based Sorting (PostGIS)
        if ($request->has('lat') && $request->has('lng')) {
            $lat = $request->input('lat');
            $lng = $request->input('lng');

            // Use PostGIS for distance (in KM) and sorting
            // ST_MakePoint(lng, lat) because PostGIS uses (x, y) order
            $userPoint = "ST_SetSRID(ST_MakePoint(?, ?), 4326)"; // User location (Geometry)
            
            // Calculate distance using optimized 'coordinates' column (Geography type)
            // 'coordinates' is Geography, so ST_Distance returns Meters by default.
            // We cast userPoint to geography to match.
            $query->selectRaw("*, (ST_Distance(coordinates, $userPoint::geography) / 1000) as distance", [$lng, $lat])
                  ->orderByRaw("coordinates <-> $userPoint", [$lng, $lat]);
        }

        // Optimization for Map View (select subset of fields)
        if ($request->input('view') === 'map') {
            $query->select(['id', 'name', 'type', 'lat', 'lng', 'address', 'price', 'pricing_type', 'image', 'rating', 'total_reviews', 'description']);
        } else {
            // Eager load relations for List/Detail view
            $query->with(['courts', 'extras', 'reviews']);
        }
        
        // Pagination logic
        if ($request->has('limit')) {
            $limit = $request->input('limit');
            return response()->json($query->paginate($limit));
        }

        // Return all if no limit specified
        return response()->json($query->get());
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
