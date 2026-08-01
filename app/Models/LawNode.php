<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LawNode extends Model
{
    protected $fillable = [
        'law_id',
        'parent_id',
        'path',
        'p_id',
        'caption',
        'text_markdown',
        'node_type',
        'type',
        'field_type',
        'has_in_links',
        'sort_order',
        'level',
        'is_orphaned',
    ];

    protected function casts(): array
    {
        return [
            'has_in_links' => 'boolean',
            'is_orphaned' => 'boolean',
        ];
    }

    public function law(): BelongsTo
    {
        return $this->belongsTo(Law::class);
    }

    /**
     * Structural parent. Not derivable from the path: глава/раздел are real
     * ancestors but deliberately absent from their descendants' citation paths.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }
}
