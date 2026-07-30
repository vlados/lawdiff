<?php

declare(strict_types=1);

use App\Models\Law;
use App\Models\LawNode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->outputDir = storage_path('app/test-ieps-'.uniqid());
    $this->sourceDir = storage_path('app/test-ieps-source-'.uniqid());

    File::ensureDirectoryExists($this->sourceDir);

    config()->set('ieps.keywords', [IEPS_PATTERN]);
    config()->set('ieps.include', []);
    config()->set('ieps.floor', ['laws' => 0, 'nodes' => 0, 'matched' => 0]);
});

afterEach(function (): void {
    foreach ([$this->outputDir ?? null, $this->sourceDir ?? null] as $dir) {
        if (is_string($dir) && File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }
    }
});

const IEPS_PATTERN = '/индивидуалн[а-я]*\s+електрическ[а-я]*/iu';

function iepsSlug(string $caption): string
{
    return Str::slug($caption, '-', 'bg');
}

/**
 * @param  list<array{0: string, 1: string|null}>  $rows  [path, text_markdown]
 */
function iepsLaw(string $caption, array $rows, bool $processed = true): Law
{
    $law = Law::factory()->create([
        'caption' => $caption,
        'content_fetched_at' => now(),
        'processed_at' => $processed ? now() : null,
    ]);

    foreach ($rows as $index => [$path, $text]) {
        $segments = explode('/', $path);

        LawNode::create([
            'law_id' => $law->id,
            'path' => $path,
            'caption' => end($segments),
            'text_markdown' => $text,
            'level' => count($segments) - 1,
            'sort_order' => $index + 1,
        ]);
    }

    return $law;
}

function writeSourceIndex(string $dir, Law ...$laws): void
{
    File::put($dir.'/index.json', json_encode([
        'generated_at' => now()->toIso8601String(),
        'count' => count($laws),
        'laws' => array_map(fn (Law $law): array => [
            'unique_id' => $law->unique_id,
            'slug' => iepsSlug((string) $law->caption),
        ], $laws),
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}

function readIepsJson(string $path): array
{
    return json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
}

test('exports a matched provision with its subtree and its ancestor chain', function () {
    $law = iepsLaw('ЗАКОН за движението по пътищата', [
        ['ЧЛ80А', null],
        ['ЧЛ80А/АЛ1', 'Водачът на индивидуално електрическо превозно средство е длъжен:'],
        ['ЧЛ80А/АЛ1/Т1', 'да ползва защитна каска;'],
        ['ЧЛ80А/АЛ1/Т2', 'да ползва светлоотразителна жилетка;'],
        ['ЧЛ80А/АЛ2', 'Съвсем несвързана алинея.'],
        ['ЧЛ99', 'Паркирането е забранено.'],
    ]);

    writeSourceIndex($this->sourceDir, $law);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
    ])->assertExitCode(0);

    $slug = iepsSlug('ЗАКОН за движението по пътищата');
    $payload = readIepsJson($this->outputDir."/laws/{$slug}.json");

    // Ancestor pulled up, subtree pulled down, unrelated siblings left out.
    expect($payload['nodes'])->toHaveCount(1)
        ->and($payload['nodes'][0]['path'])->toBe('ЧЛ80А')
        ->and($payload['nodes'][0]['children'])->toHaveCount(1)
        ->and($payload['nodes'][0]['children'][0]['path'])->toBe('ЧЛ80А/АЛ1')
        ->and($payload['nodes'][0]['children'][0]['children'])->toHaveCount(2);

    expect($payload['topic'])->toBe('ieps')
        ->and($payload['slug'])->toBe($slug)
        ->and($payload['ieps']['matched_paths'])->toBe(['ЧЛ80А/АЛ1'])
        ->and($payload['ieps']['exported_nodes'])->toBe(4)
        ->and($payload['ieps']['total_nodes_in_law'])->toBe(6)
        ->and($payload['ieps']['full_text'])->toBe("data/laws/{$slug}.json");
});

test('matches case-insensitively across Cyrillic', function () {
    // A provision that opens a sentence with "Индивидуално" and never repeats the
    // term in lowercase is unreachable without the `i` modifier.
    $law = iepsLaw('ЗАКОН с главна буква', [
        ['ДОП', null],
        ['ДОП/ПАР6', 'Индивидуално електрическо превозно средство е пътно превозно средство.'],
    ]);

    writeSourceIndex($this->sourceDir, $law);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
    ])->assertExitCode(0);

    $index = readIepsJson($this->outputDir.'/index.json');

    expect($index['count'])->toBe(1)
        ->and($index['laws'][0]['matched_nodes'])->toBe(1);
});

