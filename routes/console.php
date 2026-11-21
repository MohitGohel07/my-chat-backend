<?php

use App\Models\Clipper\ClipperSetting;
use App\Models\Clipper\ClipperUpload;
use App\Services\Clipper\YouTubePublisher;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| GTA VI Auto Clipper Scheduler & Commands
|--------------------------------------------------------------------------
*/
Artisan::command('clipper:process-uploads', function (YouTubePublisher $publisher) {
    $autoUpload = (bool) ClipperSetting::get('auto_upload_enabled', config('clipper.upload_enabled', false));
    if (! $autoUpload) {
        $this->info('Auto upload is disabled in settings.');
        return;
    }

    $queued = ClipperUpload::where('status', 'queued')
        ->where('attempts', '<', 3)
        ->take(2)
        ->get();

    if ($queued->isEmpty()) {
        $this->info('No queued uploads.');
        return;
    }

    foreach ($queued as $upload) {
        $this->info("Processing upload ID {$upload->id}...");
        $publisher->publish($upload);
    }
})->purpose('Process pending clips in the YouTube upload queue');

Schedule::command('clipper:process-uploads')->everyMinute();

