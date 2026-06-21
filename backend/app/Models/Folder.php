<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends Model
{
    use SoftDeletes, HasFactory;
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
                $folder->children()->get()->each->delete();
                $folder->snippets()->get()->each->delete();
            }
        });

        static::restoring(function ($folder) {
            $folder->children()->onlyTrashed()->get()->each->restore();
            $folder->snippets()->onlyTrashed()->get()->each->restore();
        });
    }
}
