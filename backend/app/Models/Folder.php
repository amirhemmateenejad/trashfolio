<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'project_id',
        'parent_id',
        'title',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function snippets(): HasMany
    {
        return $this->hasMany(Snippet::class);
    }

    protected static function booted()
    {
        static::deleting(function ($folder) {
            if (!$folder->isForceDeleting()) {
                $folder->children()->each->delete();
                $folder->snippets()->each->delete();
            }
        });

        static::restoring(function ($folder) {
            $folder->children()->onlyTrashed()->each->restore();
            $folder->snippets()->onlyTrashed()->each->restore();
        });
    }
}
