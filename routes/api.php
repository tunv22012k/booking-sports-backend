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
    
    // Marketplace
    Route::get('/marketplace', [\App\Http\Controllers\Api\MarketplaceController::class, 'index']);
    Route::post('/marketplace/{id}/purchase', [\App\Http\Controllers\Api\MarketplaceController::class, 'purchase']);
    
    // Reviews
    Route::post('/venues/{id}/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'store']);
});

// Public Routes
Route::get('/venues', [\App\Http\Controllers\Api\VenueController::class, 'index']);
Route::get('/venues/{id}', [\App\Http\Controllers\Api\VenueController::class, 'show']);
Route::get('/venues/{id}/bookings', [\App\Http\Controllers\Api\VenueController::class, 'getBookings']);
Route::get('/venues/{id}/pending-slots', [\App\Http\Controllers\Api\BookingController::class, 'getPendingSlots']);

