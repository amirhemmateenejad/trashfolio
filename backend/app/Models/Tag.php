<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'color',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function (Tag $tag) {
            if ($tag->isDirty('name') && !$tag->isDirty('slug')) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /** @return BelongsTo<User, Tag> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Snippet> */
    public function snippets(): BelongsToMany
    {
        return $this->belongsToMany(Snippet::class, 'snippet_tag');
    }
}
