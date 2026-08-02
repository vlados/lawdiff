<?php

declare(strict_types=1);

use App\Models\Law;
use App\Models\LegalReference;
use App\Services\LawTreeProcessor;
use App\Services\RecordLawVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<int, array{pId: int, caption: string}>  $articles
 */
function lawWithArticles(array $articles): Law
{
    return Law::factory()->create([
        'content_structure' => array_map(
            fn (array $article): array => ['pId' => $article['pId'], 'caption' => $article['caption']],
            $articles
        ),
        'content_text' => [
            'paragraphs' => array_map(
                fn (array $article): array => [
                    'pId' => $article['pId'],
                    'text' => $article['text'],
                    'type' => 1,
                ],
                $articles
            ),
        ],
        'content_fetched_at' => now(),
    ]);
}

test('resolves a relative reference to the provision it names', function () {
    // The exact case the ИЕПС keyword export could not reach: "регистъра по
    // ал. 5" names a provision that shares none of the topic's vocabulary, so
    // no amount of keyword or similarity matching pulls it in. The citation
    // does.
    $law = lawWithArticles([
        [
            'pId' => 1,
            'caption' => 'Чл. 80а',
            'text' => '<p>Чл. 80а. Изисквания:</p>'
                .'<p>(1) Водачът се вписва в регистъра по ал. 5.</p>'
                .'<p>(5) Регистърът се води от общината.</p>',
        ],
    ]);

    app(LawTreeProcessor::class)->process($law);

    $reference = LegalReference::query()->firstOrFail();
    $source = $law->nodes()->where('path', 'ЧЛ80А/АЛ1')->firstOrFail();
    $target = $law->nodes()->where('path', 'ЧЛ80А/АЛ5')->firstOrFail();

    expect($reference->source_node_id)->toBe($source->id)
        ->and($reference->target_node_id)->toBe($target->id)
        ->and($reference->target_path)->toBe('ЧЛ80А/АЛ5')
        ->and($reference->citation_text)->toBe('ал. 5')
        ->and($reference->resolution_status)->toBe(LegalReference::STATUS_RESOLVED)
        ->and($reference->target_law_id)->toBe($law->id);
});

test('resolves a full citation across articles', function () {
    $law = lawWithArticles([
        ['pId' => 1, 'caption' => 'Чл. 80а', 'text' => '<p>Чл. 80а. Водачът носи каска.</p>'],
        [
            'pId' => 2,
            'caption' => 'Чл. 183а',
            'text' => '<p>Чл. 183а. Наказва се водач, който наруши чл. 80а.</p>',
        ],
    ]);

    app(LawTreeProcessor::class)->process($law);

    $sanction = $law->nodes()->where('path', 'ЧЛ183А')->firstOrFail();
    $duty = $law->nodes()->where('path', 'ЧЛ80А')->firstOrFail();

    $reference = LegalReference::query()->where('source_node_id', $sanction->id)->firstOrFail();

    expect($reference->target_node_id)->toBe($duty->id)
        ->and($reference->resolution_status)->toBe(LegalReference::STATUS_RESOLVED);
});

test('an article does not reference itself through its own label', function () {
    // "Чл. 80а." opens the article's own text and scans as a citation of the
    // node doing the citing.
    $law = lawWithArticles([
        ['pId' => 1, 'caption' => 'Чл. 80а', 'text' => '<p>Чл. 80а. Водачът носи каска.</p>'],
    ]);

    app(LawTreeProcessor::class)->process($law);

    expect(LegalReference::query()->count())->toBe(0);
});

test('records an external citation without inventing a local target', function () {
    $law = lawWithArticles([
        [
            'pId' => 1,
            'caption' => 'Чл. 12',
            'text' => '<p>Чл. 12. Прилага се редът по чл. 5 от Закона за здравето.</p>',
        ],
    ]);

    app(LawTreeProcessor::class)->process($law);

    $reference = LegalReference::query()->firstOrFail();

    expect($reference->resolution_status)->toBe(LegalReference::STATUS_UNRESOLVED_EXTERNAL)
        ->and($reference->target_act_name)->toBe('Закона за здравето')
        ->and($reference->target_node_id)->toBeNull()
        ->and($reference->target_law_id)->toBeNull()
        ->and($reference->target_path)->toBe('ЧЛ5');
});

