<?php

namespace App\Services\Otp\drivers;

use App\Contracts\OtpSenderInterface;
use App\Contracts\SmsDriverInterface;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;


class GhasedakDriver implements OtpSenderInterface,SmsDriverInterface
{
    protected Client $client;
    protected string $apiKey;
    protected string $lineNumber;
    protected ?string $groupId;
    public string $otpTemplate;

    public function __construct()
    {
        $this->apiKey = config('sms.drivers.ghasedak.api_key');
        $this->lineNumber = config('sms.drivers.ghasedak.sender');
        $this->groupId = config('sms.drivers.ghasedak.group_id');
        $this->otpTemplate = config('sms.drivers.ghasedak.otp_template');

        $this->client = new Client([
            'base_uri' => config('sms.drivers.ghasedak.base_url'),
            'timeout'  => 10,
        ]);
    }

    public function sendOtp(string $phone, string $code): void
    {
        try {
//            $templateId = config('sms.drivers.ghasedak.otp_template');
//            $data = $this->sendTemplate($phone,$templateId,[$code]);
            $message = 'کد شما '. $code;

            $data = $this->sendSimple($phone,$message);

            Log::info('Ghasedak OTP', $data);

        } catch (\Throwable $e) {
            Log::error('Ghasedak Error: '.$e->getMessage());
        }
    }

    public function addToGroup(string $phone): void
    {
        if (!$this->groupId) {
            return;
        }

        try {
            $this->client->post('contact/group/number/add', [
                'headers' => [
                    'apikey' => $this->apiKey,
                ],
                'form_params' => [
                    'groupid' => $this->groupId,
                    'number'  => $phone,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Ghasedak Group Error: '.$e->getMessage());
        }
    }

    public function sendSimple(string $phone,string $message)
    {
        $response = $this->client->post('sms/send/simple', [
            'headers' => [
                'apikey' => $this->apiKey,
            ],
            'form_params' => [
                'message'  => $message,
                'sender'   => $this->lineNumber,
                'receptor' => $phone,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function sendTemplate(string $phone,string $templateId,array $vars =[])
    {
        $formData = [
            'sender'   => $this->lineNumber,
            'receptor' => $phone,
            'type'=>1,
            'template'=>$templateId,
        ];

        $countPram = 1;
        foreach ($vars as $var){
            $formData['param'.$countPram++] = $var;
        }

        $response = $this->client->post('sms/send/verify', [
            'headers' => [
                'apikey' => $this->apiKey,
            ],
            'form_params' => $formData,
        ]);

        return json_decode($response->getBody(), true);
    }
}
