<?php

namespace App\Console\Commands;

use App\Models\RefreshToken;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

#[Signature('app:clean-expired-tokens')]
#[Description('Command description')]
class CleanExpiredTokensCommand extends Command
{
    protected $signature = 'tokens:clean';
    protected $description = 'Delete expired refresh tokens';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = RefreshToken::query()->where('expires_at', '<=', now()->subMinutes(5))->delete();

        $this->info("Deleted {$count} expired refresh tokens.");
        return CommandAlias::SUCCESS;
    }
}