test('skips laws with no ИЕПС provisions', function () {
    $relevant = iepsLaw('ЗАКОН за движението', [
        ['ЧЛ1', 'Индивидуалните електрически превозни средства се регистрират.'],
    ]);
    $irrelevant = iepsLaw('ЗАКОН за пчеларството', [
        ['ЧЛ1', 'Пчелините се регистрират в общината.'],
    ]);

    writeSourceIndex($this->sourceDir, $relevant, $irrelevant);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
    ])->assertExitCode(0);

    expect(File::files($this->outputDir.'/laws'))->toHaveCount(1)
        ->and(File::exists($this->outputDir.'/laws/'.iepsSlug('ЗАКОН за движението').'.json'))->toBeTrue();
});

test('allowlist reaches provisions no keyword can', function () {
    // чл. 80а ал. 8 governs the ИЕПС registry but says "регистъра по ал. 5" instead.
    $law = iepsLaw('ЗАКОН за движението по пътищата', [
        ['ЧЛ80А', null],
        ['ЧЛ80А/АЛ5', 'Регистърът на индивидуалните електрически превозни средства.'],
        ['ЧЛ80А/АЛ8', 'Вписването в регистъра по ал. 5 се извършва от кмета.'],
        ['ЧЛ99', 'Несвързан текст.'],
    ]);

    writeSourceIndex($this->sourceDir, $law);
    $slug = iepsSlug('ЗАКОН за движението по пътищата');

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
    ])->assertExitCode(0);

    // Keywords alone reach ал. 5 and its parent, but never ал. 8.
    expect(readIepsJson($this->outputDir."/laws/{$slug}.json")['ieps']['exported_nodes'])->toBe(2);

    config()->set('ieps.include', [$slug => ['ЧЛ80А']]);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
    ])->assertExitCode(0);

    $payload = readIepsJson($this->outputDir."/laws/{$slug}.json");

    expect($payload['ieps']['exported_nodes'])->toBe(3)
        ->and($payload['ieps']['allowlisted_paths'])->toBe(['ЧЛ80А', 'ЧЛ80А/АЛ5', 'ЧЛ80А/АЛ8']);
});

test('allowlist prefix does not swallow a longer sibling article', function () {
    // Prefix "ЧЛ80" must match чл. 80 without dragging in чл. 80а.
    $law = iepsLaw('ЗАКОН за велосипедите', [
        ['ЧЛ80', 'Водачът на велосипед е длъжен.'],
        ['ЧЛ80А', 'Водачът е длъжен да носи каска.'],
    ]);

    writeSourceIndex($this->sourceDir, $law);
    $slug = iepsSlug('ЗАКОН за велосипедите');
    config()->set('ieps.include', [$slug => ['ЧЛ80']]);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
    ])->assertExitCode(0);

    $payload = readIepsJson($this->outputDir."/laws/{$slug}.json");

    expect($payload['ieps']['exported_nodes'])->toBe(1)
        ->and($payload['nodes'])->toHaveCount(1)
        ->and($payload['nodes'][0]['path'])->toBe('ЧЛ80');
});

