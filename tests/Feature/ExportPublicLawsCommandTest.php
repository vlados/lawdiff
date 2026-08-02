<?php

declare(strict_types=1);

use App\Models\Law;
use App\Models\LawNode;
use App\Services\LawTreeProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->outputDir = storage_path('app/test-export-'.uniqid());
});

afterEach(function (): void {
    if (isset($this->outputDir) && File::isDirectory($this->outputDir)) {
        File::deleteDirectory($this->outputDir);
    }
});

test('exports a processed law to a slug-named JSON file', function () {
    $law = Law::factory()->create([
        'caption' => 'ЗАКОН за движението по пътищата',
        'code' => '4321',
        'processed_at' => now(),
        'content_fetched_at' => now(),
    ]);

    LawNode::create([
        'law_id' => $law->id,
        'path' => 'ЧЛ1',
        'p_id' => 1,
        'caption' => 'Чл. 1',
        'text_markdown' => 'Hello world',
        'node_type' => 'article',
        'sort_order' => 1,
        'level' => 0,
    ]);

    $this->artisan('laws:export-public', ['--output' => $this->outputDir])
        ->assertExitCode(0);

    $lawFiles = File::files($this->outputDir.'/laws');
    expect($lawFiles)->toHaveCount(1);

    $filename = $lawFiles[0]->getFilename();
    expect($filename)->toEndWith('.json')
        ->and($filename)->not->toBe("{$law->unique_id}.json");

    $payload = json_decode(File::get($lawFiles[0]->getPathname()), true, flags: JSON_THROW_ON_ERROR);
    expect($payload['unique_id'])->toBe($law->unique_id)
        ->and($payload['caption'])->toBe('ЗАКОН за движението по пътищата')
        ->and($payload['nodes'])->toHaveCount(1)
        ->and($payload['nodes'][0]['text_markdown'])->toBe('Hello world')
        ->and($payload['nodes'][0]['children'])->toBe([]);

    expect(File::exists($this->outputDir.'/index.json'))->toBeTrue()
        ->and(File::exists($this->outputDir.'/index.csv'))->toBeTrue()
        ->and(File::exists($this->outputDir.'/README.md'))->toBeTrue();

    $index = json_decode(File::get($this->outputDir.'/index.json'), true, flags: JSON_THROW_ON_ERROR);
    expect($index['count'])->toBe(1)
        ->and($index['laws'][0]['file'])->toBe('laws/'.pathinfo($filename, PATHINFO_FILENAME).'.json');
});

test('law payload key order is stable', function () {
    // 423 files are already committed with this exact order. Reordering keys would
    // churn the entire dataset in a single daily commit for no semantic change.
    // Appending is the one safe move, which is why `references` sits last.
    $law = Law::factory()->create([
        'caption' => 'ЗАКОН за реда на ключовете',
        'processed_at' => now(),
    ]);

    LawNode::create([
        'law_id' => $law->id,
        'path' => 'ЧЛ1',
        'caption' => 'Чл. 1',
        'sort_order' => 1,
    ]);

    $this->artisan('laws:export-public', ['--output' => $this->outputDir])
        ->assertExitCode(0);

    $payload = json_decode(
        File::get(File::files($this->outputDir.'/laws')[0]->getPathname()),
        true,
        flags: JSON_THROW_ON_ERROR
    );

    expect(array_keys($payload))->toBe([
        'unique_id', 'slug', 'code', 'caption', 'type', 'func', 'base', 'is_actual',
        'publ_year', 'publ_date', 'start_date', 'end_date', 'act_date', 'dv', 'version',
        'celex', 'doc_lead', 'seria', 'source', 'fetched_at', 'processed_at', 'versions',
        'nodes', 'references',
    ]);
});

