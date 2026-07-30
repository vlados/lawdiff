<?php

use App\Models\Law;
use Illuminate\Support\Facades\Http;

test('command fetches and stores laws successfully', function () {
    Http::fake([
        'web-api.apis.bg/api/obshtina-legislation/DocList' => Http::sequence()
            ->push([
                'currentPage' => 1,
                'totalPages' => 1,
                'totalCount' => 2,
                'currentCount' => 2,
                'data' => [
                    [
                        'uniqueId' => 10325,
                        'dbIndex' => 0,
                        'caption' => 'ЗАКОН за насърчаване на инвестициите',
                        'func' => 1,
                        'type' => 4,
                        'base' => 'NARH',
                        'isActual' => 1,
                        'publDate' => '2025-11-11T00:00:00+02:00',
                        'startDate' => '2025-11-11T00:00:00+02:00',
                        'endDate' => null,
                        'actDate' => '1997-10-24T00:00:00+03:00',
                        'publYear' => 2025,
                        'isConnected' => 1,
                        'hasContent' => 1,
                        'code' => '4110',
                        'dv' => 96,
                        'originalId' => null,
                        'version' => null,
                        'celex' => null,
                        'docLead' => null,
                        'seria' => null,
                    ],
                    [
                        'uniqueId' => 9339,
                        'dbIndex' => 0,
                        'caption' => 'ЗАКОН за посевния и посадъчния материал',
                        'func' => 1,
                        'type' => 4,
                        'base' => 'NARH',
                        'isActual' => 1,
                        'publDate' => '2025-11-07T00:00:00+02:00',
                        'startDate' => '2026-02-09T00:00:00+02:00',
                        'endDate' => null,
                        'actDate' => '2003-03-04T00:00:00+02:00',
                        'publYear' => 2025,
                        'isConnected' => 1,
                        'hasContent' => 1,
                        'code' => '40248',
                        'dv' => 95,
                        'originalId' => null,
                        'version' => null,
                        'celex' => null,
                        'docLead' => null,
                        'seria' => null,
                    ],
                ],
            ], 200)
            ->push([
                'currentPage' => 2,
                'totalPages' => 1,
                'totalCount' => 2,
                'currentCount' => 0,
                'data' => [],
            ], 200),
    ]);

    $this->artisan('laws:fetch')
        ->expectsOutput('Starting to fetch Bulgarian laws...')
        ->expectsOutput('Fetching page 1...')
        ->assertSuccessful();

    expect(Law::count())->toBe(2);

    $firstLaw = Law::where('unique_id', 10325)->first();
    expect($firstLaw)
        ->caption->toBe('ЗАКОН за насърчаване на инвестициите')
        ->code->toBe('4110')
        ->is_actual->toBeTrue()
        ->has_content->toBeTrue();
});

test('command handles pagination correctly', function () {
    Http::fake([
        'web-api.apis.bg/api/obshtina-legislation/DocList' => Http::sequence()
            ->push([
                'currentPage' => 1,
                'totalPages' => 2,
                'totalCount' => 3,
                'currentCount' => 2,
                'data' => [
                    [
                        'uniqueId' => 1,
                        'dbIndex' => 0,
                        'caption' => 'Test Law 1',
                        'func' => 1,
                        'type' => 4,
                        'base' => 'NARH',
                        'isActual' => 1,
                        'publDate' => '2025-11-11T00:00:00+02:00',
                        'startDate' => '2025-11-11T00:00:00+02:00',
                        'endDate' => null,
                        'actDate' => '1997-10-24T00:00:00+03:00',
                        'publYear' => 2025,
                        'isConnected' => 1,
                        'hasContent' => 1,
                        'code' => '1',
                        'dv' => 1,
                        'originalId' => null,
                        'version' => null,
                        'celex' => null,
                        'docLead' => null,
                        'seria' => null,
                    ],
                    [
                        'uniqueId' => 2,
                        'dbIndex' => 0,
                        'caption' => 'Test Law 2',
                        'func' => 1,
                        'type' => 4,
                        'base' => 'NARH',
                        'isActual' => 1,
                        'publDate' => '2025-11-11T00:00:00+02:00',
                        'startDate' => '2025-11-11T00:00:00+02:00',
                        'endDate' => null,
                        'actDate' => '1997-10-24T00:00:00+03:00',
                        'publYear' => 2025,
                        'isConnected' => 1,
                        'hasContent' => 1,
                        'code' => '2',
                        'dv' => 2,
                        'originalId' => null,
                        'version' => null,
                        'celex' => null,
                        'docLead' => null,
                        'seria' => null,
                    ],
                ],
            ], 200)
            ->push([
                'currentPage' => 2,
                'totalPages' => 2,
                'totalCount' => 3,
                'currentCount' => 1,
                'data' => [
                    [
                        'uniqueId' => 3,
                        'dbIndex' => 0,
                        'caption' => 'Test Law 3',
                        'func' => 1,
                        'type' => 4,
                        'base' => 'NARH',
                        'isActual' => 1,
                        'publDate' => '2025-11-11T00:00:00+02:00',
                        'startDate' => '2025-11-11T00:00:00+02:00',
                        'endDate' => null,
                        'actDate' => '1997-10-24T00:00:00+03:00',
                        'publYear' => 2025,
                        'isConnected' => 1,
                        'hasContent' => 1,
                        'code' => '3',
                        'dv' => 3,
                        'originalId' => null,
                        'version' => null,
                        'celex' => null,
                        'docLead' => null,
                        'seria' => null,
                    ],
                ],
            ], 200)
            ->push([
                'currentPage' => 3,
                'totalPages' => 2,
                'totalCount' => 3,
                'currentCount' => 0,
                'data' => [],
            ], 200),
    ]);

    $this->artisan('laws:fetch')
        ->expectsOutput('Fetching page 1...')
        ->expectsOutput('Fetching page 2...')
        ->assertSuccessful();

    expect(Law::count())->toBe(3);
});

