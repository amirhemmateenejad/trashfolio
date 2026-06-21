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
use OpenApi\Attributes as OA;

class OtpController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    /**
     * Send an OTP code to the given mobile number.
     */
    #[OA\Post(
        path: '/auth/login',
        summary: 'Request OTP for login',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['mobile'],
                properties: [new OA\Property(property: 'mobile', type: 'string', example: '+989123456789')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OTP sent'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 429, description: 'Rate limited'),
        ]
    )]
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

    /**
     * Verify OTP code and return access/refresh tokens.
     */
    #[OA\Post(
        path: '/auth/verify',
        summary: 'Verify OTP and authenticate',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['mobile', 'code'],
                properties: [
                    new OA\Property(property: 'mobile', type: 'string'),
                    new OA\Property(property: 'code', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Authenticated, returns tokens and user'),
            new OA\Response(response: 422, description: 'Invalid OTP'),
            new OA\Response(response: 429, description: 'Rate limited'),
        ]
    )]
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
