<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes, HasFactory;
    protected $fillable = [
        'user_id',
        'title',
        'description',
    ];

    /** @return BelongsTo<User, Project> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Folder> */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    /** @return HasMany<Snippet> */
    public function snippets(): HasMany
    {
        return $this->hasMany(Snippet::class);
    }

    protected static function booted()
    {
        static::deleting(function ($project) {
            if (!$project->isForceDeleting()) {
                $project->folders()->get()->each->delete();
                $project->snippets()->get()->each->delete();
            }
        });

        static::restoring(function ($project) {
            $project->folders()->onlyTrashed()->get()->each->restore();
            $project->snippets()->onlyTrashed()->get()->each->restore();
        });
    }
}
