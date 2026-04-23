<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Rules\IranianMobile;

class OtpController extends Controller
{
    public function login()
    {
        request()->validate([
            'mobile' => ['required','string','max:11',new IranianMobile()],
        ]);

        OtpCode::query()->create([
            'mobile' => request('mobile'),
            'code' => rand(100000, 999999),
            'expires_at' => now()->addMinutes(3),
        ]);

        return response()->json([
            'message' => 'OTP sent',
        ]);
    }

    public function verify()
    {
        request()->validate([
            'mobile' => 'required|string|max:20',
            'code' => 'required|string|max:10',
        ]);

        $otp = OtpCode::query()->where('mobile', request('mobile'))
            ->where('code', request('code'))
            ->whereNull('used_at')
            ->first();

        if (!$otp || $otp->isExpired()) {
            return response()->json(['message' => 'Invalid OTP'], 422);
        }

        $otp->update(['used_at' => now()]);

        $user = User::firstOrCreate(
            ['mobile' => request('mobile')],
            ['name' => 'User ' . rand(1000, 9999)]
        );

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ]);
    }
}
