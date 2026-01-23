<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class AuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->setHttpClient(new \GuzzleHttp\Client(['verify' => false]))
                ->stateless()
                ->user();
        } catch (InvalidStateException $e) {
            return response()->json(['error' => 'Invalid state'], 400);
        }

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => null, // Password is null for social login users
            ]
        );

        // Force update updated_at to reflect latest login activity even if data hasn't changed
        $user->touch();

        $token = $user->createToken('auth_token')->plainTextToken;

        // Redirect to frontend with token
        return redirect()->to('http://localhost:3000/auth/callback?token=' . $token);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->touch(); // Update last activity before logout
            $user->tokens()->delete(); // Revoke all tokens (or just current one)
        }
        return response()->json(['message' => 'Logged out']);
    }
}
