<?php

namespace App\Repositories;

use App\Models\MessageReaction;

class ReactionRepository extends BaseRepository
{
    public function getModel()
    {
        return MessageReaction::class;
    }

    public function findByMessageAndUser($messageId, $userId)
    {
        return $this->model->where('message_id', $messageId)
            ->where('user_id', $userId)
            ->first();
    }
}
