<?php

namespace App\Services\Otp\drivers;

use App\Contracts\OtpSenderInterface;
use App\Contracts\SmsDriverInterface;
use Illuminate\Support\Facades\Log;
use Melipayamak\MelipayamakApi;

class MelipayamkDriver implements OtpSenderInterface,SmsDriverInterface
{
    private MelipayamakApi $client;
    public string $fromNumber;
    public string $groupId;
    public string $otpTemplate;
    public function __construct()
    {
        $this->client = new MelipayamakApi(config('sms.drivers.melipyamak.username'),config("sms.drivers.melipyamak.password"));
        $this->fromNumber = config('sms.drivers.melipyamak.from_number');
        $this->groupId = config('sms.drivers.melipyamak.group_id');
        $this->otpTemplate = config('sms.drivers.melipyamak.otp_template');
    }
    public function sendOtp(string $phone, string $code): void
    {
        try {
//            $data = $this->sendTemplate($phone,$templateId,[$code]);
            $message = 'کد شما '. $code;

            $data = $this->sendSimple($phone,$message);

            Log::info('MELIPAYAMAK OTP', $data);

        } catch (\Throwable $e) {
            Log::error('MELIPAYAMAK Error: '.$e->getMessage());
        }
    }

    public function addToGroup(string $phone): void
    {
        $sms = $this->client->contacts();
        $sms->add(['groupIds'=>$this->groupId,'mobilenumber'=>$phone]);
    }

    public function sendSimple(string $phone, string $message)
    {
        $sms = $this->client->sms();
        return $sms->send($phone,$this->fromNumber,$message,false);
    }

    public function sendTemplate(string $phone, string $templateId, array $vars)
    {
        $sms = $this->client->sms();
        return $sms->sendByBaseNumber($vars,$phone,$templateId);
    }
}
