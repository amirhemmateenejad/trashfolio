<?php

namespace App\Contracts;

interface OtpSenderInterface
{
    public function sendOtp(string $phone, string $code): void;
    public function addToGroup(string $phone): void;
}
