<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A redaction of a law, identified by its date of last change.
 *
 * Rows are append-only: a new ДВ publication opens a version rather than
 * editing one. Everything derived — nodes, references, exports — hangs off the
 * version it was built from, so "what did this say on that date" stays a
 * question the data can answer.
 */
class LawVersion extends Model
{
    protected $fillable = [
        'law_id',
        'changed_at',
        'dv',
        'publ_year',
        'valid_from',
        'valid_to',
        'content_structure',
        'content_text',
        'source_hash',
        'fetched_at',
        'processed_at',
    ];

    /**
     * Raw APIS payloads are megabytes per version and must never reach the
     * public JSON endpoint. Internal code reads them as properties, which
     * $hidden does not affect.
     *
     * @var list<string>
     */
    protected $hidden = [
        'content_structure',
        'content_text',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'date',
            'dv' => 'integer',
            'publ_year' => 'integer',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'content_structure' => 'array',
            'content_text' => 'array',
            'fetched_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function law(): BelongsTo
    {
        return $this->belongsTo(Law::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(LawNode::class, 'law_version_id');
    }

    /**
     * How the redaction is named in citation: "ДВ бр. 69 от 2026 г.".
     */
    public function label(): string
    {
        if ($this->dv === null) {
            return $this->changed_at?->format('d.m.Y') ?? '';
        }

        return "ДВ бр. {$this->dv} от ".($this->publ_year ?? $this->changed_at?->format('Y')).' г.';
    }

    /**
     * Hash of the payload this version was built from. Two fetches of the same
     * redaction must produce the same value, so a payload that changed without
     * publ_date moving is visible rather than silent.
     *
     * @param  array<array-key, mixed>|null  $structure
     * @param  array<array-key, mixed>|null  $text
     */
    public static function hashPayload(?array $structure, ?array $text): string
    {
        return hash(
            'sha256',
            json_encode($structure, JSON_UNESCAPED_UNICODE).'|'.json_encode($text, JSON_UNESCAPED_UNICODE)
        );
    }
}
