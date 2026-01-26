<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        // Return user's bookings with venue info
        $bookings = \App\Models\Booking::with(['court.venue'])
            ->where('user_id', $request->user()->id)
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();
            
        // Transform to match frontend expectation if needed (or do it in frontend)
        return response()->json($bookings);
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'court_id' => 'required|exists:courts,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'total_price' => 'required|numeric',
        ]);

        // Check availability
        $exists = \App\Models\Booking::where('court_id', $validated['court_id'])
            ->where('date', $validated['date'])
            ->where('status', '!=', 'cancelled')
            ->where(function($q) use ($validated) {
                // Overlap check
                $q->where(function($sub) use ($validated) {
                    $sub->where('start_time', '<', $validated['end_time'])
                        ->where('end_time', '>', $validated['start_time']);
                });
            })
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Slot already booked'], 409);
        }

        $booking = \App\Models\Booking::create([
            'user_id' => $request->user()->id,
            'court_id' => $validated['court_id'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'total_price' => $validated['total_price'],
            'status' => 'confirmed', // Auto confirm for demo
            'is_paid' => true, // Auto pay for demo
        ]);

        return response()->json($booking, 201);
    }
}
