<?php

use App\Models\Law;

test('can retrieve a law as JSON', function () {
    $law = Law::factory()->create([
        'unique_id' => 12345,
        'caption' => 'ЗАКОН за тестване',
        'code' => '1234',
        'is_actual' => true,
    ]);

    $response = $this->get("/laws/{$law->id}");

    $response->assertSuccessful();
    $response->assertJson([
        'id' => $law->id,
        'unique_id' => 12345,
        'caption' => 'ЗАКОН за тестване',
        'code' => '1234',
        'is_actual' => true,
    ]);
});

test('can retrieve a law with content', function () {
    $law = Law::factory()->create([
        'unique_id' => 11110,
        'caption' => 'ЗАКОН за движението по пътищата',
        'content_structure' => [
            ['pId' => 334983, 'caption' => 'Глава първа'],
        ],
        'content_text' => [
            'paragraphs' => [
                ['pId' => 334979, 'text' => 'Test content'],
            ],
        ],
        'content_fetched_at' => now(),
    ]);

    $response = $this->get("/laws/{$law->id}");

    $response->assertSuccessful();
    $response->assertJsonFragment([
        'unique_id' => 11110,
        'caption' => 'ЗАКОН за движението по пътищата',
    ]);
    $response->assertJsonFragment([
        'content_structure' => [
            ['pId' => 334983, 'caption' => 'Глава първа'],
        ],
    ]);
    $response->assertJsonPath('content_text.paragraphs.0.text', 'Test content');
});

test('returns 404 for non-existent law', function () {
    $response = $this->get('/laws/99999');

    $response->assertNotFound();
});

test('law JSON includes all relevant fields', function () {
    $law = Law::factory()->create();

    $response = $this->get("/laws/{$law->id}");

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'id',
        'unique_id',
        'db_index',
        'caption',
        'func',
        'type',
        'base',
        'is_actual',
        'publ_date',
        'start_date',
        'end_date',
        'act_date',
        'publ_year',
        'is_connected',
        'has_content',
        'code',
        'dv',
        'original_id',
        'version',
        'celex',
        'doc_lead',
        'seria',
        'content_structure',
        'content_text',
        'content_fetched_at',
        'created_at',
        'updated_at',
    ]);
});
