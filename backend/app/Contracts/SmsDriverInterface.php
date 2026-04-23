<?php

namespace App\Contracts;

interface SmsDriverInterface
{
    public function sendSimple(string $phone,string $message);

    public function addToGroup(string $phone);

    public function sendTemplate(string $phone,string $templateId,array $vars);
}
