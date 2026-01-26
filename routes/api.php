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
    Route::get('/chats/{chatId}/messages', [\App\Http\Controllers\Api\ChatController::class, 'getMessages']);
    Route::post('/chats/{chatId}/read', [\App\Http\Controllers\Api\ChatController::class, 'markAsRead']);
    Route::get('/chats', [\App\Http\Controllers\Api\ChatController::class, 'getChats']);
    Route::get('/users', [\App\Http\Controllers\Api\UserController::class, 'index']);
    Route::get('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'show']);
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    
    // Reactions
    Route::post('/messages/{messageId}/react', [\App\Http\Controllers\Api\ReactionController::class, 'react']);
});
