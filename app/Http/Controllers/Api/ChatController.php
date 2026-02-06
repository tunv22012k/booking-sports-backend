<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use App\Http\Requests\Chat\SendMessageRequest;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function sendMessage(SendMessageRequest $request, $chatId)
    {
        try {
            $message = $this->chatService->sendMessage($request->user(), $chatId, $request->validated());
            return $this->successResponse(['message' => $message], 'Message Sent!');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function getMessages(Request $request, $chatId)
    {
        $limit = $request->input('limit', 20);
        $before = $request->input('before');

        $messages = $this->chatService->getMessages($chatId, $limit, $before);

        return $this->successResponse(['messages' => $messages]);
    }

    public function getChats(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $search = $request->input('search');
        $wantsPagination = $request->has('page');

        $result = $this->chatService->getChats($request->user(), $perPage, $search, $wantsPagination);

        return $this->successResponse($result);
    }

    public function markAsRead(Request $request, $chatId)
    {
        try {
            $count = $this->chatService->markAsRead($request->user(), $chatId);
            return $this->successResponse(['marked_count' => $count]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function getUnreadCount(Request $request)
    {
        $count = $this->chatService->getUnreadCount($request->user());
        return $this->successResponse(['count' => $count]);
    }
}