test('nests child nodes under their parent path', function () {
    $law = Law::factory()->create([
        'caption' => 'ЗАКОН за тестово вложение',
        'processed_at' => now(),
    ]);

    $rows = [
        ['ЧЛ80А', 0, 1, 'Чл. 80а.'],
        ['ЧЛ80А/АЛ1', 1, 2, 'Алинея 1'],
        ['ЧЛ80А/АЛ1/Т1', 2, 3, 'Точка 1'],
        ['ЧЛ80А/АЛ1/Т2', 2, 4, 'Точка 2'],
        ['ЧЛ80А/АЛ2', 1, 5, 'Алинея 2'],
        ['ЧЛ81', 0, 6, 'Чл. 81.'],
    ];

    foreach ($rows as [$path, $level, $sort, $caption]) {
        LawNode::create([
            'law_id' => $law->id,
            'path' => $path,
            'caption' => $caption,
            'level' => $level,
            'sort_order' => $sort,
        ]);
    }

    $this->artisan('laws:export-public', ['--output' => $this->outputDir])
        ->assertExitCode(0);

    $payload = json_decode(
        File::get(File::files($this->outputDir.'/laws')[0]->getPathname()),
        true,
        flags: JSON_THROW_ON_ERROR
    );

    expect($payload['nodes'])->toHaveCount(2);

    $art80 = collect($payload['nodes'])->firstWhere('path', 'ЧЛ80А');
    expect($art80['children'])->toHaveCount(2);

    $al1 = collect($art80['children'])->firstWhere('path', 'ЧЛ80А/АЛ1');
    expect($al1['children'])->toHaveCount(2)
        ->and($al1['children'][0]['path'])->toBe('ЧЛ80А/АЛ1/Т1')
        ->and($al1['children'][1]['path'])->toBe('ЧЛ80А/АЛ1/Т2');

    $art81 = collect($payload['nodes'])->firstWhere('path', 'ЧЛ81');
    expect($art81['children'])->toBe([]);
});

test('skips laws that have not been processed', function () {
    Law::factory()->create([
        'caption' => 'Unprocessed law',
        'processed_at' => null,
    ]);

    $this->artisan('laws:export-public', ['--output' => $this->outputDir])
        ->assertExitCode(0);

    expect(File::isDirectory($this->outputDir.'/laws'))->toBeFalse();
});

test('disambiguates slug collisions', function () {
    $captions = ['ЗАКОН за нещо', 'ЗАКОН за нещо'];

    foreach ($captions as $caption) {
        Law::factory()->create([
            'caption' => $caption,
            'processed_at' => now(),
        ]);
    }

    $this->artisan('laws:export-public', ['--output' => $this->outputDir])
        ->assertExitCode(0);

    $files = collect(File::files($this->outputDir.'/laws'))
        ->map(fn ($f) => $f->getFilename())
        ->sort()
        ->values();

    expect($files)->toHaveCount(2);

    $stems = $files->map(fn (string $name): string => pathinfo($name, PATHINFO_FILENAME));
    expect($stems->unique()->count())->toBe(2);
});

test('reports pipeline counts and warns when laws are missing from export', function () {
    Law::factory()->create([
        'caption' => 'ЗАКОН A',
        'has_content' => true,
        'content_fetched_at' => now(),
        'processed_at' => now(),
    ]);

    Law::factory()->create([
        'caption' => 'ЗАКОН B',
        'has_content' => true,
        'content_fetched_at' => now(),
        'processed_at' => null,
    ]);

    Law::factory()->create([
        'caption' => 'ЗАКОН C',
        'has_content' => false,
        'content_fetched_at' => null,
        'processed_at' => null,
    ]);

    $this->artisan('laws:export-public', ['--output' => $this->outputDir])
        ->expectsOutputToContain('Law pipeline status:')
        ->expectsOutputToContain('Laws in database')
        ->expectsOutputToContain('Eligible for export (processed)')
        ->expectsOutputToContain('Laws exported (this run)')
        ->expectsOutputToContain('Files on disk in laws/')
        ->expectsOutputToContain('2 law(s) in the database were not exported')
        ->expectsOutputToContain('still need content fetched')
        ->expectsOutputToContain('have content but are not processed yet')
        ->assertExitCode(0);

    expect(File::files($this->outputDir.'/laws'))->toHaveCount(1);
});

