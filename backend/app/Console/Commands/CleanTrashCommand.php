<?php

namespace App\Console\Commands;

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CleanTrashCommand extends Command
{
    protected $signature = 'trash:clean';

    protected $description = 'Permanently delete trashed items older than 30 days';

    public function handle(): int
    {
        $days = 30;

        $projects = Project::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays($days))
            ->forceDelete();

        $folders = Folder::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays($days))
            ->forceDelete();

        $snippets = Snippet::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays($days))
            ->forceDelete();

        $this->info("Old trashed items cleaned.");
        return CommandAlias::SUCCESS;
    }
}
