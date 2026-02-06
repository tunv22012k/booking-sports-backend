<?php

namespace App\Services;

use App\Repositories\ChatRepository;
use App\Events\MessageSent;
use App\Events\UserReceivedMessage;
use App\Events\MessageRead;
use Illuminate\Support\Facades\Log;

class ChatService
{
    protected $chatRepository;

    public function __construct(ChatRepository $chatRepository)
    {
        $this->chatRepository = $chatRepository;
    }

    public function sendMessage($user, $chatId, $data)
    {
        $messageData = [
            'chat_id' => $chatId,
            'sender_id' => $user->id,
            'text' => $data['text'] ?? null,
            'type' => $data['type'] ?? 'text',
            'media' => $data['media'] ?? null,
        ];

        $message = $this->chatRepository->createMessage($messageData);
        
        // Broadcast to chat channel
        broadcast(new MessageSent($message, $chatId))->toOthers();

        // Broadcast to recipient
        $this->broadcastToRecipient($user, $chatId, $message);

        return $message;
    }

    public function getMessages($chatId, $limit, $before)
    {
        return $this->chatRepository->getMessages($chatId, $limit, $before);
    }

    public function getChats($user, $perPage, $search, $wantsPagination)
    {
        $userId = $user->id;
        $googleId = $user->google_id;

        $query = $this->chatRepository->getChatIdsByUser($userId, $googleId, $search);

        if ($wantsPagination) {
            $paginatedIds = $query->paginate($perPage);
            $chatIds = $paginatedIds->pluck('chat_id');
        } else {
            $chatIds = $query->pluck('chat_id');
        }

        $usersData = $this->fetchChatUsers($chatIds, $userId, $googleId);

        if ($wantsPagination) {
            $response = $paginatedIds->toArray();
            $response['data'] = $usersData;
            return $response;
        }

        return $usersData;
    }

    public function markAsRead($user, $chatId)
    {
        // Normalize chatId
        $parts = explode('_', $chatId);
        if (count($parts) === 2) {
            sort($parts);
            $chatId = implode('_', $parts);
        }

        $affectedRows = $this->chatRepository->markMessagesAsRead($chatId, $user->id);

        if ($affectedRows > 0) {
             $this->broadcastReadEvent($user, $parts, $chatId);
        }

        return $affectedRows;
    }

    public function getUnreadCount($user)
    {
        return $this->chatRepository->getUnreadCount($user->id, $user->google_id);
    }

    // Private helpers

    private function broadcastToRecipient($sender, $chatId, $message)
    {
        $parts = explode('_', $chatId);
        $recipientId = null;
        if (count($parts) === 2) {
            // Check against both id and google_id to be safe
            $isPart0Me = ($parts[0] == $sender->id || $parts[0] == $sender->google_id);
            $recipientId = $isPart0Me ? $parts[1] : $parts[0];
        }

        if ($recipientId) {
            $recipientUser = $this->chatRepository->findUserByIdOrGoogleId($recipientId);

            if ($recipientUser) {
                // Frontend listens on `google_id` if available, otherwise `id`
                $recipientChannelId = $recipientUser->google_id ?? $recipientUser->id;
                
                $message->load(['sender', 'reactions']);
                
                broadcast(new UserReceivedMessage($message, $recipientChannelId));

                // Broadcast to sender as well
                $senderChannelId = $sender->google_id ?? $sender->id;
                if ($senderChannelId != $recipientChannelId) {
                     broadcast(new UserReceivedMessage($message, $senderChannelId));
                }
            }
        }
    }

    private function broadcastReadEvent($user, $chatIdParts, $chatId)
    {
        $myIds = array_filter([$user->id, $user->google_id]);
        $otherId = null;
        
        if (in_array($chatIdParts[0], $myIds)) {
            $otherId = $chatIdParts[1];
        } elseif (in_array($chatIdParts[1], $myIds)) {
            $otherId = $chatIdParts[0];
        } else {
            // Fallback string compare
            if (in_array((string)$chatIdParts[0], array_map('strval', $myIds))) $otherId = $chatIdParts[1];
            elseif (in_array((string)$chatIdParts[1], array_map('strval', $myIds))) $otherId = $chatIdParts[0];
        }

        if ($otherId) {
            $otherUser = $this->chatRepository->findUserByIdOrGoogleId($otherId);
            
            if ($otherUser) {
                $channelId = $otherUser->google_id ?? $otherUser->id;
                broadcast(new MessageRead($chatId, now(), $user->google_id ?? $user->id, $channelId));
            }
        }
    }

    private function fetchChatUsers($chatIds, $userId, $googleId)
    {
        if ($chatIds->isEmpty()) return [];

        $otherUserIds = [];
        foreach ($chatIds as $cId) {
            $parts = explode('_', $cId);
            if (count($parts) === 2) {
                $isPart0Me = ($parts[0] == $userId || $parts[0] == $googleId);
                $isPart1Me = ($parts[1] == $userId || $parts[1] == $googleId);

                if ($isPart0Me) {
                    $otherUserIds[] = $parts[1];
                } elseif ($isPart1Me) {
                    $otherUserIds[] = $parts[0];
                }
            }
        }
        
        $otherUserIds = array_unique($otherUserIds);
        
        $safeIds = [];
        $allIdsAsString = [];
        
        foreach ($otherUserIds as $uid) {
            $allIdsAsString[] = (string)$uid;
            if (is_numeric($uid) && strlen((string)$uid) <= 18) {
                $safeIds[] = $uid;
            }
        }

        $latestMessages = $this->chatRepository->getLatestMessagesByChatIds($chatIds);
        $users = $this->chatRepository->findUsersByIdsAndGoogleIds($safeIds, $allIdsAsString);
            
        return $users->map(function($u) use ($latestMessages, $userId, $googleId) {
            $myIds = array_filter([$userId, $googleId]);
            $theirIds = array_filter([$u->id, $u->google_id]);
            
            $lastMsg = null;
            
            foreach ($myIds as $myId) {
                foreach ($theirIds as $theirId) {
                    $possibleId = [$myId, $theirId];
                    sort($possibleId);
                    $chatId = implode('_', $possibleId);
                    
                    $found = $latestMessages->firstWhere('chat_id', $chatId);
                    if ($found) {
                        if (!$lastMsg || $found->created_at > $lastMsg->created_at) {
                             $lastMsg = $found;
                        }
                    }
                }
            }
            
            $u->last_message = $lastMsg ? ($lastMsg->text ?: ($lastMsg->type === 'image' ? '[Hình ảnh]' : ($lastMsg->type === 'video' ? '[Video]' : 'Tin nhắn mới'))) : null;
            $u->last_message_sender_id = $lastMsg ? $lastMsg->sender_id : null;
            $u->last_message_read_at = $lastMsg ? $lastMsg->read_at : null;
            $u->last_message_time = $lastMsg ? $lastMsg->created_at : null;
            return $u;
        })->sortByDesc('last_message_time')->values()->all();
    }
}
