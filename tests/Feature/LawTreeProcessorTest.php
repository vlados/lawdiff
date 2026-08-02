<?php

declare(strict_types=1);

use App\Models\Law;
use App\Models\LawNode;
use App\Services\LawPathBuilder;
use App\Services\LawTreeProcessor;
use App\Services\LegalReferenceExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->processor = app(LawTreeProcessor::class);
});

test('processes simple law with html to markdown conversion', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 1', 'parentId' => null],
        ],
        'content_text' => [
            'paragraphs' => [
                [
                    'pId' => 1,
                    'text' => '<p>This is <strong>bold</strong> text with <em>italic</em>.</p>',
                    'type' => 1,
                    'fieldType' => 1,
                    'hasInLinks' => false,
                ],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $nodes = $law->nodes;

    expect($nodes)->toHaveCount(1)
        ->and($nodes[0]->path)->toBe('ЧЛ1')
        ->and($nodes[0]->caption)->toBe('Чл. 1')
        ->and($nodes[0]->text_markdown)->toBe('This is **bold** text with *italic*.')
        ->and($nodes[0]->node_type)->toBe('article')
        ->and($nodes[0]->is_orphaned)->toBeFalse();
});

test('processes law with nested children', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            [
                'pId' => 1,
                'caption' => 'Глава 1',
                'parentId' => null,
                'children' => [
                    [
                        'pId' => 2,
                        'caption' => 'Чл. 1',
                        'parentId' => 1,
                        'children' => [
                            [
                                'pId' => 3,
                                'caption' => 'Ал. 1',
                                'parentId' => 2,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'content_text' => [
            'paragraphs' => [
                ['pId' => 1, 'text' => '<p>Chapter text</p>', 'type' => 1],
                ['pId' => 2, 'text' => '<p>Article text</p>', 'type' => 2],
                ['pId' => 3, 'text' => '<p>Paragraph text</p>', 'type' => 3],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $nodes = $law->nodes()->orderBy('sort_order')->get();

    // The chapter is kept as a node, but stays out of the article's citation
    // path: чл. 1 is cited as ЧЛ1 regardless of which глава holds it.
    expect($nodes)->toHaveCount(3)
        ->and($nodes[0]->path)->toBe('ГЛАВА_1')
        ->and($nodes[0]->node_type)->toBe('chapter')
        ->and($nodes[0]->caption)->toBe('Глава 1')
        ->and($nodes[0]->text_markdown)->toBe('Chapter text')
        ->and($nodes[0]->parent_id)->toBeNull()
        ->and($nodes[1]->path)->toBe('ЧЛ1')
        ->and($nodes[1]->level)->toBe(1)
        ->and($nodes[1]->caption)->toBe('Чл. 1')
        ->and($nodes[1]->parent_id)->toBe($nodes[0]->id)
        ->and($nodes[2]->path)->toBe('ЧЛ1/АЛ1')
        ->and($nodes[2]->level)->toBe(2)
        ->and($nodes[2]->caption)->toBe('Ал. 1')
        ->and($nodes[2]->parent_id)->toBe($nodes[1]->id);
});

test('точки with letter suffixes become their own nodes', function () {
    // ЗДвП § 6 defines terms as т. 18, 18а, 18б, 18в. A точка pattern of (\d+)
    // never matched "18а." at all, so four separate definitions collapsed into
    // one node — a single embedding covering полуремарке, регистрация and ИЕПС.
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => '§ 6'],
        ],
        'content_text' => [
            'paragraphs' => [
                [
                    'pId' => 1,
                    'text' => '<p>§ 6. По смисъла на този закон:</p>'
                        .'<p>18. "Полуремарке" е ремарке без предна ос.</p>'
                        .'<p>18а. "Регистрация" е административно разрешение.</p>'
                        .'<p>18б. "Индивидуално електрическо превозно средство" е пътно превозно средство.</p>'
                        .'<p>18в. "Самобалансиращо се превозно средство" е ППС с електродвигател.</p>',
                    'type' => 1,
                ],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $nodes = $law->nodes()->orderBy('sort_order')->get();

    expect($nodes)->toHaveCount(5)
        ->and($nodes[0]->path)->toBe('§6')
        ->and($nodes[1]->path)->toBe('§6/Т18')
        ->and($nodes[1]->text_markdown)->toContain('Полуремарке')
        ->and($nodes[2]->path)->toBe('§6/Т18А')
        ->and($nodes[2]->text_markdown)->toContain('Регистрация')
        ->and($nodes[2]->text_markdown)->not->toContain('Полуремарке')
        ->and($nodes[3]->path)->toBe('§6/Т18Б')
        ->and($nodes[3]->text_markdown)->toContain('Индивидуално електрическо')
        ->and($nodes[4]->path)->toBe('§6/Т18В')
        ->and($nodes[4]->text_markdown)->toContain('Самобалансиращо');
});

test('букви with numeric suffixes become their own nodes', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 9'],
        ],
        'content_text' => [
            'paragraphs' => [
                [
                    'pId' => 1,
                    'text' => '<p>Чл. 9. Освобождават се:</p>'
                        .'<p>а) учебните заведения;</p>'
                        .'<p>а1) детските градини;</p>'
                        .'<p>б) читалищата.</p>',
                    'type' => 1,
                ],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $paths = $law->nodes()->orderBy('sort_order')->pluck('path')->all();

    expect($paths)->toBe(['ЧЛ9', 'ЧЛ9/БУКВА_А', 'ЧЛ9/БУКВА_А1', 'ЧЛ9/БУКВА_Б']);
});

test('sections nest under chapters without entering citation paths', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            [
                'pId' => 1,
                'caption' => 'Глава първа',
                'children' => [
                    [
                        'pId' => 2,
                        'caption' => 'Раздел I',
                        'children' => [
                            ['pId' => 3, 'caption' => 'Чл. 1'],
                        ],
                    ],
                ],
            ],
            [
                'pId' => 4,
                'caption' => 'Глава втора',
                'children' => [
                    [
                        'pId' => 5,
                        'caption' => 'Раздел I',
                        'children' => [
                            ['pId' => 6, 'caption' => 'Чл. 2'],
                        ],
                    ],
                ],
            ],
        ],
        'content_text' => ['paragraphs' => []],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $paths = $law->nodes()->orderBy('sort_order')->pluck('path')->all();

    // "Раздел I" repeats per chapter, so containers nest among themselves to
    // stay distinct — while the articles keep bare, citeable paths.
    expect($paths)->toBe([
        'ГЛАВА_ПЪРВА',
        'ГЛАВА_ПЪРВА/РАЗДЕЛ_I',
        'ЧЛ1',
        'ГЛАВА_ВТОРА',
        'ГЛАВА_ВТОРА/РАЗДЕЛ_I',
        'ЧЛ2',
    ]);
});

test('splitting an article conserves every fragment of its text exactly once', function () {
    // The structural guarantee the corpus rests on, checked from two sides
    // because neither alone is enough:
    //
    // The path list catches merging. An unrecognised marker does not lose text —
    // it leaves the following provision buried inside its predecessor, which is
    // exactly what (\d+) did to "18а." and what produces a node whose embedding
    // covers four unrelated definitions at once.
    //
    // The concatenation catches loss and duplication, which merging hides: it
    // shortens or lengthens the total, so a fragment dropped on the floor or
    // emitted under two nodes fails here even when every path looks right.
    $html = '<p>Чл. 80а. Водачът на ИЕПС е длъжен:</p>'
        .'<p>(1) При движение по пътищата:</p>'
        .'<p>1. да управлява с двете ръце;</p>'
        .'<p>18. да ползва велоалея, когато има такава;</p>'
        .'<p>18а. да не превозва други лица;</p>'
        .'<p>18б. да не се движи по автомагистрала;</p>'
        .'<p>(2) Забраната по ал. 1 не се прилага за:</p>'
        .'<p>а) служебни превозни средства;</p>'
        .'<p>а1) превозни средства на органите за пожарна безопасност;</p>'
        .'<p>б) технически изпитвания.</p>';

    // Markdown of the same payload, unsplit: a caption the splitter ignores
    // gives us the conversion result without coupling the test to the converter.
    $reference = Law::factory()->create([
        'content_structure' => [['pId' => 1, 'caption' => 'Приложение']],
        'content_text' => ['paragraphs' => [['pId' => 1, 'text' => $html, 'type' => 1]]],
        'content_fetched_at' => now(),
    ]);
    $this->processor->process($reference);

    $sourceMarkdown = $reference->nodes()->firstOrFail()->text_markdown;
    expect($reference->nodes()->count())->toBe(1);

    $law = Law::factory()->create([
        'content_structure' => [['pId' => 1, 'caption' => 'Чл. 80а']],
        'content_text' => ['paragraphs' => [['pId' => 1, 'text' => $html, 'type' => 1]]],
        'content_fetched_at' => now(),
    ]);
    $this->processor->process($law);

    $nodes = $law->nodes()->orderBy('sort_order')->get();

    expect($nodes->pluck('path')->all())->toBe([
        'ЧЛ80А',
        'ЧЛ80А/АЛ1',
        'ЧЛ80А/АЛ1/Т1',
        'ЧЛ80А/АЛ1/Т18',
        'ЧЛ80А/АЛ1/Т18А',
        'ЧЛ80А/АЛ1/Т18Б',
        'ЧЛ80А/АЛ2',
        'ЧЛ80А/АЛ2/БУКВА_А',
        'ЧЛ80А/АЛ2/БУКВА_А1',
        'ЧЛ80А/АЛ2/БУКВА_Б',
    ]);

    // Applied to both sides, so removing the markers the splitter consumes can
    // never manufacture a pass out of a genuine difference in the prose.
    $withoutMarkers = function (string $text): string {
        $text = preg_replace('/\(\d{1,2}[а-я]?\)/u', '', $text);
        $text = preg_replace('/\d{1,3}[а-я]?\\\\?\./u', '', (string) $text);
        $text = preg_replace('/[а-я]\d?\)/u', '', (string) $text);

        return (string) preg_replace('/\s+/u', '', (string) $text);
    };

    expect($withoutMarkers($nodes->pluck('text_markdown')->implode('')))
        ->toBe($withoutMarkers((string) $sourceMarkdown));
});

test('transitional paragraphs stay prefixed by their section', function () {
    // § numbering restarts in every ПЗР block, so unlike глава the section must
    // remain in the path or the § of two amending acts would collide.
    $law = Law::factory()->create([
        'content_structure' => [
            [
                'pId' => 1,
                'caption' => 'ПРЕХОДНИ И ЗАКЛЮЧИТЕЛНИ РАЗПОРЕДБИ',
                'children' => [
                    ['pId' => 2, 'caption' => '§ 1'],
                ],
            ],
        ],
        'content_text' => ['paragraphs' => []],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $paths = $law->nodes()->orderBy('sort_order')->pluck('path')->all();

    expect($paths)->toBe(['ПЗР', 'ПЗР/§1']);
});

test('includes orphaned paragraphs not in structure', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 1', 'parentId' => null],
        ],
        'content_text' => [
            'paragraphs' => [
                ['pId' => 1, 'text' => '<p>In structure</p>', 'type' => 1, 'fieldType' => 1],
                ['pId' => 2, 'text' => '<p>Title</p>', 'type' => 0, 'fieldType' => 1],
                ['pId' => 3, 'text' => '<p>Publication info</p>', 'type' => 0, 'fieldType' => 2],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $nodes = $law->nodes()->orderBy('sort_order')->get();

    expect($nodes)->toHaveCount(3)
        ->and($nodes[0]->is_orphaned)->toBeFalse()
        ->and($nodes[0]->path)->toBe('ЧЛ1')
        ->and($nodes[1]->is_orphaned)->toBeTrue()
        ->and($nodes[1]->path)->toBe('ЗАГЛАВИЕ')
        ->and($nodes[1]->node_type)->toBe('title')
        ->and($nodes[2]->is_orphaned)->toBeTrue()
        ->and($nodes[2]->path)->toBe('ПУБЛ_ИНФО')
        ->and($nodes[2]->node_type)->toBe('publication_info');
});

test('handles empty content structure', function () {
    $law = Law::factory()->create([
        'content_structure' => [],
        'content_text' => ['paragraphs' => []],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    expect($law->nodes)->toHaveCount(0);
});

test('converts complex html to markdown correctly', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 1', 'parentId' => null],
        ],
        'content_text' => [
            'paragraphs' => [
                [
                    'pId' => 1,
                    'text' => '<div><p>Paragraph with <a href="http://example.com">link</a> and <ul><li>item 1</li><li>item 2</li></ul></p></div>',
                    'type' => 1,
                ],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $node = $law->nodes->first();

    expect($node->text_markdown)
        ->toContain('[link](http://example.com)')
        ->toContain('item 1')
        ->toContain('item 2');
});

test('removes excessive newlines from markdown', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 1', 'parentId' => null],
        ],
        'content_text' => [
            'paragraphs' => [
                [
                    'pId' => 1,
                    'text' => "<p>First paragraph</p>\n\n\n\n<p>Second paragraph</p>",
                    'type' => 1,
                ],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    expect($law->nodes->first()->text_markdown)->not->toContain("\n\n\n");
});

test('handles nodes without pId gracefully', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            ['caption' => 'Invalid Node'], // Missing pId
            ['pId' => 1, 'caption' => 'Чл. 1', 'parentId' => null],
        ],
        'content_text' => [
            'paragraphs' => [
                ['pId' => 1, 'text' => '<p>Text</p>', 'type' => 1],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    expect($law->nodes)->toHaveCount(1)
        ->and($law->nodes->first()->p_id)->toBe(1);
});

test('deletes existing nodes before reprocessing', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 1', 'parentId' => null],
        ],
        'content_text' => [
            'paragraphs' => [
                ['pId' => 1, 'text' => '<p>Original text</p>', 'type' => 1],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    // First process
    $this->processor->process($law);
    expect($law->nodes)->toHaveCount(1);

    // Update content and reprocess
    $law->update([
        'content_structure' => [
            ['pId' => 2, 'caption' => 'Чл. 2', 'parentId' => null],
            ['pId' => 3, 'caption' => 'Чл. 3', 'parentId' => null],
        ],
        'content_text' => [
            'paragraphs' => [
                ['pId' => 2, 'text' => '<p>New text 1</p>', 'type' => 1],
                ['pId' => 3, 'text' => '<p>New text 2</p>', 'type' => 1],
            ],
        ],
    ]);

    $this->processor->process($law->fresh());

    $law->refresh();
    expect($law->nodes)->toHaveCount(2)
        ->and($law->nodes()->where('p_id', 1)->exists())->toBeFalse()
        ->and($law->nodes()->where('p_id', 2)->exists())->toBeTrue()
        ->and($law->nodes()->where('p_id', 3)->exists())->toBeTrue();
});

test('parses article with алинеи into separate nodes', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 134', 'parentId' => null],
        ],
        'content_text' => [
            'paragraphs' => [
                [
                    'pId' => 1,
                    'text' => '<p>Чл. 134. (Нов - ДВ, бр. 95 от 2003 г.) (1) Актовете за установяване на административните нарушения се съставят от инспекторите.</p><p>(2) Наказателните постановления се издават от изпълнителния директор.</p><p>(3) Установяването на нарушенията се извършва по реда на закона.</p>',
                    'type' => 1,
                ],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $nodes = $law->nodes()->orderBy('sort_order')->get();

    // Should have: 1 article + 3 алинеи = 4 nodes total
    expect($nodes)->toHaveCount(4)
        // Article node
        ->and($nodes[0]->path)->toBe('ЧЛ134')
        ->and($nodes[0]->node_type)->toBe('article')
        ->and($nodes[0]->caption)->toBe('Чл. 134')
        ->and($nodes[0]->text_markdown)->toContain('(Нов - ДВ')
        // Алинея 1
        ->and($nodes[1]->path)->toBe('ЧЛ134/АЛ1')
        ->and($nodes[1]->node_type)->toBe('paragraph')
        ->and($nodes[1]->caption)->toBeNull()
        ->and($nodes[1]->level)->toBe(1)
        ->and($nodes[1]->text_markdown)->toContain('Актовете за установяване')
        // Алинея 2
        ->and($nodes[2]->path)->toBe('ЧЛ134/АЛ2')
        ->and($nodes[2]->node_type)->toBe('paragraph')
        ->and($nodes[2]->level)->toBe(1)
        ->and($nodes[2]->text_markdown)->toContain('Наказателните постановления')
        // Алинея 3
        ->and($nodes[3]->path)->toBe('ЧЛ134/АЛ3')
        ->and($nodes[3]->node_type)->toBe('paragraph')
        ->and($nodes[3]->level)->toBe(1)
        ->and($nodes[3]->text_markdown)->toContain('Установяването на нарушенията');
});

test('parses article with точки into separate nodes', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 130', 'parentId' => null],
        ],
        'content_text' => [
            'paragraphs' => [
                [
                    'pId' => 1,
                    'text' => '<p>Чл. 130. Инспекторите са длъжни:</p><p>1. да пазят в тайна поверителните сведения;</p><p>2. да пазят в тайна източника, от който е получен сигнал.</p>',
                    'type' => 1,
                ],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $nodes = $law->nodes()->orderBy('sort_order')->get();

    // Should have: 1 article + 2 точки = 3 nodes total
    expect($nodes)->toHaveCount(3)
        // Article node
        ->and($nodes[0]->path)->toBe('ЧЛ130')
        ->and($nodes[0]->node_type)->toBe('article')
        ->and($nodes[0]->text_markdown)->toContain('Инспекторите са длъжни')
        // Точка 1
        ->and($nodes[1]->path)->toBe('ЧЛ130/Т1')
        ->and($nodes[1]->node_type)->toBe('point')
        ->and($nodes[1]->level)->toBe(1)
        ->and($nodes[1]->text_markdown)->toContain('да пазят в тайна поверителните')
        // Точка 2
        ->and($nodes[2]->path)->toBe('ЧЛ130/Т2')
        ->and($nodes[2]->node_type)->toBe('point')
        ->and($nodes[2]->level)->toBe(1)
        ->and($nodes[2]->text_markdown)->toContain('да пазят в тайна източника');
});

test('parses article with букви into separate nodes', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 5', 'parentId' => null],
        ],
        'content_text' => [
            'paragraphs' => [
                [
                    'pId' => 1,
                    'text' => '<p>Чл. 5. Документите могат да бъдат:</p><p>а) оригинали;</p><p>б) заверени копия;</p><p>в) електронни документи.</p>',
                    'type' => 1,
                ],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $nodes = $law->nodes()->orderBy('sort_order')->get();

    // Should have: 1 article + 3 букви = 4 nodes total
    expect($nodes)->toHaveCount(4)
        // Article node
        ->and($nodes[0]->path)->toBe('ЧЛ5')
        ->and($nodes[0]->node_type)->toBe('article')
        ->and($nodes[0]->text_markdown)->toContain('Документите могат да бъдат')
        // Буква а)
        ->and($nodes[1]->path)->toBe('ЧЛ5/БУКВА_А')
        ->and($nodes[1]->node_type)->toBe('letter')
        ->and($nodes[1]->level)->toBe(1)
        ->and($nodes[1]->text_markdown)->toContain('оригинали')
        // Буква б)
        ->and($nodes[2]->path)->toBe('ЧЛ5/БУКВА_Б')
        ->and($nodes[2]->node_type)->toBe('letter')
        ->and($nodes[2]->level)->toBe(1)
        ->and($nodes[2]->text_markdown)->toContain('заверени копия')
        // Буква в)
        ->and($nodes[3]->path)->toBe('ЧЛ5/БУКВА_В')
        ->and($nodes[3]->node_type)->toBe('letter')
        ->and($nodes[3]->level)->toBe(1)
        ->and($nodes[3]->text_markdown)->toContain('електронни документи');
});

test('parses nested structure with алинея containing точки', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 10', 'parentId' => null],
        ],
        'content_text' => [
            'paragraphs' => [
                [
                    'pId' => 1,
                    'text' => '<p>Чл. 10. Общи правила:</p><p>(1) За изпълнение на дейността се изискват:</p><p>1. регистрация в регистъра;</p><p>2. издаден лиценз;</p><p>3. договор за сътрудничество.</p><p>(2) Контролът се извършва от компетентния орган.</p>',
                    'type' => 1,
                ],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $nodes = $law->nodes()->orderBy('sort_order')->get();

    // Should have: 1 article + 2 алинеи + 3 точки = 6 nodes total
    expect($nodes)->toHaveCount(6)
        // Article node
        ->and($nodes[0]->path)->toBe('ЧЛ10')
        ->and($nodes[0]->node_type)->toBe('article')
        ->and($nodes[0]->text_markdown)->toContain('Общи правила')
        // Алинея 1
        ->and($nodes[1]->path)->toBe('ЧЛ10/АЛ1')
        ->and($nodes[1]->node_type)->toBe('paragraph')
        ->and($nodes[1]->level)->toBe(1)
        ->and($nodes[1]->text_markdown)->toContain('За изпълнение на дейността')
        // Точка 1 within Алинея 1
        ->and($nodes[2]->path)->toBe('ЧЛ10/АЛ1/Т1')
        ->and($nodes[2]->node_type)->toBe('point')
        ->and($nodes[2]->level)->toBe(2)
        ->and($nodes[2]->text_markdown)->toContain('регистрация в регистъра')
        // Точка 2 within Алинея 1
        ->and($nodes[3]->path)->toBe('ЧЛ10/АЛ1/Т2')
        ->and($nodes[3]->node_type)->toBe('point')
        ->and($nodes[3]->level)->toBe(2)
        ->and($nodes[3]->text_markdown)->toContain('издаден лиценз')
        // Точка 3 within Алинея 1
        ->and($nodes[4]->path)->toBe('ЧЛ10/АЛ1/Т3')
        ->and($nodes[4]->node_type)->toBe('point')
        ->and($nodes[4]->level)->toBe(2)
        ->and($nodes[4]->text_markdown)->toContain('договор за сътрудничество')
        // Алинея 2
        ->and($nodes[5]->path)->toBe('ЧЛ10/АЛ2')
        ->and($nodes[5]->node_type)->toBe('paragraph')
        ->and($nodes[5]->level)->toBe(1)
        ->and($nodes[5]->text_markdown)->toContain('Контролът се извършва');
});

test('parses deeply nested structure with алинея containing точка containing букви', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 15', 'parentId' => null],
        ],
        'content_text' => [
            'paragraphs' => [
                [
                    'pId' => 1,
                    'text' => '<p>Чл. 15. Документация:</p><p>(1) Необходими документи:</p><p>1. Лични документи включват:</p><p>а) лична карта;</p><p>б) паспорт;</p><p>в) свидетелство за раждане.</p><p>(2) Допълнителни изисквания.</p>',
                    'type' => 1,
                ],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $nodes = $law->nodes()->orderBy('sort_order')->get();

    // Should have: 1 article + 2 алинеи + 1 точка + 3 букви = 7 nodes total
    expect($nodes)->toHaveCount(7)
        // Article node
        ->and($nodes[0]->path)->toBe('ЧЛ15')
        ->and($nodes[0]->node_type)->toBe('article')
        // Алинея 1
        ->and($nodes[1]->path)->toBe('ЧЛ15/АЛ1')
        ->and($nodes[1]->node_type)->toBe('paragraph')
        ->and($nodes[1]->level)->toBe(1)
        // Точка 1 within Алинея 1
        ->and($nodes[2]->path)->toBe('ЧЛ15/АЛ1/Т1')
        ->and($nodes[2]->node_type)->toBe('point')
        ->and($nodes[2]->level)->toBe(2)
        ->and($nodes[2]->text_markdown)->toContain('Лични документи включват')
        // Буква а) within Точка 1
        ->and($nodes[3]->path)->toBe('ЧЛ15/АЛ1/Т1/БУКВА_А')
        ->and($nodes[3]->node_type)->toBe('letter')
        ->and($nodes[3]->level)->toBe(3)
        ->and($nodes[3]->text_markdown)->toContain('лична карта')
        // Буква б) within Точка 1
        ->and($nodes[4]->path)->toBe('ЧЛ15/АЛ1/Т1/БУКВА_Б')
        ->and($nodes[4]->node_type)->toBe('letter')
        ->and($nodes[4]->level)->toBe(3)
        ->and($nodes[4]->text_markdown)->toContain('паспорт')
        // Буква в) within Точка 1
        ->and($nodes[5]->path)->toBe('ЧЛ15/АЛ1/Т1/БУКВА_В')
        ->and($nodes[5]->node_type)->toBe('letter')
        ->and($nodes[5]->level)->toBe(3)
        ->and($nodes[5]->text_markdown)->toContain('свидетелство за раждане')
        // Алинея 2
        ->and($nodes[6]->path)->toBe('ЧЛ15/АЛ2')
        ->and($nodes[6]->node_type)->toBe('paragraph')
        ->and($nodes[6]->level)->toBe(1)
        ->and($nodes[6]->text_markdown)->toContain('Допълнителни изисквания');
});

test('repeated captions get suffixed paths instead of colliding', function () {
    // Budget-law appendices restate "Чл. 1" per annex. Duplicate paths silently
    // overwrite each other in the export tree builder, so every occurrence must
    // get its own path.
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 5'],
            ['pId' => 2, 'caption' => 'Чл. 5'],
            ['pId' => 3, 'caption' => 'Чл. 5'],
        ],
        'content_text' => [
            'paragraphs' => [
                ['pId' => 1, 'text' => '<p>Първо приложение</p>', 'type' => 1],
                ['pId' => 2, 'text' => '<p>Второ приложение</p>', 'type' => 1],
                ['pId' => 3, 'text' => '<p>Трето приложение</p>', 'type' => 1],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $paths = $law->nodes()->orderBy('sort_order')->pluck('path');

    expect($paths->all())->toBe(['ЧЛ5', 'ЧЛ5-2', 'ЧЛ5-3'])
        ->and($paths->unique())->toHaveCount(3);
});

test('year references in parentheses are not parsed as алинеи', function () {
    // Amendment history like "(2011, 1989)" used to become ал. 2011 nodes and
    // truncate the real article text at the first year.
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 4а'],
        ],
        'content_text' => [
            'paragraphs' => [
                ['pId' => 1, 'text' => '<p>Мерките по този закон се прилагат съгласно резолюции (2011) и (1988) на Съвета за сигурност.</p>', 'type' => 1],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->processor->process($law);

    $nodes = $law->nodes;

    expect($nodes)->toHaveCount(1)
        ->and($nodes[0]->path)->toBe('ЧЛ4А')
        ->and($nodes[0]->text_markdown)->toContain('(2011)')
        ->and($nodes[0]->text_markdown)->toContain('(1988)');
});

test('a mid-process failure rolls back to the previous complete node set', function () {
    // The rebuild is delete-then-insert. Without a transaction a crash leaves a
    // partially rebuilt law with a stale processed_at, and the export ships it.
    $law = Law::factory()->create([
        'content_structure' => [['pId' => 1, 'caption' => 'Чл. 1']],
        'content_text' => ['paragraphs' => [['pId' => 1, 'text' => '<p>Ново</p>', 'type' => 1]]],
        'content_fetched_at' => now(),
        'processed_at' => now()->subDay(),
    ]);

    foreach ([['ЧЛ1', 1], ['ЧЛ2', 2]] as [$path, $sort]) {
        LawNode::create([
            'law_id' => $law->id,
            'path' => $path,
            'caption' => $path,
            'text_markdown' => 'от предишна обработка',
            'sort_order' => $sort,
        ]);
    }

    $builder = Mockery::mock(LawPathBuilder::class);
    $builder->shouldReceive('determineNodeType')->andThrow(new RuntimeException('boom'));

    $processor = new LawTreeProcessor($builder, app(LegalReferenceExtractor::class));

    expect(fn () => $processor->process($law))->toThrow(RuntimeException::class);

    expect($law->nodes()->count())->toBe(2)
        ->and($law->nodes()->orderBy('sort_order')->pluck('path')->all())->toBe(['ЧЛ1', 'ЧЛ2']);
});
