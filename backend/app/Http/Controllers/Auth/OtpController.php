<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\Otp\OtpService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    public function login(LoginRequest $request)
    {
        $mobile = $request->validated('mobile');
        $key    = "otp:{$mobile}";

        if (RateLimiter::tooManyAttempts($key, 1)) {
            abort(429, 'Too many attempts');
        }

        RateLimiter::hit($key, 120);

        $this->otp->requestCode($mobile);

        return ['message' => 'OTP sent'];
    }

    public function verify(VerifyOtpRequest $request)
    {
        $data = $request->validated();
        $key  = "otp_verify:{$data['mobile']}";

        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Too many attempts');
        }

        RateLimiter::hit($key, 120);

        $otp = $this->otp->verify($data['mobile'], $data['code']);

        if (!$otp || $otp->isExpired()) {
            return response(['message' => 'Invalid OTP'], 422);
        }

        $otp->update(['used_at' => now()]);

        $user  = User::firstOrCreate(['mobile' => $data['mobile']], ['name' => null]);
        $isNew = $user->wasRecentlyCreated;

        $accessToken  = $user->createToken('access')->plainTextToken;
        $refreshToken = RefreshToken::create([
            'user_id'    => $user->id,
            'token'      => Str::random(64),
            'expires_at' => now()->addDays(30),
        ]);

        return [
            'is_new'        => $isNew,
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken->token,
            'user'          => new UserResource($user),
        ];
    }
}
