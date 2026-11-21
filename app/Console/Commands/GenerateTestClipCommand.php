<?php

namespace App\Console\Commands;

use App\Models\Clipper\ClipperClip;
use App\Models\Clipper\ClipperHighlight;
use App\Models\Clipper\ClipperSession;
use App\Models\Clipper\ClipperStream;
use App\Models\Clipper\ClipperUpload;
use App\Models\Clipper\ClipperYoutubeAccount;
use Illuminate\Console\Command;

class GenerateTestClipCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clipper:generate-test-clip {stream_id? : Optional ID of the stream}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a test 9:16 highlight clip to verify Clips Library and YouTube upload flow';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $streamId = $this->argument('stream_id');
        $stream = $streamId ? ClipperStream::find($streamId) : ClipperStream::where('status', 'monitoring')->first();

        if (! $stream) {
            $stream = ClipperStream::latest()->first();
        }

        if (! $stream) {
            $stream = ClipperStream::create([
                'name' => 'GTA VI Gameplay Showcase Live',
                'source_url' => 'https://www.youtube.com/watch?v=sample_gtavi_live',
                'source_platform' => 'youtube',
                'status' => 'monitoring',
                'clip_min_seconds' => 20,
                'clip_max_seconds' => 60,
                'highlight_sensitivity' => 'medium',
                'vertical_enabled' => true,
                'captions_enabled' => true,
                'auto_upload_enabled' => true,
                'require_approval' => false,
                'upload_privacy' => 'public',
            ]);
            $this->info("Created default active stream: {$stream->name}");
        }

        $this->info("Using Stream: [{$stream->id}] {$stream->name}");

        // 1. Create a session record
        $session = ClipperSession::create([
            'stream_id' => $stream->id,
            'worker_id' => 'worker-cloud-cpu-01',
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'ended_at' => now(),
            'bytes_captured' => 104857600, // 100MB
            'highlights_count' => 1,
            'clips_count' => 1,
        ]);

        // 2. Create a highlight candidate
        $highlight = ClipperHighlight::create([
            'stream_id' => $stream->id,
            'session_id' => $session->id,
            'start_seconds' => 124.5,
            'end_seconds' => 158.7,
            'score' => 94.8,
            'signals' => [
                'audio_energy' => 0.92,
                'scene_change' => 0.89,
                'whisper_excitement' => 0.96,
            ],
            'status' => 'clipped',
        ]);

        // 3. Create the 9:16 vertical clip
        $titles = [
            'INSANE High-Speed Police Chase in Vice City! 🚨 #GTAVI #Shorts',
            'UNREAL Helicopter Stunt in Downtown Vice City! 🚁 #GTA6',
            'Craziest Wanted Level 5 Escape in GTA VI! 🔥 #Gaming',
        ];
        $selectedTitle = $titles[array_rand($titles)];

        $clip = ClipperClip::create([
            'stream_id' => $stream->id,
            'session_id' => $session->id,
            'highlight_id' => $highlight->id,
            'title' => $selectedTitle,
            'description' => "Unbelievable action captured live from GTA VI gameplay! #GTAVI #GTA6 #Shorts #Gaming #RockstarGames",
            'tags' => ['GTAVI', 'GTA6', 'Shorts', 'Gaming', 'ViceCity'],
            'duration' => 34.2,
            'aspect_ratio' => '9:16',
            'video_path' => 'clips/sample_gtavi_short.mp4',
            'thumbnail_path' => 'thumbnails/sample_gtavi_thumb.jpg',
            'captions_path' => 'captions/sample_gtavi.vtt',
            'storage_disk' => 'local',
            'score' => 94.8,
            'status' => 'ready',
            'approval_status' => $stream->require_approval ? 'pending' : 'approved',
            'file_size' => 18452100,
        ]);

        $this->info("Generated Clip ID: [{$clip->id}] - \"{$clip->title}\"");

        // 4. If auto-upload is enabled and YouTube is connected, enqueue upload
        $youtube = ClipperYoutubeAccount::where('is_active', true)->first();
        if ($youtube && ! $stream->require_approval) {
            $upload = ClipperUpload::create([
                'clip_id' => $clip->id,
                'status' => 'queued',
                'privacy' => $stream->upload_privacy ?? 'public',
                'attempts' => 0,
            ]);
            $this->info("Enqueued for YouTube Upload: ID [{$upload->id}]");
        }

        $this->info("Test clip successfully created! Visit /clipper/clips on your mobile browser to view it.");
        return Command::SUCCESS;
    }
}
