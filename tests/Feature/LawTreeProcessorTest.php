<?php

declare(strict_types=1);

use App\Models\Law;
use App\Services\LawTreeProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->processor = new LawTreeProcessor;
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

    // Chapter nodes are skipped, so only article and paragraph are saved
    expect($nodes)->toHaveCount(2)
        ->and($nodes[0]->path)->toBe('ЧЛ1')
        ->and($nodes[0]->level)->toBe(0)
        ->and($nodes[0]->caption)->toBe('Чл. 1')
        ->and($nodes[1]->path)->toBe('ЧЛ1/АЛ1')
        ->and($nodes[1]->level)->toBe(1)
        ->and($nodes[1]->caption)->toBe('Ал. 1');
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
