<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function index()
    {
        // List bookings available for transfer
        $listings = \App\Models\Booking::with(['court.venue'])
            ->where('is_for_transfer', true)
            ->where('transfer_status', 'available')
            ->where('date', '>=', now()->toDateString()) // Only future/today
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($listings);
    }

    public function purchase(\Illuminate\Http\Request $request, $id)
    {
        $booking = \App\Models\Booking::findOrFail($id);
        $user = $request->user();

        if (!$booking->is_for_transfer || $booking->transfer_status !== 'available') {
            return response()->json(['message' => 'Booking not available for transfer'], 400);
        }

        if ($booking->user_id === $user->id) {
            return response()->json(['message' => 'Cannot buy your own booking'], 400);
        }

        // Transaction logic
        \Illuminate\Support\Facades\DB::transaction(function() use ($booking, $user) {
            $booking->update([
                'user_id' => $user->id, // Transfer ownership
                'transfer_status' => 'transferred',
                'requested_by' => null, // Clear request if any
            ]);
            
            // In real app, create Transaction record, deduct money, etc.
        });

        return response()->json(['message' => 'Transfer successful', 'booking' => $booking]);
    }
}
