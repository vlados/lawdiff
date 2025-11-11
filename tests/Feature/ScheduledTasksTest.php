<?php

use Illuminate\Support\Facades\Schedule;

test('laws fetch command is scheduled daily', function () {
    $events = Schedule::events();

    $lawsFetchEvent = collect($events)->first(function ($event) {
        return str_contains($event->command ?? '', 'laws:fetch');
    });

    expect($lawsFetchEvent)->not->toBeNull();
    expect($lawsFetchEvent->expression)->toBe('0 0 * * *');
});

test('scheduled laws fetch command can run successfully', function () {
    $this->artisan('schedule:run')
        ->assertSuccessful();
});
