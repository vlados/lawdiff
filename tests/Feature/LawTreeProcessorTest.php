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

    expect($nodes)->toHaveCount(3)
        ->and($nodes[0]->path)->toBe('1')
        ->and($nodes[0]->level)->toBe(0)
        ->and($nodes[1]->path)->toBe('1/ЧЛ1')
        ->and($nodes[1]->level)->toBe(1)
        ->and($nodes[2]->path)->toBe('1/ЧЛ1/АЛ1')
        ->and($nodes[2]->level)->toBe(2);
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
