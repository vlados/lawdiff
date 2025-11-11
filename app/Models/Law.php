<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Law extends Model
{
    /** @use HasFactory<\Database\Factories\LawFactory> */
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'db_index',
        'caption',
        'func',
        'type',
        'base',
        'is_actual',
        'publ_date',
        'start_date',
        'end_date',
        'act_date',
        'publ_year',
        'is_connected',
        'has_content',
        'code',
        'dv',
        'original_id',
        'version',
        'celex',
        'doc_lead',
        'seria',
        'content_structure',
        'content_text',
        'content_fetched_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'unique_id' => 'integer',
            'db_index' => 'integer',
            'func' => 'integer',
            'type' => 'integer',
            'is_actual' => 'boolean',
            'publ_date' => 'datetime',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'act_date' => 'datetime',
            'publ_year' => 'integer',
            'is_connected' => 'boolean',
            'has_content' => 'boolean',
            'dv' => 'integer',
            'original_id' => 'integer',
            'content_structure' => 'array',
            'content_text' => 'array',
            'content_fetched_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(LawNode::class);
    }
}
