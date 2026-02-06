<?php

namespace App\Services;

use App\Repositories\UserRepository;

class AuthService
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function handleGoogleLogin($googleUser)
    {
        $user = $this->userRepository->updateOrCreateGoogleUser([
            'email' => $googleUser->getEmail(),
            'name' => $googleUser->getName(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
        ]);

        // Force update updated_at to reflect latest login activity
        $user->touch();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $token;
    }

    public function logout($user)
    {
        if ($user) {
            $user->touch(); // Update last activity
            $user->tokens()->delete(); // Revoke all tokens
        }
    }
}
