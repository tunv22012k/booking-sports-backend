<?php

namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function listUsers($currentUser, $params)
    {
        $perPage = min(data_get($params, 'per_page', 20), 100);
        $search = data_get($params, 'search', '');

        return $this->userRepository->getUsers($currentUser->id, $search, $perPage);
    }

    public function getUser($id)
    {
        // Check safety for bigint (approx 19 digits). Safe limit 18.
        $isSafeId = is_numeric($id) && strlen((string)$id) <= 18;

        if ($isSafeId) {
            $user = $this->userRepository->findByIdOrGoogleId($id);
        } else {
            $user = $this->userRepository->findByGoogleId($id);
        }

        if (!$user) {
            throw new \Exception('User not found', 404);
        }

        return $user;
    }
}