test('a citation of a provision the law does not have is kept as unresolved', function () {
    // Silence here would hide the corpus telling on itself: either the target
    // was renumbered, or the parser still misreads the marker that would have
    // produced it.
    $law = lawWithArticles([
        ['pId' => 1, 'caption' => 'Чл. 12', 'text' => '<p>Чл. 12. Прилага се чл. 999.</p>'],
    ]);

    app(LawTreeProcessor::class)->process($law);

    $reference = LegalReference::query()->firstOrFail();

    expect($reference->resolution_status)->toBe(LegalReference::STATUS_UNRESOLVED_INTERNAL)
        ->and($reference->target_path)->toBe('ЧЛ999')
        ->and($reference->target_node_id)->toBeNull();
});

test('references are rebuilt with the nodes rather than left stale', function () {
    $law = lawWithArticles([
        ['pId' => 1, 'caption' => 'Чл. 12', 'text' => '<p>Чл. 12. Прилага се чл. 999.</p>'],
    ]);

    $processor = app(LawTreeProcessor::class);
    $processor->process($law);

    expect(LegalReference::query()->count())->toBe(1);

    $law->update(['publ_date' => now()->addDay()]);
    app(RecordLawVersion::class)->record(
        $law->fresh(),
        [['pId' => 1, 'caption' => 'Чл. 12']],
        ['paragraphs' => [['pId' => 1, 'text' => '<p>Чл. 12. Няма препратки.</p>', 'type' => 1]]]
    );

    $processor->process($law->fresh());

    expect(LegalReference::query()->count())->toBe(0);
});

test('deleting a law takes its references with it', function () {
    $law = lawWithArticles([
        ['pId' => 1, 'caption' => 'Чл. 12', 'text' => '<p>Чл. 12. Прилага се чл. 999.</p>'],
    ]);

    app(LawTreeProcessor::class)->process($law);
    expect(LegalReference::query()->count())->toBe(1);

    $law->delete();

    expect(LegalReference::query()->count())->toBe(0);
});

test('the command rebuilds the graph without reprocessing nodes', function () {
    $law = lawWithArticles([
        ['pId' => 1, 'caption' => 'Чл. 80а', 'text' => '<p>Чл. 80а. Водачът носи каска.</p>'],
        ['pId' => 2, 'caption' => 'Чл. 183а', 'text' => '<p>Чл. 183а. Наказва се нарушение на чл. 80а.</p>'],
    ]);

    app(LawTreeProcessor::class)->process($law);
    $law->update(['processed_at' => now()]);

    $nodeIds = $law->nodes()->orderBy('sort_order')->pluck('id')->all();
    LegalReference::query()->delete();

    $this->artisan('laws:extract-references')->assertExitCode(0);

    expect(LegalReference::query()->count())->toBe(1)
        ->and($law->nodes()->orderBy('sort_order')->pluck('id')->all())->toBe($nodeIds);
});

test('a § citation resolves inside its own transitional block', function () {
    // § numbering restarts per block, so a bare "§ 6" means the § of the block
    // doing the citing. Resolving it at the law root would both miss the target
    // and turn the paragraph's own label into a phantom reference.
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'ДОПЪЛНИТЕЛНИ РАЗПОРЕДБИ', 'children' => [
                ['pId' => 2, 'caption' => '§ 6'],
                ['pId' => 3, 'caption' => '§ 7'],
            ]],
        ],
        'content_text' => ['paragraphs' => [
            ['pId' => 2, 'text' => '<p>§ 6. По смисъла на този закон.</p>', 'type' => 1],
            ['pId' => 3, 'text' => '<p>§ 7. Прилага се определението по § 6.</p>', 'type' => 1],
        ]],
        'content_fetched_at' => now(),
    ]);

    app(LawTreeProcessor::class)->process($law);

    $references = LegalReference::query()->with('sourceNode')->get();

    expect($references)->toHaveCount(1)
        ->and($references[0]->sourceNode->path)->toBe('ДОП/§7')
        ->and($references[0]->target_path)->toBe('ДОП/§6')
        ->and($references[0]->resolution_status)->toBe(LegalReference::STATUS_RESOLVED);
});
