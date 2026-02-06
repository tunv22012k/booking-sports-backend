<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReactionService;
use App\Http\Requests\Chat\ReactRequest;

class ReactionController extends Controller
{
    protected $reactionService;

    public function __construct(ReactionService $reactionService)
    {
        $this->reactionService = $reactionService;
    }

    public function react(ReactRequest $request, $messageId)
    {
        try {
            $result = $this->reactionService->reactToMessage($request->user(), $messageId, $request->reaction);
            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
