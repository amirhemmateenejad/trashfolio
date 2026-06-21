<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Models\RefreshToken;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function refresh(RefreshTokenRequest $request)
    {
        $old = RefreshToken::where('token', $request->validated('refresh_token'))->first();

        if (!$old || $old->revoked || $old->expires_at->isPast()) {
            return response(['message' => 'Invalid refresh token'], 401);
        }

        $user = $old->user;
        $old->update(['revoked' => true]);
        $user->tokens()->delete();

        $accessToken  = $user->createToken('access')->plainTextToken;
        $refreshToken = RefreshToken::create([
            'user_id'    => $user->id,
            'token'      => Str::random(64),
            'expires_at' => now()->addDays(30),
        ]);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken->token,
        ];
    }
}
