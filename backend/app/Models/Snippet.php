<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Snippet extends Model
{
    use SoftDeletes, Searchable, HasFactory;
    protected $fillable = [
        'project_id',
        'folder_id',
        'title',
        'content',
        'language',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Snippet $snippet) {
            $snippet->tags()->detach();
        });
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'snippet_tag');
    }

    public function toSearchableArray(): array
    {
        // همه روابط لازم را لود کن، تا indexing کامل باشد
        $this->loadMissing([
            'tags',
            'project',
            'folder',
            'folder.parent',
            'folder.parent.project'
        ]);

        return [
            'id'         => $this->id,
            'user_id'    => $this->owner_user_id,
            'project_id' => $this->project_id,
            'folder_id'  => $this->folder_id,
            'title'      => $this->title,
            'content'    => $this->content,
            'language'   => $this->language,
            'tags'       => $this->tags->pluck('name')->toArray(),
            'created_at' => optional($this->created_at)->timestamp,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->deleted_at === null;
    }

    public function getOwnerUserIdAttribute(): ?int
    {
        if ($this->project_id) {
            $project = $this->relationLoaded('project')
                ? $this->project
                : $this->project()->withTrashed()->first();

            if ($project) {
                return $project->user_id;
            }
        }

        if ($this->folder_id) {
            $folder = $this->relationLoaded('folder')
                ? $this->folder
                : $this->folder()->withTrashed()->first();

            if ($folder) {
                return $this->resolveFolderOwner($folder);
            }
        }

        return null;
    }

    private function resolveFolderOwner($folder): ?int
    {
        $project = $folder->relationLoaded('project')
            ? $folder->project
            : $folder->project()->withTrashed()->first();

        if ($project) {
            return $project->user_id;
        }

        $parent = $folder->relationLoaded('parent')
            ? $folder->parent
            : $folder->parent()->withTrashed()->first();

        if ($parent) {
            return $this->resolveFolderOwner($parent);
        }

        return null;
    }
}
