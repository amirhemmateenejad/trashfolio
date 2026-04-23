<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Snippet extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'project_id',
        'folder_id',
        'title',
        'content',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'snippet_tag');
    }

    protected static function booted()
    {
        static::deleting(function ($project) {
            if (!$project->isForceDeleting()) {
                $project->folders()->each->delete();
                $project->snippets()->each->delete();
            }
        });

        static::restoring(function ($project) {
            $project->folders()->onlyTrashed()->each->restore();
            $project->snippets()->onlyTrashed()->each->restore();
        });
    }
}
