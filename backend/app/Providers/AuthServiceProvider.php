<?php

namespace App\Providers;

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use App\Models\Tag;
use App\Policies\FolderPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\SnippetPolicy;
use App\Policies\TagPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;


class AuthServiceProvider extends ServiceProvider
{

    protected $policies = [
        Project::class => ProjectPolicy::class,
        Folder::class => FolderPolicy::class,
        Snippet::class => SnippetPolicy::class,
        Tag::class => TagPolicy::class,
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
