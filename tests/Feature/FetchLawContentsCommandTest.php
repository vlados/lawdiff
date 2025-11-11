<?php

use App\Models\Law;
use Illuminate\Support\Facades\Http;

test('command fetches and stores law contents successfully', function () {
    $law = Law::factory()->create([
        'unique_id' => 11110,
        'db_index' => 0,
        'content_fetched_at' => null,
    ]);

    Http::fake([
        '*/DocContent*' => Http::response([
            [
                'pId' => 334983,
                'parId' => 5,
                'caption' => 'Глава първа ОБЩИ ПОЛОЖЕНИЯ',
                'childrenCount' => 4,
                'parentId' => 0,
                'children' => [],
            ],
        ], 200),
        '*/DocTextJson/*' => Http::response([
            'paragraphs' => [
                [
                    'pId' => 334979,
                    'type' => 0,
                    'fieldType' => 1,
                    'text' => '<h3>ЗАКОН за движението по пътищата</h3>',
                    'hasInLinks' => false,
                ],
            ],
        ], 200),
    ]);

    $this->artisan('laws:fetch-contents')
        ->expectsOutput('Starting to fetch law contents...')
        ->assertSuccessful();

    $law->refresh();

    expect($law->content_structure)->toBeArray()->not->toBeEmpty();
    expect($law->content_text)->toBeArray()->not->toBeEmpty();
    expect($law->content_fetched_at)->not->toBeNull();
});

test('command handles specific law ID', function () {
    $law1 = Law::factory()->create(['unique_id' => 1, 'content_fetched_at' => null]);
    $law2 = Law::factory()->create(['unique_id' => 2, 'content_fetched_at' => null]);

    Http::fake([
        '*/DocContent*' => Http::response([], 200),
        '*/DocTextJson/*' => Http::response(['paragraphs' => []], 200),
    ]);

    $this->artisan("laws:fetch-contents --law-id={$law1->id}")
        ->assertSuccessful();

    $law1->refresh();
    $law2->refresh();

    expect($law1->content_fetched_at)->not->toBeNull();
    expect($law2->content_fetched_at)->toBeNull();
});

test('command respects limit option', function () {
    Law::factory()->count(5)->create(['content_fetched_at' => null]);

    Http::fake([
        '*/DocContent*' => Http::response([], 200),
        '*/DocTextJson/*' => Http::response(['paragraphs' => []], 200),
    ]);

    $this->artisan('laws:fetch-contents --limit=3')
        ->assertSuccessful();

    expect(Law::whereNotNull('content_fetched_at')->count())->toBe(3);
    expect(Law::whereNull('content_fetched_at')->count())->toBe(2);
});

test('command skips laws with existing content and old dates by default', function () {
    $lawWithContent = Law::factory()->create([
        'content_fetched_at' => now(),
        'content_structure' => ['data' => 'existing'],
        'content_text' => ['data' => 'existing'],
        'publ_date' => now()->subYear(),
        'start_date' => now()->subYear(),
    ]);

    $lawWithoutContent = Law::factory()->create(['content_fetched_at' => null]);

    Http::fake([
        '*/DocContent*' => Http::response(['new' => 'data'], 200),
        '*/DocTextJson/*' => Http::response(['paragraphs' => ['new' => 'data']], 200),
    ]);

    $this->artisan('laws:fetch-contents')
        ->assertSuccessful();

    $lawWithContent->refresh();
    $lawWithoutContent->refresh();

    expect($lawWithContent->content_structure)->toBe(['data' => 'existing']);
    expect($lawWithoutContent->content_structure)->toBe(['new' => 'data']);
});

test('command refetches content for laws with future publication date', function () {
    $lawWithFuturePublDate = Law::factory()->create([
        'content_fetched_at' => now()->subDay(),
        'content_structure' => ['data' => 'old'],
        'content_text' => ['data' => 'old'],
        'publ_date' => now()->addWeek(),
        'start_date' => now()->subYear(),
    ]);

    Http::fake([
        '*/DocContent*' => Http::response(['data' => 'updated'], 200),
        '*/DocTextJson/*' => Http::response(['paragraphs' => ['data' => 'updated']], 200),
    ]);

    $this->artisan('laws:fetch-contents')
        ->assertSuccessful();

    $lawWithFuturePublDate->refresh();

    expect($lawWithFuturePublDate->content_structure)->toBe(['data' => 'updated']);
});

test('command refetches content for laws with future start date', function () {
    $lawWithFutureStartDate = Law::factory()->create([
        'content_fetched_at' => now()->subDay(),
        'content_structure' => ['data' => 'old'],
        'content_text' => ['data' => 'old'],
        'publ_date' => now()->subYear(),
        'start_date' => now()->addMonth(),
    ]);

    Http::fake([
        '*/DocContent*' => Http::response(['data' => 'updated'], 200),
        '*/DocTextJson/*' => Http::response(['paragraphs' => ['data' => 'updated']], 200),
    ]);

    $this->artisan('laws:fetch-contents')
        ->assertSuccessful();

    $lawWithFutureStartDate->refresh();

    expect($lawWithFutureStartDate->content_structure)->toBe(['data' => 'updated']);
});

test('command force refetches with force option', function () {
    $law = Law::factory()->create([
        'content_fetched_at' => now()->subDay(),
        'content_structure' => ['data' => 'old'],
        'content_text' => ['data' => 'old'],
    ]);

    Http::fake([
        '*/DocContent*' => Http::response(['data' => 'new'], 200),
        '*/DocTextJson/*' => Http::response(['paragraphs' => ['data' => 'new']], 200),
    ]);

    $this->artisan('laws:fetch-contents --force --limit=1')
        ->assertSuccessful();

    $law->refresh();

    expect($law->content_structure)->toBe(['data' => 'new']);
});

test('command handles API errors gracefully', function () {
    $law1 = Law::factory()->create(['unique_id' => 1, 'content_fetched_at' => null]);
    $law2 = Law::factory()->create(['unique_id' => 2, 'content_fetched_at' => null]);

    Http::fake([
        '*/DocContent*uniqueId=1*' => Http::response(null, 500),
        '*/DocContent*uniqueId=2*' => Http::response([], 200),
        '*/DocTextJson/*' => Http::response(['paragraphs' => []], 200),
    ]);

    $this->artisan('laws:fetch-contents')
        ->assertSuccessful();

    $law1->refresh();
    $law2->refresh();

    expect($law1->content_fetched_at)->toBeNull();
    expect($law2->content_fetched_at)->not->toBeNull();
});
