<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'title',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public function snippets(): HasMany
    {
        return $this->hasMany(Snippet::class);
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