test('does not warn about missing exports when scoped by law-id', function () {
    Law::factory()->create([
        'caption' => 'ЗАКОН processed',
        'processed_at' => now(),
    ]);

    $unprocessed = Law::factory()->create([
        'caption' => 'ЗАКОН unprocessed',
        'processed_at' => null,
        'content_fetched_at' => null,
        'has_content' => false,
    ]);

    Law::factory()->create([
        'caption' => 'ЗАКОН other unprocessed',
        'processed_at' => null,
        'content_fetched_at' => null,
        'has_content' => false,
    ]);

    $this->artisan('laws:export-public', [
        '--output' => $this->outputDir,
        '--law-id' => $unprocessed->id,
    ])
        ->doesntExpectOutputToContain('were not exported because they are not yet processed')
        ->assertExitCode(0);
});

test('prune refuses a mass deletion and fails the command', function () {
    // A shrunken corpus (partial fetch, rebuilt database) must not translate into
    // deleting most of the published dataset from an unattended job.
    $law = Law::factory()->create([
        'caption' => 'ЗАКОН единствен оцелял',
        'processed_at' => now(),
    ]);
    LawNode::create([
        'law_id' => $law->id,
        'path' => 'ЧЛ1',
        'caption' => 'Чл. 1',
        'sort_order' => 1,
    ]);

    File::ensureDirectoryExists($this->outputDir.'/laws');
    foreach (range(1, 30) as $i) {
        File::put($this->outputDir."/laws/stale-{$i}.json", '{}');
    }

    $this->artisan('laws:export-public', ['--output' => $this->outputDir, '--prune' => true])
        ->expectsOutputToContain('Prune aborted')
        ->assertExitCode(1);

    expect(File::exists($this->outputDir.'/laws/stale-1.json'))->toBeTrue()
        ->and(File::exists($this->outputDir.'/laws/stale-30.json'))->toBeTrue();
});

test('prune removes stale law files', function () {
    $law = Law::factory()->create([
        'caption' => 'ЗАКОН за тест',
        'processed_at' => now(),
    ]);
    LawNode::create([
        'law_id' => $law->id,
        'path' => 'ЧЛ1',
        'caption' => 'Чл. 1',
        'sort_order' => 1,
    ]);

    File::ensureDirectoryExists($this->outputDir.'/laws');
    File::put($this->outputDir.'/laws/old-removed-law.json', '{}');

    $this->artisan('laws:export-public', ['--output' => $this->outputDir, '--prune' => true])
        ->assertExitCode(0);

    expect(File::exists($this->outputDir.'/laws/old-removed-law.json'))->toBeFalse();
});

test('the exported tree nests articles under their chapter', function () {
    // Chapters are absent from their descendants' citation paths, so deriving
    // the parent by stripping "/segment" would flatten every law into a list of
    // roots. Nesting follows parent_id instead.
    $law = Law::factory()->create([
        'caption' => 'ЗАКОН за пробата',
        'processed_at' => now(),
        'content_fetched_at' => now(),
    ]);

    $chapter = LawNode::create([
        'law_id' => $law->id,
        'path' => 'ГЛАВА_ПЪРВА',
        'caption' => 'Глава първа',
        'node_type' => 'chapter',
        'sort_order' => 1,
        'level' => 0,
    ]);

    $article = LawNode::create([
        'law_id' => $law->id,
        'parent_id' => $chapter->id,
        'path' => 'ЧЛ1',
        'caption' => 'Чл. 1',
        'node_type' => 'article',
        'sort_order' => 2,
        'level' => 1,
    ]);

    LawNode::create([
        'law_id' => $law->id,
        'parent_id' => $article->id,
        'path' => 'ЧЛ1/АЛ1',
        'text_markdown' => 'Алинея първа',
        'node_type' => 'paragraph',
        'sort_order' => 3,
        'level' => 2,
    ]);

    $this->artisan('laws:export-public', ['--output' => $this->outputDir])
        ->assertExitCode(0);

    $payload = json_decode(
        File::get(File::files($this->outputDir.'/laws')[0]->getPathname()),
        true,
        flags: JSON_THROW_ON_ERROR
    );

    expect($payload['nodes'])->toHaveCount(1)
        ->and($payload['nodes'][0]['path'])->toBe('ГЛАВА_ПЪРВА')
        ->and($payload['nodes'][0]['parent_path'])->toBeNull()
        ->and($payload['nodes'][0]['children'])->toHaveCount(1)
        ->and($payload['nodes'][0]['children'][0]['path'])->toBe('ЧЛ1')
        ->and($payload['nodes'][0]['children'][0]['parent_path'])->toBe('ГЛАВА_ПЪРВА')
        ->and($payload['nodes'][0]['children'][0]['children'][0]['path'])->toBe('ЧЛ1/АЛ1')
        ->and($payload['nodes'][0]['children'][0]['children'][0]['parent_path'])->toBe('ЧЛ1');
});

