<?php

namespace App\Console\Commands;

use App\Models\OtpCode;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

#[Signature('app:clean-expired-otps')]
#[Description('Command description')]
class CleanExpiredOtpsCommand extends Command
{
    protected $signature = 'otp:clean';
    protected $description = 'Delete expired OTP codes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = OtpCode::where('expires_at', '<=', now()->subMinutes(5))->delete();

        $this->info("Deleted {$count} expired OTP codes.");
        return CommandAlias::SUCCESS;

    }
}
