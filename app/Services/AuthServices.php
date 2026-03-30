<?php

namespace App\Services;

use App\Exceptions\InvalidLoginException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthServices
{
    /**
     * Handle user registration.
     */
    public function register(array $data)
    {
        $data['password'] = Hash::make($data['password']);

        // Default role to student if not provided
        if (!isset($data['role'])) {
            $data['role'] = 'student';
        }

        return User::create($data);
    }

    /**
     * Handle user login.
     */
    public function login(array $data)
    {
        $user = User::where('username', $data['username'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw new InvalidLoginException();
        }

        $token = $user->createToken('access_token', ["role:{$user->role}", 'access_api'], Carbon::now()->addMinutes(60))->plainTextToken;
        $refresh_token = $user->createToken('refresh_token', ["role:{$user->role}", 'issue_access_api'], Carbon::now()->addDays(1))->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'refresh_token' => $refresh_token
        ];
    }
}
