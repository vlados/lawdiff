<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Law;
use App\Models\LawVersion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Opens the redaction a freshly fetched payload belongs to.
 *
 * The fetch used to overwrite the law in place, so the superseded text was gone
 * the moment a ДВ issue amended it — ДВ бр. 69 alone rewrote 23 laws in a
 * single run. A version is identified by the law's date of last change, so a
 * payload arriving under a new publ_date opens a row instead of replacing one.
 */
class RecordLawVersion
{
    /**
     * @param  array<array-key, mixed>|null  $structure
     * @param  array<array-key, mixed>|null  $text
     */
    public function record(Law $law, ?array $structure, ?array $text): LawVersion
    {
        return DB::transaction(function () use ($law, $structure, $text): LawVersion {
            $changedAt = ($law->publ_date ?? now())->toDateString();
            $validFrom = $law->start_date?->toDateString();

            $existing = $law->versions()->where('changed_at', $changedAt)->first();

            if ($existing !== null) {
                return $this->refresh($law, $existing, $structure, $text);
            }

            $this->closePrevious($law, $validFrom ?? $changedAt);

            $version = LawVersion::create([
                'law_id' => $law->id,
                'changed_at' => $changedAt,
                'dv' => $law->dv,
                'publ_year' => $law->publ_year,
                'valid_from' => $validFrom,
                'valid_to' => $law->end_date?->toDateString(),
                'content_structure' => $structure,
                'content_text' => $text,
                'source_hash' => LawVersion::hashPayload($structure, $text),
                'fetched_at' => now(),
            ]);

            $law->forceFill(['current_version_id' => $version->id])->save();
            $law->setRelation('currentVersion', $version);

            return $version;
        });
    }

    /**
     * A payload arriving under a date that already has a version is the same
     * redaction by the definition in use, so it updates that row rather than
     * opening another. source_hash still moves when the text differs, which is
     * the only signal that APIS changed a redaction without renumbering it.
     *
     * @param  array<array-key, mixed>|null  $structure
     * @param  array<array-key, mixed>|null  $text
     */
    private function refresh(Law $law, LawVersion $version, ?array $structure, ?array $text): LawVersion
    {
        $version->update([
            'content_structure' => $structure,
            'content_text' => $text,
            'source_hash' => LawVersion::hashPayload($structure, $text),
            'fetched_at' => now(),
            'dv' => $law->dv,
            'publ_year' => $law->publ_year,
            'valid_from' => $law->start_date?->toDateString(),
            'valid_to' => $law->end_date?->toDateString(),
        ]);

        $law->forceFill(['current_version_id' => $version->id])->save();
        $law->setRelation('currentVersion', $version);

        return $version;
    }

    /**
     * The outgoing redaction stops applying the day before the new one starts,
     * not the day it was published: a law published on 31.07 to take effect on
     * 01.10 leaves the old text in force for two more months.
     */
    private function closePrevious(Law $law, string $nextValidFrom): void
    {
        $previous = $law->versions()->whereNull('valid_to')->orderByDesc('changed_at')->first();

        if ($previous === null) {
            return;
        }

        $previous->update([
            'valid_to' => Carbon::parse($nextValidFrom)->subDay()->toDateString(),
        ]);
    }
}
