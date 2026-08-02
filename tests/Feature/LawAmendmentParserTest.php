<?php

declare(strict_types=1);

use App\Models\Law;
use App\Services\LawAmendmentParser;
use App\Services\LawTreeProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;

beforeEach(function () {
    $this->parser = app(LawAmendmentParser::class);
    $this->draft = tempnam(sys_get_temp_dir(), 'amendment').'.txt';
});

afterEach(function () {
    if (isset($this->draft) && file_exists($this->draft)) {
        unlink($this->draft);
    }
});

test('amendment targets carry a path that law_nodes can be joined on', function () {
    // The human-readable path was the only output, and nothing could match it
    // against a node — so a parsed amendment knew which provision it changed
    // and still could not point at it.
    file_put_contents($this->draft, <<<'TXT'
        ЗАКОН за изменение и допълнение на Закона за движението по пътищата

        § 1. В чл. 151, ал. 1, т. 12 думите "се заменят" се заменят с "отпадат".

        § 2. В § 6, т. 18б се правят следните изменения.
        TXT);

    $result = $this->parser->parse($this->draft);

    $targets = collect($result['amendments'])
        ->flatMap(fn (array $amendment): array => $amendment['targets'])
        ->all();

    expect(collect($targets)->pluck('canonical_path')->all())
        ->toContain('ЧЛ151/АЛ1/Т12')
        ->toContain('§6/Т18Б');

    foreach ($targets as $target) {
        expect($target)->toHaveKeys(['path', 'canonical_path'])
            ->and($target['canonical_path'])->not->toBeNull();
    }
});

test('a canonical target matches the path the tree processor produces', function () {
    // Both sides are asserted together on purpose: the two spellings are
    // produced by different code, and a drift between them is exactly the
    // failure this is meant to prevent.
    file_put_contents($this->draft, "ЗАКОН за изменение\n\n§ 1. В чл. 80а, ал. 1, т. 6 се добавя изречение.\n");

    $law = Law::factory()->create([
        'content_structure' => [['pId' => 1, 'caption' => 'Чл. 80а']],
        'content_text' => ['paragraphs' => [[
            'pId' => 1,
            'text' => '<p>Чл. 80а. Водачът е длъжен:</p><p>(1) При движение:</p><p>6. да носи каска.</p>',
            'type' => 1,
        ]]],
        'content_fetched_at' => now(),
    ]);

    app(LawTreeProcessor::class)->process($law);

    $canonical = $this->parser->parse($this->draft)['amendments'][0]['targets'][0]['canonical_path'];

    expect($law->nodes()->where('path', $canonical)->exists())->toBeTrue();
})->uses(RefreshDatabase::class);