test('the export carries the citation graph keyed by path', function () {
    // Node ids are rebuilt on every reprocess, so an exported edge has to be
    // addressed by path or it means nothing to a consumer of the dataset.
    $law = Law::factory()->create([
        'caption' => 'ЗАКОН за пробата',
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 80а'],
            ['pId' => 2, 'caption' => 'Чл. 183а'],
        ],
        'content_text' => ['paragraphs' => [
            ['pId' => 1, 'text' => '<p>Чл. 80а. Водачът носи каска.</p>', 'type' => 1],
            ['pId' => 2, 'text' => '<p>Чл. 183а. Наказва се нарушение на чл. 80а.</p>', 'type' => 1],
        ]],
        'content_fetched_at' => now(),
        'processed_at' => now(),
    ]);

    app(LawTreeProcessor::class)->process($law);

    $this->artisan('laws:export-public', ['--output' => $this->outputDir])
        ->assertExitCode(0);

    $payload = json_decode(
        File::get(File::files($this->outputDir.'/laws')[0]->getPathname()),
        true,
        flags: JSON_THROW_ON_ERROR
    );

    expect($payload['references'])->toHaveCount(1)
        ->and($payload['references'][0])->toMatchArray([
            'source_path' => 'ЧЛ183А',
            'target_path' => 'ЧЛ80А',
            'citation' => 'чл. 80а',
            'relation' => 'refers_to',
            'status' => 'resolved',
        ]);
});

test('the export names the redaction its text is', function () {
    $law = Law::factory()->create([
        'caption' => 'ЗАКОН за движението по пътищата',
        'publ_date' => '2026-06-16',
        'start_date' => '2026-06-20',
        'end_date' => null,
        'dv' => 55,
        'publ_year' => 2026,
        'content_structure' => [['pId' => 1, 'caption' => 'Чл. 1']],
        'content_text' => ['paragraphs' => [['pId' => 1, 'text' => '<p>Чл. 1. Текст.</p>', 'type' => 1]]],
        'content_fetched_at' => now(),
        'processed_at' => now(),
    ]);

    app(LawTreeProcessor::class)->process($law);

    $this->artisan('laws:export-public', ['--output' => $this->outputDir])
        ->assertExitCode(0);

    $payload = json_decode(
        File::get(File::files($this->outputDir.'/laws')[0]->getPathname()),
        true,
        flags: JSON_THROW_ON_ERROR
    );

    expect($payload['versions']['current'])->toMatchArray([
        'changed_at' => '2026-06-16',
        'label' => 'ДВ бр. 55 от 2026 г.',
        'dv' => 55,
        'valid_from' => '2026-06-20',
        'valid_to' => null,
    ])
        ->and($payload['versions']['current']['source_hash'])->not->toBeNull()
        ->and($payload['versions']['known'])->toHaveCount(1);
});