test('command updates existing laws', function () {
    Law::factory()->create([
        'unique_id' => 10325,
        'caption' => 'Old Caption',
        'code' => 'OLD',
    ]);

    Http::fake([
        'web-api.apis.bg/api/obshtina-legislation/DocList' => Http::response([
            'currentPage' => 1,
            'totalPages' => 1,
            'totalCount' => 1,
            'currentCount' => 1,
            'data' => [
                [
                    'uniqueId' => 10325,
                    'dbIndex' => 0,
                    'caption' => 'Updated Caption',
                    'func' => 1,
                    'type' => 4,
                    'base' => 'NARH',
                    'isActual' => 1,
                    'publDate' => '2025-11-11T00:00:00+02:00',
                    'startDate' => '2025-11-11T00:00:00+02:00',
                    'endDate' => null,
                    'actDate' => '1997-10-24T00:00:00+03:00',
                    'publYear' => 2025,
                    'isConnected' => 1,
                    'hasContent' => 1,
                    'code' => 'NEW',
                    'dv' => 96,
                    'originalId' => null,
                    'version' => null,
                    'celex' => null,
                    'docLead' => null,
                    'seria' => null,
                ],
            ],
        ], 200),
    ]);

    $this->artisan('laws:fetch')->assertSuccessful();

    expect(Law::count())->toBe(1);

    $law = Law::where('unique_id', 10325)->first();
    expect($law)
        ->caption->toBe('Updated Caption')
        ->code->toBe('NEW');
});

test('command fails when the API errors so the pipeline stops before export', function () {
    Http::fake([
        'web-api.apis.bg/api/obshtina-legislation/DocList' => Http::response(null, 500),
    ]);

    $this->artisan('laws:fetch')
        ->expectsOutput('Starting to fetch Bulgarian laws...')
        ->expectsOutputToContain('Law list fetch is incomplete')
        ->assertExitCode(1);

    expect(Law::count())->toBe(0);
});

test('command fails on a mid-pagination error but keeps the laws already fetched', function () {
    // A partial list must fail the run: the export step prunes files for laws
    // missing from the database, so a silently truncated list deletes data.
    $lawRow = fn (int $id) => [
        'uniqueId' => $id, 'dbIndex' => 0, 'caption' => "ЗАКОН {$id}", 'func' => 1,
        'type' => 4, 'base' => 'NARH', 'isActual' => 1,
        'publDate' => '2025-11-11T00:00:00+02:00', 'startDate' => '2025-11-11T00:00:00+02:00',
        'endDate' => null, 'actDate' => '1997-10-24T00:00:00+03:00', 'publYear' => 2025,
        'isConnected' => 1, 'hasContent' => 1, 'code' => (string) $id, 'dv' => 1,
        'originalId' => null, 'version' => null, 'celex' => null, 'docLead' => null, 'seria' => null,
    ];

    Http::fake([
        'web-api.apis.bg/api/obshtina-legislation/DocList' => Http::sequence()
            ->push(['totalCount' => 4, 'data' => [$lawRow(1), $lawRow(2)]])
            ->push(null, 500),
    ]);

    $this->artisan('laws:fetch --page-size=2')->assertExitCode(1);

    expect(Law::count())->toBe(2);
});
