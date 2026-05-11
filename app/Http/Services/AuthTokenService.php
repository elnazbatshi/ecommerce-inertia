<?php

namespace App\Http\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthTokenService
{
    public function register(array $data): User
    {
        $user = User::create($data);
        $user->assignRole('admin');

        return $user;
    }

    public function attempt(string $phone, string $password): User
    {
        $user = User::where('phone', $phone)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $user;
    }

    public function tokenPayload(User $user): array
    {
        return [
            'token' => $user->createToken(config('shop.api.token_name', 'shop-inertia'))->plainTextToken,
            'user' => UserResource::make($user)->resolve(),
        ];
    }
}
