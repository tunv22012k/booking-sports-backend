<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use GuzzleHttp\Client;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

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
                ->setHttpClient(new Client(['verify' => false]))
                ->stateless()
                ->user();
        } catch (InvalidStateException $e) {
            return $this->errorResponse('Invalid state', 400);
        } catch (\Exception $e) {
            return $this->errorResponse('Authentication failed', 400);
        }

        $token = $this->authService->handleGoogleLogin($googleUser);

        // Redirect to frontend with token
        return redirect()->to('http://localhost:3000/auth/callback?token=' . $token);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());
        return $this->successResponse(null, 'Logged out');
    }
}
