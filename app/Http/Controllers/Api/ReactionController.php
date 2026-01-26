<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageReactionUpdated;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageReaction;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function react(Request $request, $messageId)
    {
        $request->validate([
            'reaction' => 'required|string|max:10', // Allow small string for emoji
        ]);

        $user = $request->user();
        $message = Message::findOrFail($messageId);
        
        // Find existing reaction by this user on this message
        $existing = MessageReaction::where('message_id', $messageId)
            ->where('user_id', $user->id)
            ->first();

        $action = 'added';

        if ($existing) {
            if ($existing->reaction === $request->reaction) {
                // Same reaction -> Toggle OFF
                $existing->delete();
                $action = 'removed';
            } else {
                // Different reaction -> Update
                $existing->update(['reaction' => $request->reaction]);
                $action = 'updated';
            }
        } else {
            // New reaction
            MessageReaction::create([
                'message_id' => $messageId,
                'user_id' => $user->id,
                'reaction' => $request->reaction
            ]);
        }

        // Broadcast event
        // We need to send the UPDATED list of reactions for this message
        // Or just the specific change. Ideally, sending the fresh list prevents sync issues.
        $message->load('reactions'); // Reload reactions
        
        broadcast(new MessageReactionUpdated($messageId, $message->chat_id, $message->reactions))->toOthers();

        return response()->json([
            'status' => 'success', 
            'action' => $action,
            'reactions' => $message->reactions
        ]);
    }
}
