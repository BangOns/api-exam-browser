<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
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

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials do not match our records.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
