<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $perPage = min($request->input('per_page', 20), 100); // Max 100 per page
        $search = $request->input('search', '');
        
        $query = User::where('id', '!=', $currentUser->id);
        
        // Apply search filter
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Order by most recently active
        $query->orderBy('updated_at', 'desc');
        
        // Paginate
        $users = $query->paginate($perPage);
        
        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'has_more' => $users->hasMorePages(),
            ]
        ]);
    }

    public function show($id)
    {
        // Check safety for bigint (approx 19 digits). Safe limit 18.
        $isSafeId = is_numeric($id) && strlen((string)$id) <= 18;

        if ($isSafeId) {
             $user = User::where(function($q) use ($id) {
                 $q->where('id', $id)->orWhere('google_id', (string)$id);
             })->first();
        } else {
             $user = User::where('google_id', (string)$id)->first();
        }

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }
}
