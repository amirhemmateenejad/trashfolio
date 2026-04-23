<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function refresh()
    {
        request()->validate([
            'refresh_token' => 'required|string'
        ]);

        $old = RefreshToken::query()->where('token', request('refresh_token'))->first();

        if (!$old || $old->revoked || $old->expires_at->isPast()) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $user = $old->user;

        // revoke old refresh token
        $old->update(['revoked' => true]);

        // revoke all old access tokens
        $user->tokens()->delete();

        // create new tokens
        $accessToken = $user->createToken('access')->plainTextToken;

        $newRefresh = RefreshToken::query()->create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(30),
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefresh->token,
        ];
    }
}
