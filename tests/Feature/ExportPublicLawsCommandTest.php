<?php

declare(strict_types=1);

use App\Models\Law;
use App\Models\LawNode;
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
