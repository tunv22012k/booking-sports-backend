<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || ($user->role !== 'owner' && $user->role !== 'admin')) {
            return response()->json([
                'message' => 'Bạn không có quyền truy cập khu vực này.'
            ], 403);
        }

        return $next($request);
    }
}