test('fails when an allowlist entry resolves to nothing', function () {
    $law = iepsLaw('ЗАКОН за движението', [
        ['ЧЛ1', 'Индивидуално електрическо превозно средство.'],
    ]);

    writeSourceIndex($this->sourceDir, $law);
    $slug = iepsSlug('ЗАКОН за движението');
    config()->set('ieps.include', [$slug => ['ЧЛ80А']]);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
    ])
        ->expectsOutputToContain('resolved to nothing')
        ->expectsOutputToContain("{$slug} => ЧЛ80А")
        ->assertExitCode(1);
});

test('fails when an allowlisted law is absent from the corpus entirely', function () {
    $law = iepsLaw('ЗАКОН за движението', [
        ['ЧЛ1', 'Индивидуално електрическо превозно средство.'],
    ]);

    writeSourceIndex($this->sourceDir, $law);
    config()->set('ieps.include', ['zakon-za-elektronnoto-upravlenie' => ['ЧЛ52А']]);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
    ])
        ->expectsOutputToContain('zakon-za-elektronnoto-upravlenie => ЧЛ52А')
        ->assertExitCode(1);
});

test('floor guard fails the run before writing anything', function () {
    $law = iepsLaw('ЗАКОН за пчеларството', [
        ['ЧЛ1', 'Пчелините се регистрират.'],
    ]);

    writeSourceIndex($this->sourceDir, $law);
    config()->set('ieps.floor', ['laws' => 2, 'nodes' => 40]);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
    ])
        ->expectsOutputToContain('Floor guard tripped')
        ->expectsOutputToContain('Nothing was written')
        ->assertExitCode(1);

    expect(File::isDirectory($this->outputDir))->toBeFalse();
});

test('floor guard leaves the previous dataset intact when it trips', function () {
    $law = iepsLaw('ЗАКОН за пчеларството', [
        ['ЧЛ1', 'Пчелините се регистрират.'],
    ]);

    writeSourceIndex($this->sourceDir, $law);

    File::ensureDirectoryExists($this->outputDir.'/laws');
    File::put($this->outputDir.'/laws/previous-run.json', '{"kept":true}');

    config()->set('ieps.floor', ['laws' => 1, 'nodes' => 1]);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
        '--prune' => true,
    ])->assertExitCode(1);

    expect(File::exists($this->outputDir.'/laws/previous-run.json'))->toBeTrue();
});

test('floor guard catches a keyword collapse that the allowlist would mask', function () {
    // The allowlisted subtree alone clears the node floor, so without a separate
    // guard on the keyword layer a total matching failure would look healthy.
    $law = iepsLaw('ЗАКОН за движението', [
        ['ЧЛ80А', null],
        ['ЧЛ80А/АЛ1', 'Водачът е длъжен да носи каска.'],
        ['ЧЛ80А/АЛ2', 'Возенето на повече от един човек е забранено.'],
        ['ЧЛ80А/АЛ3', 'Минималната възраст е шестнадесет години.'],
    ]);

    writeSourceIndex($this->sourceDir, $law);
    config()->set('ieps.include', [iepsSlug('ЗАКОН за движението') => ['ЧЛ80А']]);
    config()->set('ieps.floor', ['laws' => 1, 'nodes' => 4, 'matched' => 1]);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
    ])
        ->expectsOutputToContain('Floor guard tripped')
        ->expectsOutputToContain('0 keyword match(es), expected at least 1')
        ->assertExitCode(1);

    expect(File::isDirectory($this->outputDir))->toBeFalse();
});

test('skip-floor bypasses the guard', function () {
    $law = iepsLaw('ЗАКОН за пчеларството', [
        ['ЧЛ1', 'Пчелините се регистрират.'],
    ]);

    writeSourceIndex($this->sourceDir, $law);
    config()->set('ieps.floor', ['laws' => 2, 'nodes' => 40]);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
        '--skip-floor' => true,
    ])
        ->expectsOutputToContain('Floor guard skipped')
        ->assertExitCode(0);
});

