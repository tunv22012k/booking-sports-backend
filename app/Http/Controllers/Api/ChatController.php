<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function sendMessage(Request $request, $chatId)
    {
        $request->validate([
            'text' => 'nullable|string',
            'type' => 'required|string',
            'media' => 'nullable',
        ]);

        $user = $request->user();

        $message = \App\Models\Message::create([
            'chat_id' => $chatId,
            'sender_id' => $user->id,
            'text' => $request->text,
            'type' => $request->type ?? 'text',
            'media' => $request->media,
        ]);
        
        // Broadcast to chat channel
        broadcast(new MessageSent($message, $chatId))->toOthers();

        // Also broadcast to the recipient so their Sidebar updates immediately even if they aren't in the chat view
        // Extract recipient ID from chatId (uid1_uid2)
        $parts = explode('_', $chatId);
        $recipientId = null;
        if (count($parts) === 2) {
            // Check against both id and google_id to be safe
            $isPart0Me = ($parts[0] == $user->id || $parts[0] == $user->google_id);
            $recipientId = $isPart0Me ? $parts[1] : $parts[0];
        }

        if ($recipientId) {
            \Illuminate\Support\Facades\Log::info("ChatController: Target Recipient ID extracted: " . $recipientId);
            
            // Fix: Check if recipientId is a valid bigint (Postgres bigint max is ~9e18, 19 digits)
            // Google IDs can be larger (21+ digits), causing "Numeric value out of range" if checked against 'id'
            $isIntegerSafe = is_numeric($recipientId) && strlen((string)$recipientId) < 19;

            $recipientUser = null;
            
            if ($isIntegerSafe) {
                 // Safe to check both
                 $recipientUser = User::where('id', $recipientId)
                    ->orWhere('google_id', $recipientId)
                    ->first();
            } else {
                 // Only check google_id to avoid SQL overflow
                 $recipientUser = User::where('google_id', $recipientId)->first();
            }

            if ($recipientUser) {
                // Frontend listens on `google_id` if available, otherwise `id`
                $recipientChannelId = $recipientUser->google_id ?? $recipientUser->id;
                \Illuminate\Support\Facades\Log::info("ChatController: Broadcasting to channel user: " . $recipientChannelId);
                
                // Load sender and reactions so frontend can access sender's google_id to match with sidebar list
                $message->load(['sender', 'reactions']);
                
                broadcast(new \App\Events\UserReceivedMessage($message, $recipientChannelId));

                // Also broadcast to the sender so their sidebar updates (moves chat to top, updates last message)
                // Sender's listening ID:
                $senderChannelId = $user->google_id ?? $user->id;
                if ($senderChannelId != $recipientChannelId) {
                     broadcast(new \App\Events\UserReceivedMessage($message, $senderChannelId));
                }
            } else {
                \Illuminate\Support\Facades\Log::warning("ChatController: Recipient user not found for ID: " . $recipientId);
            }
        } else {
             \Illuminate\Support\Facades\Log::warning("ChatController: Could not extract recipient ID from ChatID: " . $chatId);
        }

        return response()->json(['status' => 'Message Sent!', 'message' => $message]);
    }

    public function getMessages(Request $request, $chatId)
    {
        $limit = $request->input('limit', 20);
        $before = $request->input('before');

        $query = \App\Models\Message::where('chat_id', $chatId)
            ->with(['sender', 'reactions']); // Load sender and reactions

        if ($before) {
            $query->where('created_at', '<', $before);
        }

        $messages = $query->orderBy('created_at', 'desc') // Latest first (relative to cursor)
            ->take($limit)
            ->get();

        // Reverse back to ASC for frontend display (Oldest -> Newest)
        $messages = $messages->sortBy('created_at')->values();

        return response()->json(['messages' => $messages]);
    }

    public function getChats(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;

        // Find all chat_ids where this user is involved
        // Pattern: "min_max". user ID could be at start or end.
        // We can check if distinct chat_ids contain the user ID.
        // Note: Simple LIKE query might match "1" in "10". 
        // Better: chat_id is always "ID_ID". 
        // But IDs are bigints.
        
        // Let's rely on string parsing for now or exact matches if we knew the format.
        // Format: sorted(uid1, uid2).join('_').
        
        $googleId = $user->google_id;

        $query = \App\Models\Message::query()
            ->select('chat_id')
            ->distinct()
            ->where(function ($q) use ($userId) {
                $q->where('chat_id', 'like', "{$userId}_%")
                  ->orWhere('chat_id', 'like', "%_{$userId}");
            });

        if ($googleId) {
            $query->orWhere(function ($q) use ($googleId) {
                $q->where('chat_id', 'like', "{$googleId}_%")
                  ->orWhere('chat_id', 'like', "%_{$googleId}");
            });
        }

        $chatIds = $query->pluck('chat_id');
            
        // Extract other user IDs
        $otherUserIds = [];
        foreach ($chatIds as $cId) {
            $parts = explode('_', $cId);
            if (count($parts) === 2) {
                // Check against both id and google_id to determine which part is "me"
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
        
        // Segregate IDs to avoid Postgres integer overflow
        $safeIds = [];
        $allIdsAsString = [];
        
        foreach ($otherUserIds as $uid) {
            $allIdsAsString[] = (string)$uid;
            if (is_numeric($uid) && strlen((string)$uid) <= 18) {
                $safeIds[] = $uid;
            }
        }

        // Fetch the latest message for each valid chat_id
        // We use a subquery or simply fetch all latest messages for these chat IDs
        // Since we have distinct chatIds, let's fetch the latest message for each.
        // Optimization: In a real app, use a window function or a dedicated 'conversations' table.
        // Here: Select * where In chatIds order by created_at desc.
        // We can't easily valid "latest per group" in Eloquent without raw SQL.
        // Simpler approach for now: Fetch all messages for these chats, group by chat_id, take last.
        // Warning: This is heavy if history is huge.
        // Better: Use a raw query to get the ID of the latest message per chat.
        
        $latestMessages = \App\Models\Message::whereIn('chat_id', $chatIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('chat_id');

        $users = User::whereIn('id', $safeIds)
            ->orWhereIn('google_id', $allIdsAsString)
            ->get();
            
        // Map messages to users
        $users->map(function($u) use ($latestMessages, $userId, $googleId) {
            // Reconstruct possible chat IDs to find the match
            // The chat ID between 'me' ($userId or $googleId) and 'them' ($u->id or $u->google_id)
            // We need to check all combinations because we don't know which ID type was used to create the chat
            
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
                        // If we found a message, pick it.
                        // If there are multiple chat versions (e.g. id_id vs google_google), prefer the most recent?
                        // For now just taking the first found.
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
        });
        
        return response()->json($users); // Return users array directly to match Sidebar expectation
    }

    public function markAsRead(Request $request, $chatId)
    {
        $user = $request->user();
        
        // Ensure consistent chatId sorting (internal_id vs google_id complexity handled by sorting)
        // If frontend sends 'B_A' but DB stores 'A_B', we need to match DB.
        // Assuming DB creation logic always sorts IDs.
        $parts = explode('_', $chatId);
        if (count($parts) === 2) {
            sort($parts);
            $chatId = implode('_', $parts);
        }

        // Update all unread messages in this chat sent by OTHER users
        // i.e., messages where sender_id != me AND read_at IS NULL
        // We need to be careful: $user->id is internal ID.
        // But message->sender_id might be Google ID if that's how it was stored (though we prefer internal).
        // Let's assume message->sender_id matches either user->id OR user->google_id
        
        $query = \App\Models\Message::where('chat_id', $chatId)
            ->whereNull('read_at');
            
        // Exclude my own messages (I don't "read" my own messages)
        // sender_id in DB is always the internal ID (bigint), so we only filter by $user->id.
        // Including google_id (string) causes "Numeric value out of range" if it's too large for bigint.
        $query->where('sender_id', '!=', $user->id);
        
        $affectedRows = $query->update(['read_at' => now()]);

        if ($affectedRows > 0) {
             // Broadcast event to the OTHER user(s) in the chat
             // Extract other user ID from chatId
             // Re-parse parts (reuse sorted parts)
             
             // Determine which ID is 'other'
             // One of them should match one of $myIds
             $myIds = array_filter([$user->id, $user->google_id]);
             $otherId = null;
             
             if (in_array($parts[0], $myIds)) {
                 $otherId = $parts[1];
             } elseif (in_array($parts[1], $myIds)) {
                 $otherId = $parts[0];
             } else {
                 // Fallback: If neither matches (weird), maybe type mismatch?
                 // Try string comparison
                 if (in_array((string)$parts[0], array_map('strval', $myIds))) $otherId = $parts[1];
                 elseif (in_array((string)$parts[1], array_map('strval', $myIds))) $otherId = $parts[0];
             }

             if ($otherId) {
                 // Handle potential BigInt overflow for Google IDs
                 // If ID is numeric but very long (>18 chars), it's likely a Google ID, not a Postgres BigInt ID.
                 // Postgres BigInt max is 9223372036854775807 (19 digits).
                 
                 $otherUser = User::query();
                 // If clearly a Google ID string
                 if (is_numeric($otherId) && strlen((string)$otherId) > 18) {
                     $otherUser->where('google_id', (string)$otherId);
                 } else {
                     $otherUser->where(function($q) use ($otherId) {
                         $q->where('id', $otherId)->orWhere('google_id', $otherId);
                     });
                 }
                 $otherUser = $otherUser->first();
                 
                 if ($otherUser) {
                     $channelId = $otherUser->google_id ?? $otherUser->id;
                     broadcast(new \App\Events\MessageRead($chatId, now(), $user->google_id ?? $user->id, $channelId));
                 }
             }
        }
        
        return response()->json(['status' => 'success', 'marked_count' => $affectedRows]);
    }
    public function getUnreadCount(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;
        $googleId = $user->google_id;
        
        $count = \App\Models\Message::whereNull('read_at')
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
            
        return response()->json(['count' => $count]);
    }
}
