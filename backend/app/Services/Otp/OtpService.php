<?php

namespace App\Services\Otp;

use App\Contracts\OtpSenderInterface;
use App\Models\OtpCode;

class OtpService
{
    public function __construct(
        protected OtpSenderInterface $sender
    ) {}

    public function requestCode(string $phone): OtpCode
    {
        $code = rand(1000, 9999);

        $otp = OtpCode::create([
            'mobile' => $phone,
            'code'  => $code,
            'expires_at' => now()->addMinutes(2),
        ]);

        $this->sender->sendOtp($phone, $code);

        return $otp;
    }

    public function verify(string $phone, string $code): ?OtpCode
    {
        return OtpCode::where('mobile', $phone)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function addToGroup(string $phone): void
    {
        $this->sender->addToGroup($phone);
    }
}