test('fails when the public index is missing', function () {
    iepsLaw('ЗАКОН за движението', [
        ['ЧЛ1', 'Индивидуално електрическо превозно средство.'],
    ]);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/does-not-exist.json',
    ])
        ->expectsOutputToContain('Run `laws:export-public` first')
        ->assertExitCode(1);
});

test('fails when a matching law is missing from the public index', function () {
    iepsLaw('ЗАКОН за движението', [
        ['ЧЛ1', 'Индивидуално електрическо превозно средство.'],
    ]);

    writeSourceIndex($this->sourceDir);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
    ])
        ->expectsOutputToContain('missing from the public index')
        ->assertExitCode(1);
});

test('refuses to write into the public export directory', function () {
    $law = iepsLaw('ЗАКОН за движението', [
        ['ЧЛ1', 'Индивидуално електрическо превозно средство.'],
    ]);

    writeSourceIndex($this->sourceDir, $law);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->sourceDir,
        '--source-index' => $this->sourceDir.'/index.json',
    ])
        ->expectsOutputToContain('Refusing to write into the public export directory')
        ->assertExitCode(1);

    expect(readIepsJson($this->sourceDir.'/index.json')['count'])->toBe(1);
});

test('carries slugs through from the public export instead of recomputing them', function () {
    $law = iepsLaw('ЗАКОН за движението по пътищата', [
        ['ЧЛ1', 'Индивидуално електрическо превозно средство.'],
    ]);

    // The public export resolved a slug collision with a suffix; the fork must follow it,
    // otherwise the two datasets disagree on the filename for the same law.
    $collided = iepsSlug('ЗАКОН за движението по пътищата').'-1';

    File::put($this->sourceDir.'/index.json', json_encode([
        'count' => 1,
        'laws' => [['unique_id' => $law->unique_id, 'slug' => $collided]],
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
    ])->assertExitCode(0);

    expect(File::exists($this->outputDir."/laws/{$collided}.json"))->toBeTrue();

    $index = readIepsJson($this->outputDir.'/index.json');

    expect($index['laws'][0]['slug'])->toBe($collided)
        ->and($index['laws'][0]['full_text'])->toBe("data/laws/{$collided}.json");
});

test('writes index, csv and readme, and prunes stale files', function () {
    $law = iepsLaw('ЗАКОН за движението', [
        ['ЧЛ1', 'Индивидуално електрическо превозно средство.'],
    ]);

    writeSourceIndex($this->sourceDir, $law);

    File::ensureDirectoryExists($this->outputDir.'/laws');
    File::put($this->outputDir.'/laws/stale-law.json', '{}');

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
        '--prune' => true,
    ])->assertExitCode(0);

    expect(File::exists($this->outputDir.'/laws/stale-law.json'))->toBeFalse()
        ->and(File::exists($this->outputDir.'/index.csv'))->toBeTrue()
        ->and(File::exists($this->outputDir.'/README.md'))->toBeTrue();

    $index = readIepsJson($this->outputDir.'/index.json');

    expect($index['topic'])->toBe('ieps')
        ->and($index['node_count'])->toBe(1)
        ->and($index['criteria']['keywords'])->toBe([IEPS_PATTERN]);

    // The README belongs to the fork, not to the public dataset.
    expect(File::get($this->outputDir.'/README.md'))->toContain('ИЕПС');
});

test('ignores laws that have not been processed', function () {
    $law = iepsLaw('ЗАКОН за движението', [
        ['ЧЛ1', 'Индивидуално електрическо превозно средство.'],
    ], processed: false);

    writeSourceIndex($this->sourceDir, $law);

    $this->artisan('laws:export-ieps', [
        '--output' => $this->outputDir,
        '--source-index' => $this->sourceDir.'/index.json',
    ])->assertExitCode(0);

    expect(File::files($this->outputDir.'/laws'))->toHaveCount(0);
});
