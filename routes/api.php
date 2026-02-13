<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web']], function () {
    Route::get('/auth/google/redirect', [\App\Http\Controllers\Api\AuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [\App\Http\Controllers\Api\AuthController::class, 'handleGoogleCallback']);
});

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/chats/{chatId}/messages', [\App\Http\Controllers\Api\ChatController::class, 'sendMessage']);
    Route::get('/messages/unread-count', [\App\Http\Controllers\Api\ChatController::class, 'getUnreadCount']);
    Route::get('/chats/{chatId}/messages', [\App\Http\Controllers\Api\ChatController::class, 'getMessages']);
    Route::post('/chats/{chatId}/read', [\App\Http\Controllers\Api\ChatController::class, 'markAsRead']);
    Route::get('/chats', [\App\Http\Controllers\Api\ChatController::class, 'getChats']);
    Route::get('/users', [\App\Http\Controllers\Api\UserController::class, 'index']);
    Route::post('/user/profile', [\App\Http\Controllers\Api\UserController::class, 'updateProfile']);
    Route::get('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'show']);
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    
    // Reactions
    Route::post('/messages/{messageId}/react', [\App\Http\Controllers\Api\ReactionController::class, 'react']);
    
    // Booking System
    Route::get('/bookings', [\App\Http\Controllers\Api\BookingController::class, 'index']);
    Route::post('/bookings', [\App\Http\Controllers\Api\BookingController::class, 'store']);
    Route::post('/bookings/initiate', [\App\Http\Controllers\Api\BookingController::class, 'initiate']);
    Route::get('/bookings/{id}', [\App\Http\Controllers\Api\BookingController::class, 'show']);
    Route::post('/bookings/{id}/confirm', [\App\Http\Controllers\Api\BookingController::class, 'confirm']);

    Route::post('/bookings/{id}/cancel', [\App\Http\Controllers\Api\BookingController::class, 'cancel']);
    Route::post('/bookings/{id}/transfer', [\App\Http\Controllers\Api\BookingController::class, 'transfer']);
    
    // Marketplace
    Route::get('/marketplace', [\App\Http\Controllers\Api\MarketplaceController::class, 'index']);
    Route::post('/marketplace/{id}/purchase', [\App\Http\Controllers\Api\MarketplaceController::class, 'purchase']);
    
    // Reviews
    Route::post('/venues/{id}/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'store']);
});

// ── Owner Routes ──
Route::middleware(['auth:sanctum', 'owner'])->prefix('owner')->group(function () {
    // Venues
    Route::get('/venues', [\App\Http\Controllers\Api\Owner\VenueController::class, 'index']);
    Route::post('/venues', [\App\Http\Controllers\Api\Owner\VenueController::class, 'store']);
    Route::get('/venues/{id}', [\App\Http\Controllers\Api\Owner\VenueController::class, 'show']);
    Route::put('/venues/{id}', [\App\Http\Controllers\Api\Owner\VenueController::class, 'update']);
    Route::delete('/venues/{id}', [\App\Http\Controllers\Api\Owner\VenueController::class, 'destroy']);
    Route::get('/venues/{id}/reviews', [\App\Http\Controllers\Api\Owner\VenueController::class, 'getReviews']);
    Route::get('/venues/{id}/bookings', [\App\Http\Controllers\Api\Owner\VenueController::class, 'getBookings']);

    // Courts (nested under venue)
    Route::get('/venues/{venueId}/courts', [\App\Http\Controllers\Api\Owner\CourtController::class, 'index']);
    Route::post('/venues/{venueId}/courts', [\App\Http\Controllers\Api\Owner\CourtController::class, 'store']);
    Route::get('/courts/{id}', [\App\Http\Controllers\Api\Owner\CourtController::class, 'show']);
    Route::put('/courts/{id}', [\App\Http\Controllers\Api\Owner\CourtController::class, 'update']);
    Route::delete('/courts/{id}', [\App\Http\Controllers\Api\Owner\CourtController::class, 'destroy']);

    // Court Schedules
    Route::get('/courts/{courtId}/schedules', [\App\Http\Controllers\Api\Owner\CourtScheduleController::class, 'index']);
    Route::post('/courts/{courtId}/schedules', [\App\Http\Controllers\Api\Owner\CourtScheduleController::class, 'store']);
    Route::post('/courts/{courtId}/schedules/batch', [\App\Http\Controllers\Api\Owner\CourtScheduleController::class, 'storeBatch']);
    Route::put('/schedules/{id}', [\App\Http\Controllers\Api\Owner\CourtScheduleController::class, 'update']);
    Route::delete('/schedules/{id}', [\App\Http\Controllers\Api\Owner\CourtScheduleController::class, 'destroy']);

    // Owner Extras (Catalog - owner defines extras, shared across all venues/courts)
    Route::get('/extras', [\App\Http\Controllers\Api\Owner\VenueExtraController::class, 'index']);
    Route::post('/extras', [\App\Http\Controllers\Api\Owner\VenueExtraController::class, 'store']);
    Route::put('/extras/{id}', [\App\Http\Controllers\Api\Owner\VenueExtraController::class, 'update']);
    Route::delete('/extras/{id}', [\App\Http\Controllers\Api\Owner\VenueExtraController::class, 'destroy']);

    // Sync extras to a court (attach from owner catalog)
    Route::post('/courts/{courtId}/sync-extras', [\App\Http\Controllers\Api\Owner\CourtController::class, 'syncExtras']);

    // Owner Bookings
    Route::get('/bookings', [\App\Http\Controllers\Api\Owner\BookingController::class, 'index']);
    Route::get('/bookings/{id}', [\App\Http\Controllers\Api\Owner\BookingController::class, 'show']);
    Route::put('/bookings/{id}/status', [\App\Http\Controllers\Api\Owner\BookingController::class, 'updateStatus']);
});

// Public Routes
Route::get('/venues', [\App\Http\Controllers\Api\VenueController::class, 'index']);
Route::get('/venues/{id}', [\App\Http\Controllers\Api\VenueController::class, 'show']);
Route::get('/venues/{id}/bookings', [\App\Http\Controllers\Api\VenueController::class, 'getBookings']);
Route::get('/venues/{id}/pending-slots', [\App\Http\Controllers\Api\BookingController::class, 'getPendingSlots']);
Route::get('/venues/{id}/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'index']);

// Auth (public)
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'loginApi']);
