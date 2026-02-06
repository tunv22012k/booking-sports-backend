<?php

namespace App\Repositories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChatRepository extends BaseRepository
{
    public function getModel()
    {
        return Message::class;
    }

    public function createMessage(array $data)
    {
        return $this->create($data);
    }

    public function getMessages($chatId, $limit = 20, $before = null)
    {
        $query = $this->model->where('chat_id', $chatId)
            ->with(['sender', 'reactions']);

        if ($before) {
            $query->where('created_at', '<', $before);
        }

        $messages = $query->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();

        // Reverse back to ASC for frontend
        return $messages->sortBy('created_at')->values();
    }

    public function getChatIdsByUser($userId, $googleId, $search = null)
    {
        // Aggregation to get chat_ids ordered by last message time
        $query = $this->model->select('chat_id')
            ->selectRaw('MAX(created_at) as last_activity')
            ->groupBy('chat_id')
            ->orderBy('last_activity', 'desc');

        if ($search) {
             // Find users matching both name and email for better search
             $matchingUsers = User::where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->get();

            $candidateChatIds = [];
            $myIds = array_filter([$userId, $googleId]);

            foreach ($matchingUsers as $u) {
                // Skip myself
                if ($u->id == $userId) continue;

                $theirIds = array_filter([$u->id, $u->google_id]);
                foreach ($myIds as $myId) {
                    foreach ($theirIds as $theirId) {
                        $parts = [$myId, $theirId];
                        sort($parts);
                        $candidateChatIds[] = implode('_', $parts);
                    }
                }
            }
            
            if (empty($candidateChatIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('chat_id', $candidateChatIds);
            }
        } else {
            // Standard filter: Chats involving me
            $query->where(function ($q) use ($userId, $googleId) {
                $q->where('chat_id', 'like', "{$userId}_%")
                  ->orWhere('chat_id', 'like', "%_{$userId}");
                  
                if ($googleId) {
                    $q->orWhere('chat_id', 'like', "{$googleId}_%")
                      ->orWhere('chat_id', 'like', "%_{$googleId}");
                }
            });
        }

        return $query;
    }

    public function getLatestMessagesByChatIds($chatIds)
    {
        return $this->model->whereIn('chat_id', $chatIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('chat_id');
    }

    public function findUserByIdOrGoogleId($id)
    {
        // Handle potential BigInt overflow for Google IDs
        // If ID is numeric but very long (>18 chars), it's likely a Google ID
        if (is_numeric($id) && strlen((string)$id) > 18) {
            return User::where('google_id', (string)$id)->first();
        }

        return User::where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('google_id', $id);
        })->first();
    }

    public function findUsersByIdsAndGoogleIds($ids, $googleIds)
    {
        return User::whereIn('id', $ids)
            ->orWhereIn('google_id', $googleIds)
            ->get();
    }

    public function markMessagesAsRead($chatId, $excludeUserId)
    {
        $query = $this->model->where('chat_id', $chatId)
            ->whereNull('read_at')
            ->where('sender_id', '!=', $excludeUserId);
        
        return $query->update(['read_at' => now()]);
    }

    public function getUnreadCount($userId, $googleId)
    {
        return $this->model->whereNull('read_at')
            ->where('sender_id', '!=', $userId)
            ->where(function($q) use ($userId, $googleId) {
                $q->where('chat_id', 'like', "{$userId}_%")
                  ->orWhere('chat_id', 'like', "%_{$userId}");
                  
                if ($googleId) {
                    $q->orWhere('chat_id', 'like', "{$googleId}_%")
                      ->orWhere('chat_id', 'like', "%_{$googleId}");
                }
            })
            ->count();
    }
}
