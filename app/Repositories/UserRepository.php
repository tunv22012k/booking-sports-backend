<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    public function getModel()
    {
        return User::class;
    }

    public function findByEmail($email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function updateOrCreateGoogleUser($data)
    {
        return $this->model->updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'google_id' => $data['google_id'],
                'avatar' => $data['avatar'],
                'password' => null, // Password is null for social login
            ]
        );
    }

    public function getUsers($excludeId, $search, $perPage)
    {
        $query = $this->model->where('id', '!=', $excludeId);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('updated_at', 'desc')->paginate($perPage);
    }

    public function findByIdOrGoogleId($id)
    {
        return $this->model->where(function($q) use ($id) {
            $q->where('id', $id)->orWhere('google_id', (string)$id);
        })->first();
    }

    public function findByGoogleId($id)
    {
        return $this->model->where('google_id', (string)$id)->first();
    }
}
