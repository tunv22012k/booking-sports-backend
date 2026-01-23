<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id || (string) $user->google_id === (string) $id;
});

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    // Check if user is part of the chat
    // For now allow any authenticated user
    return $user != null;
});

Broadcast::channel('presence', function ($user) {
    return ['id' => $user->google_id ?? (string) $user->id, 'name' => $user->name, 'avatar' => $user->avatar];
});
