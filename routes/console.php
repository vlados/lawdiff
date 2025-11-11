<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('laws:fetch')
    ->dailyAt('01:00')
    ->onSuccess(function () {
        info('Bulgarian laws updated successfully');
    })
    ->onFailure(function () {
        info('Failed to update Bulgarian laws');
    });

Schedule::command('laws:fetch-contents --limit=50')
    ->hourly()
    ->onSuccess(function () {
        info('Law contents fetched successfully');
    })
    ->onFailure(function () {
        info('Failed to fetch law contents');
    });
