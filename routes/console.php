<?php

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\Leads\ImportTrackDriveData;
use App\Console\Commands\Leads\ReprocessFailedCalls;
use App\Console\Commands\Leads\ImportTrackDriveLeads;
use App\Console\Commands\Leads\ReprocessFailedTranscript;

// Programación de comandos
Schedule::command(ImportTrackDriveData::class)
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

Schedule::command(ImportTrackDriveLeads::class, [env('TRACKDRIVE_PROVIDER_ID')])
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

Schedule::command(ReprocessFailedCalls::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

Schedule::command(ReprocessFailedTranscript::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();
