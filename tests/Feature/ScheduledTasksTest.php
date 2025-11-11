<?php

use Illuminate\Support\Facades\Schedule;

test('laws fetch command is scheduled daily at 1am', function () {
    $events = Schedule::events();

    $lawsFetchEvent = collect($events)->first(function ($event) {
        return str_contains($event->command ?? '', 'laws:fetch')
            && ! str_contains($event->command ?? '', 'laws:fetch-contents');
    });

    expect($lawsFetchEvent)->not->toBeNull();
    expect($lawsFetchEvent->expression)->toBe('0 1 * * *');
});

test('law contents fetch command is scheduled hourly', function () {
    $events = Schedule::events();

    $lawsContentsEvent = collect($events)->first(function ($event) {
        return str_contains($event->command ?? '', 'laws:fetch-contents');
    });

    expect($lawsContentsEvent)->not->toBeNull();
    expect($lawsContentsEvent->expression)->toBe('0 * * * *');
});

test('scheduled laws fetch command can run successfully', function () {
    $this->artisan('schedule:run')
        ->assertSuccessful();
});
