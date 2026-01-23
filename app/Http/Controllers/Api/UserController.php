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
        $users = User::where('id', '!=', $currentUser->id)->get();
        return response()->json($users);
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
