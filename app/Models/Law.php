<?php

namespace App\Models;

use Database\Factories\LawFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Law extends Model
{
    /** @use HasFactory<LawFactory> */
    use HasFactory;

    protected $fillable = [
        'current_version_id',
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
            'content_fetched_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LawVersion::class)->orderByDesc('changed_at');
    }

    /**
     * The redaction currently in the corpus. Held as a column rather than
     * derived from max(changed_at) so a version published ahead of the one in
     * force cannot silently become "current" the moment it is fetched.
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(LawVersion::class, 'current_version_id');
    }

    /**
     * The payload lives on the version, not the law — a law has no text of its
     * own, only the text of whichever redaction you asked for. Reading through
     * the current version keeps the common case ("what does it say now")
     * spelled the way it reads.
     */
    protected function contentStructure(): Attribute
    {
        return Attribute::make(get: fn (): ?array => $this->currentVersion?->content_structure);
    }

    protected function contentText(): Attribute
    {
        return Attribute::make(get: fn (): ?array => $this->currentVersion?->content_text);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(LawNode::class);
    }

    /**
     * Citations made by this law's provisions, wherever they point.
     */
    public function references(): HasMany
    {
        return $this->hasMany(LegalReference::class);
    }
}
