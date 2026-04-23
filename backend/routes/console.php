<?php

use App\Console\Commands\CleanExpiredOtpsCommand;
use App\Console\Commands\CleanExpiredTokensCommand;
use App\Console\Commands\CleanTrashCommand;

Schedule::command(CleanExpiredOtpsCommand::class)->everySixHours();
Schedule::command(CleanExpiredTokensCommand::class)->everySixHours();
Schedule::command(CleanTrashCommand::class)->dailyAt('12:00');
