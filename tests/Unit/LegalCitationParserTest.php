<?php

declare(strict_types=1);

use App\Services\LegalCitationParser;

beforeEach(function () {
    $this->parser = new LegalCitationParser;
});

test('builds canonical paths that match node paths', function (array $components, string $expected) {
    expect($this->parser->toCanonicalPath($components))->toBe($expected);
})->with([
    'article only' => [['article' => '143'], 'ЧЛ143'],
    'article with suffix' => [['article' => '80а'], 'ЧЛ80А'],
    'full citation' => [
        ['article' => '80а', 'paragraph' => '1', 'point' => '6'],
        'ЧЛ80А/АЛ1/Т6',
    ],
    'down to the letter' => [
        ['article' => '164в', 'paragraph' => '2', 'point' => '5', 'letter' => 'б'],
        'ЧЛ164В/АЛ2/Т5/БУКВА_Б',
    ],
    'transitional paragraph' => [['section' => '6', 'point' => '18б'], '§6/Т18Б'],
    'suffixed letter' => [['article' => '9', 'letter' => 'а1'], 'ЧЛ9/БУКВА_А1'],
]);

test('an empty component set has no path', function () {
    expect($this->parser->toCanonicalPath([]))->toBeNull();
});

test('scans a full citation as one target', function () {
    $citations = $this->parser->scan('Наказва се по чл. 80а, ал. 1, т. 6 от закона.');

    expect($citations)->toHaveCount(1)
        ->and($citations[0]['canonical_path'])->toBe('ЧЛ80А/АЛ1/Т6')
        ->and($citations[0]['text'])->toBe('чл. 80а, ал. 1, т. 6')
        ->and($citations[0]['is_relative'])->toBeFalse()
        ->and($citations[0]['external_act'])->toBeNull();
});

test('separated components are separate citations', function () {
    // The components descend, but prose stands between them: merging these
    // would invent a чл. 80а, ал. 3 that the text never cited.
    $citations = $this->parser->scan('Редът по чл. 80а се прилага при условията на ал. 3.');

    expect($citations)->toHaveCount(2)
        ->and($citations[0]['canonical_path'])->toBe('ЧЛ80А')
        ->and($citations[1]['canonical_path'])->toBe('АЛ3')
        ->and($citations[1]['is_relative'])->toBeTrue();
});

test('an enumerated sibling inherits the levels above it', function () {
    $citations = $this->parser->scan('Изискванията по чл. 80а, ал. 1 и ал. 3 се прилагат.');

    expect(array_column($citations, 'canonical_path'))
        ->toBe(['ЧЛ80А/АЛ1', 'ЧЛ80А/АЛ3']);
});

test('a citation cannot ascend into a new article', function () {
    $citations = $this->parser->scan('т. 6 чл. 80а');

    expect(array_column($citations, 'canonical_path'))->toBe(['Т6', 'ЧЛ80А']);
});

test('detects a named external act', function () {
    $citations = $this->parser->scan('по чл. 5 от Закона за здравето се прилага');

    expect($citations)->toHaveCount(1)
        ->and($citations[0]['canonical_path'])->toBe('ЧЛ5')
        ->and($citations[0]['external_act'])->toBe('Закона за здравето');
});

test('detects an abbreviated external act', function () {
    $citations = $this->parser->scan('съгласно чл. 12 от ЗДДС.');

    expect($citations[0]['external_act'])->toBe('ЗДДС');
});

test('a citation without "от" stays local', function () {
    $citations = $this->parser->scan('по чл. 12 се прилага редът на предходната алинея');

    expect($citations[0]['external_act'])->toBeNull();
});

test('resolves a relative citation against the citing node', function (string $source, array $components, string $expected) {
    expect($this->parser->resolveRelative($source, $components))->toBe($expected);
})->with([
    // "регистъра по ал. 5" inside чл. 80а, ал. 1 means чл. 80а, ал. 5 — the
    // kind of reference the ИЕПС keyword export could never follow.
    'alinea from a point' => ['ЧЛ80А/АЛ1/Т6', ['paragraph' => '5'], 'ЧЛ80А/АЛ5'],
    'point from an alinea' => ['ЧЛ80А/АЛ1', ['point' => '6'], 'ЧЛ80А/АЛ1/Т6'],
    'letter from a point' => ['ЧЛ80А/АЛ1/Т6', ['letter' => 'б'], 'ЧЛ80А/АЛ1/Т6/БУКВА_Б'],
    'alinea from the article itself' => ['ЧЛ80А', ['paragraph' => '2'], 'ЧЛ80А/АЛ2'],
    // § numbering restarts per block, so the ПЗР container must survive.
    'point inside a transitional block' => ['ДОП/§6/Т18', ['point' => '20'], 'ДОП/§6/Т20'],
]);

test('an act title keeps the words real titles contain', function (string $text, string $expected) {
    expect($this->parser->scan($text)[0]['external_act'])->toBe($expected);
})->with([
    'conjunction inside the title' => [
        'по чл. 4 от Закона за здравословни и безопасни условия на труд се прилага',
        'Закона за здравословни и безопасни условия на труд',
    ],
    'preposition inside the title' => [
        'по чл. 6 от Закона за защита при бедствия не се дължи',
        'Закона за защита при бедствия',
    ],
    'code' => ['чл. 328 от Кодекса на труда', 'Кодекса на труда'],
]);
