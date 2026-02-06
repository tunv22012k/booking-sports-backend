<?php

namespace App\Services;

use App\Repositories\ReactionRepository;
use App\Models\Message;
use App\Events\MessageReactionUpdated;

class ReactionService
{
    protected $reactionRepository;

    public function __construct(ReactionRepository $reactionRepository)
    {
        $this->reactionRepository = $reactionRepository;
    }

    public function reactToMessage($user, $messageId, $reactionChar)
    {
        $message = Message::findOrFail($messageId);
        
        $existing = $this->reactionRepository->findByMessageAndUser($messageId, $user->id);
        $action = 'added';

        if ($existing) {
            if ($existing->reaction === $reactionChar) {
                // Toggle OFF
                $existing->delete();
                $action = 'removed';
            } else {
                // Update
                $existing->update(['reaction' => $reactionChar]);
                $action = 'updated';
            }
        } else {
            // New reaction
            $this->reactionRepository->create([
                'message_id' => $messageId,
                'user_id' => $user->id,
                'reaction' => $reactionChar
            ]);
        }

        // Broadcast event
        $message->load('reactions');
        
        broadcast(new MessageReactionUpdated($messageId, $message->chat_id, $message->reactions))->toOthers();

        return [
            'status' => 'success',
            'action' => $action,
            'reactions' => $message->reactions
        ];
    }
}
