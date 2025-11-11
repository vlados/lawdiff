<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LawNode extends Model
{
    protected $fillable = [
        'law_id',
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
}
