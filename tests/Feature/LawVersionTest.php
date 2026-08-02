<?php

declare(strict_types=1);

use App\Models\Law;
use App\Models\LawVersion;
use App\Services\LawTreeProcessor;
use App\Services\RecordLawVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->recorder = app(RecordLawVersion::class);
});

function lawPublishedOn(string $publDate, string $startDate, int $dv): Law
{
    return Law::factory()->create([
        'publ_date' => $publDate,
        'start_date' => $startDate,
        'end_date' => null,
        'dv' => $dv,
        'publ_year' => (int) substr($publDate, 0, 4),
        'content_structure' => [['pId' => 1, 'caption' => 'Чл. 1']],
        'content_text' => ['paragraphs' => [['pId' => 1, 'text' => '<p>Чл. 1. Първа редакция.</p>', 'type' => 1]]],
        'content_fetched_at' => now(),
    ]);
}

test('a new date of last change opens a version instead of overwriting', function () {
    // ДВ бр. 69 moved publ_date for 23 laws in a single run. Before versions the
    // superseded text was simply gone from the database.
    $law = lawPublishedOn('2026-06-16', '2026-06-20', 55);

    expect($law->versions()->count())->toBe(1);

    $law->update(['publ_date' => '2026-07-31', 'start_date' => '2026-08-01', 'dv' => 69]);

    $this->recorder->record($law->fresh(), [['pId' => 2, 'caption' => 'Чл. 2']], [
        'paragraphs' => [['pId' => 2, 'text' => '<p>Чл. 2. Втора редакция.</p>', 'type' => 1]],
    ]);

    $law->refresh();
    $versions = $law->versions()->reorder('changed_at')->get();

    expect($versions)->toHaveCount(2)
        ->and($versions[0]->changed_at->toDateString())->toBe('2026-06-16')
        ->and($versions[1]->changed_at->toDateString())->toBe('2026-07-31')
        ->and($law->current_version_id)->toBe($versions[1]->id)
        // The first redaction's text is still there to diff against.
        ->and($versions[0]->content_text['paragraphs'][0]['text'])->toContain('Първа редакция');
});

test('the superseded version stops applying the day the new one takes effect', function () {
    // Not the day it was published: a law published on 31.07 to take effect on
    // 01.10 leaves the previous text in force for two more months.
    $law = lawPublishedOn('2026-06-16', '2026-06-20', 55);

    $law->update(['publ_date' => '2026-07-31', 'start_date' => '2026-10-01', 'dv' => 69]);
    $this->recorder->record($law->fresh(), [['pId' => 2, 'caption' => 'Чл. 2']], ['paragraphs' => []]);

    $versions = $law->fresh()->versions()->reorder('changed_at')->get();

    expect($versions[0]->valid_to->toDateString())->toBe('2026-09-30')
        ->and($versions[1]->valid_from->toDateString())->toBe('2026-10-01')
        ->and($versions[1]->valid_to)->toBeNull();
});

test('refetching the same redaction does not open a second version', function () {
    $law = lawPublishedOn('2026-06-16', '2026-06-20', 55);
    $original = $law->currentVersion;

    $this->recorder->record($law->fresh(), [['pId' => 1, 'caption' => 'Чл. 1']], [
        'paragraphs' => [['pId' => 1, 'text' => '<p>Чл. 1. Първа редакция.</p>', 'type' => 1]],
    ]);

    expect($law->fresh()->versions()->count())->toBe(1)
        ->and($law->fresh()->current_version_id)->toBe($original->id);
});

test('a payload that changes without the date moving is visible in the hash', function () {
    // The one case the dates cannot see. It stays the same version by the
    // definition in use, but the hash moves so it is not silent.
    $law = lawPublishedOn('2026-06-16', '2026-06-20', 55);
    $before = $law->currentVersion->source_hash;

    $this->recorder->record($law->fresh(), [['pId' => 1, 'caption' => 'Чл. 1']], [
        'paragraphs' => [['pId' => 1, 'text' => '<p>Чл. 1. Мълчаливо поправена.</p>', 'type' => 1]],
    ]);

    $law->refresh();

    expect($law->versions()->count())->toBe(1)
        ->and($law->currentVersion->source_hash)->not->toBe($before);
});

test('nodes record which redaction they describe', function () {
    $law = lawPublishedOn('2026-06-16', '2026-06-20', 55);

    app(LawTreeProcessor::class)->process($law);

    expect($law->nodes()->pluck('law_version_id')->unique()->all())
        ->toBe([$law->current_version_id]);
});

test('a law reads its text through the current redaction', function () {
    $law = lawPublishedOn('2026-06-16', '2026-06-20', 55);

    expect($law->content_structure)->toBe([['pId' => 1, 'caption' => 'Чл. 1']])
        ->and($law->content_text['paragraphs'][0]['text'])->toContain('Първа редакция');
});

test('a version is named by the ДВ issue that carried it', function () {
    $law = lawPublishedOn('2026-07-31', '2026-08-01', 69);

    expect($law->currentVersion->label())->toBe('ДВ бр. 69 от 2026 г.');
});

test('a law with no redaction has no text and is not processed', function () {
    Law::factory()->create(['processed_at' => null]);

    $this->artisan('laws:process-trees')
        ->expectsOutput('No laws found to process.')
        ->assertExitCode(0);

    expect(LawVersion::query()->count())->toBe(0);
});
