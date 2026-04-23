<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use App\Models\User;
use App\Rules\IranianMobile;
use App\Services\Otp\OtpService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function __construct(
        protected OtpService $otp
    ) {}
    public function login()
    {

        $data = request()->validate([
            'mobile' => ['required','string','max:11',new IranianMobile()],
        ]);

        $key = "otp:" . $data['mobile'];

        if (RateLimiter::tooManyAttempts($key, 1)) {
            abort(429, 'Too many attempts');
        }

        RateLimiter::hit($key, 120);

        $this->otp->requestCode($data['mobile']);

        return response()->json([
            'message' => 'OTP sent',
        ]);
    }

    public function verify()
    {
        $data = request()->validate([
            'mobile' => 'required|string|max:20',
            'code' => 'required|string|max:10',
        ]);

        $key = "otp_verify:" . $data['mobile'];

        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Too many attempts');
        }

        RateLimiter::hit($key, 120);

        $otp = $this->otp->verify($data['mobile'],$data['code']);

        if (!$otp || $otp->isExpired()) {
            return response()->json(['message' => 'Invalid OTP'], 422);
        }

        $otp->update(['used_at' => now()]);

        $user = User::firstOrCreate(
            ['mobile' => request('mobile')],
            ['name' => null]
        );

        $isNew = $user->wasRecentlyCreated;

        $accessToken = $user->createToken('access')->plainTextToken;

        $refreshToken = RefreshToken::query()->create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'is_new' => $isNew,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken->token,
            'user'  => $user,
        ]);
    }
}
