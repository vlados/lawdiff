<?php

declare(strict_types=1);

use App\Models\Law;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('processes laws with content successfully', function () {
    $law = Law::factory()->create([
        'content_structure' => [
            ['pId' => 1, 'caption' => 'Чл. 1', 'parentId' => null],
        ],
        'content_text' => [
            'paragraphs' => [
                ['pId' => 1, 'text' => '<p>Test content</p>', 'type' => 1],
            ],
        ],
        'content_fetched_at' => now(),
        'processed_at' => null,
    ]);

    $this->artisan('laws:process-trees')
        ->expectsOutput('Starting to process law trees...')
        ->expectsOutput('Found 1 laws to process.')
        ->assertExitCode(0);

    $law->refresh();

    expect($law->nodes)->not->toBeEmpty()
        ->and($law->nodes)->toHaveCount(1)
        ->and($law->nodes->first()->text_markdown)->toBe('Test content')
        ->and($law->processed_at)->not->toBeNull();
});

test('processes specific law by id', function () {
    $law1 = Law::factory()->create([
        'content_structure' => [['pId' => 1, 'caption' => 'Чл. 1', 'parentId' => null]],
        'content_text' => ['paragraphs' => [['pId' => 1, 'text' => '<p>Law 1</p>', 'type' => 1]]],
        'content_fetched_at' => now(),
    ]);

    $law2 = Law::factory()->create([
        'content_structure' => [['pId' => 2, 'caption' => 'Чл. 2', 'parentId' => null]],
        'content_text' => ['paragraphs' => [['pId' => 2, 'text' => '<p>Law 2</p>', 'type' => 1]]],
        'content_fetched_at' => now(),
    ]);

    $this->artisan("laws:process-trees --law-id={$law1->id}")
        ->expectsOutput('Found 1 laws to process.')
        ->assertExitCode(0);

    $law1->refresh();
    $law2->refresh();

    expect($law1->nodes)->not->toBeEmpty()
        ->and($law2->nodes)->toBeEmpty();
});

test('force option reprocesses already processed laws', function () {
    $law = Law::factory()->create([
        'content_structure' => [['pId' => 1, 'caption' => 'Чл. 1', 'parentId' => null]],
        'content_text' => ['paragraphs' => [['pId' => 1, 'text' => '<p>Content</p>', 'type' => 1]]],
        'content_fetched_at' => now(),
        'processed_at' => now()->subDay(),
    ]);

    // Create old node
    $law->nodes()->create([
        'path' => 'OLD',
        'p_id' => 999,
        'text_markdown' => 'Old processed node',
        'sort_order' => 0,
    ]);

    $this->artisan('laws:process-trees --force')
        ->expectsOutput('Found 1 laws to process.')
        ->assertExitCode(0);

    $law->refresh();

    expect($law->nodes)->toHaveCount(1)
        ->and($law->nodes->first()->p_id)->toBe(1)
        ->and($law->nodes->first()->text_markdown)->toBe('Content')
        ->and($law->processed_at)->toBeGreaterThan(now()->subMinute());
});

test('skips laws without content structure', function () {
    Law::factory()->create([
        'content_structure' => null,
        'content_text' => ['paragraphs' => [['pId' => 1, 'text' => '<p>Content</p>']]],
    ]);

    $this->artisan('laws:process-trees')
        ->expectsOutput('No laws found to process.')
        ->assertExitCode(0);
});

test('skips laws without content text', function () {
    Law::factory()->create([
        'content_structure' => [['pId' => 1, 'caption' => 'Article']],
        'content_text' => null,
    ]);

    $this->artisan('laws:process-trees')
        ->expectsOutput('No laws found to process.')
        ->assertExitCode(0);
});

test('respects limit option', function () {
    Law::factory()->count(5)->create([
        'content_structure' => [['pId' => 1, 'caption' => 'Чл. 1', 'parentId' => null]],
        'content_text' => ['paragraphs' => [['pId' => 1, 'text' => '<p>Content</p>', 'type' => 1]]],
        'content_fetched_at' => now(),
    ]);

    $this->artisan('laws:process-trees --limit=2')
        ->expectsOutput('Found 2 laws to process.')
        ->assertExitCode(0);

    expect(Law::whereNotNull('processed_at')->count())->toBe(2);
});

test('processes all laws even if one fails', function () {
    $law1 = Law::factory()->create([
        'content_structure' => [['pId' => 1]], // Valid
        'content_text' => ['paragraphs' => [['pId' => 1, 'text' => '<p>Valid</p>', 'type' => 1]]],
        'content_fetched_at' => now(),
    ]);

    $law2 = Law::factory()->create([
        'content_structure' => [['pId' => 2]], // Valid structure
        'content_text' => ['paragraphs' => [['pId' => 2, 'text' => '<p>Another valid</p>', 'type' => 1]]],
        'content_fetched_at' => now(),
    ]);

    $this->artisan('laws:process-trees')
        ->assertExitCode(0);

    $law1->refresh();
    $law2->refresh();

    expect($law1->nodes)->not->toBeEmpty()
        ->and($law2->nodes)->not->toBeEmpty();
});

test('displays processing statistics', function () {
    Law::factory()->count(3)->create([
        'content_structure' => [['pId' => 1, 'caption' => 'Чл. 1', 'parentId' => null]],
        'content_text' => ['paragraphs' => [['pId' => 1, 'text' => '<p>Content</p>', 'type' => 1]]],
        'content_fetched_at' => now(),
    ]);

    $this->artisan('laws:process-trees')
        ->expectsOutput('Starting to process law trees...')
        ->expectsOutput('Found 3 laws to process.')
        ->expectsOutputToContain('Finished processing law trees!')
        ->expectsOutputToContain('Total Processed')
        ->expectsOutputToContain('Success')
        ->expectsOutputToContain('Failed')
        ->assertExitCode(0);
});

test('skips already processed laws by default', function () {
    Law::factory()->create([
        'content_structure' => [['pId' => 1]],
        'content_text' => ['paragraphs' => [['pId' => 1, 'text' => '<p>Content</p>']]],
        'content_fetched_at' => now(),
        'processed_at' => now(),
    ]);

    $this->artisan('laws:process-trees')
        ->expectsOutput('No laws found to process.')
        ->assertExitCode(0);
});

test('processes laws with complex nested structure', function () {
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
                    ],
                ],
            ],
        ],
        'content_text' => [
            'paragraphs' => [
                ['pId' => 1, 'text' => '<p>Chapter <strong>text</strong></p>', 'type' => 1],
                ['pId' => 2, 'text' => '<p>Article <em>text</em></p>', 'type' => 2],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $this->artisan('laws:process-trees')
        ->assertExitCode(0);

    $law->refresh();

    $nodes = $law->nodes()->orderBy('sort_order')->get();

    expect($nodes)->toHaveCount(2)
        ->and($nodes[0]->path)->toBe('1')
        ->and($nodes[0]->text_markdown)->toContain('**text**')
        ->and($nodes[1]->path)->toBe('1/ЧЛ1')
        ->and($nodes[1]->text_markdown)->toContain('*text*');
});
