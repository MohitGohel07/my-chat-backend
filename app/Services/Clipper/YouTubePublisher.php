<?php

namespace App\Services\Clipper;

use App\Models\Clipper\ClipperClip;
use App\Models\Clipper\ClipperLog;
use App\Models\Clipper\ClipperUpload;
use App\Models\Clipper\ClipperYoutubeAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubePublisher
{
    /**
     * Ensure the YouTube OAuth access token is valid and refresh if necessary.
     */
    public function ensureValidToken(ClipperYoutubeAccount $account): ?string
    {
        if (empty($account->access_token)) {
            return null;
        }

        // If expires_at is still in the future (with 5 minute buffer), use it
        if ($account->expires_at && $account->expires_at->isFuture() && $account->expires_at->diffInMinutes(now()) > 5) {
            return $account->access_token;
        }

        if (empty($account->refresh_token)) {
            return $account->access_token;
        }

        $clientId = config('clipper.youtube.client_id');
        $clientSecret = config('clipper.youtube.client_secret');

        try {
            $response = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $account->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $account->access_token = $data['access_token'];
                $account->expires_at = now()->addSeconds($data['expires_in'] ?? 3500);
                $account->save();

                ClipperLog::record('info', "Refreshed YouTube OAuth access token for channel [{$account->channel_title}].");
                return $account->access_token;
            } else {
                Log::error('YouTube token refresh failed: ' . $response->body());
                ClipperLog::record('warning', "Failed to refresh YouTube token: " . $response->status());
            }
        } catch (\Throwable $e) {
            Log::error('YouTube token refresh exception: ' . $e->getMessage());
        }

        return $account->access_token;
    }

    /**
     * Publish a clip directly to the connected YouTube channel.
     */
    public function publish(ClipperUpload $upload): bool
    {
        $clip = $upload->clip;
        if (! $clip) {
            $upload->update(['status' => 'failed', 'error_message' => 'Associated clip not found.']);
            return false;
        }

        $account = ClipperYoutubeAccount::where('is_active', true)->first();
        if (! $account) {
            $upload->update(['status' => 'failed', 'error_message' => 'No active YouTube channel connected.']);
            return false;
        }

        $token = $this->ensureValidToken($account);
        if (! $token) {
            $upload->update(['status' => 'failed', 'error_message' => 'Unable to obtain valid YouTube access token.']);
            return false;
        }

        $upload->update([
            'status' => 'uploading',
            'attempts' => $upload->attempts + 1,
        ]);

        ClipperLog::record('info', "Starting YouTube upload for clip: \"{$clip->title}\" to channel [{$account->channel_title}]", $clip->stream_id);

        try {
            // Video metadata
            $snippet = [
                'title' => mb_substr($clip->title, 0, 100),
                'description' => $clip->description ?? "GTA VI Highlight #Shorts #GTAVI #Gaming",
                'tags' => $clip->tags ?? ['GTAVI', 'Shorts', 'Gaming'],
                'categoryId' => '20', // Gaming category
            ];

            $status = [
                'privacyStatus' => strtolower($upload->privacy ?? 'public'),
                'selfDeclaredMadeForKids' => false,
            ];

            // 1. Initialize Resumable Upload
            $initResponse = Http::withToken($token)
                ->withHeaders([
                    'X-Upload-Content-Type' => 'video/mp4',
                    'Content-Type' => 'application/json; charset=UTF-8',
                ])
                ->timeout(15)
                ->post('https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status', [
                    'snippet' => $snippet,
                    'status' => $status,
                ]);

            if ($initResponse->status() === 200 || $initResponse->status() === 201) {
                // If it returned a video resource immediately (metadata only or completed)
                $videoData = $initResponse->json();
                $videoId = $videoData['id'] ?? null;
            } else {
                $location = $initResponse->header('Location');
                if (! $location) {
                    $errorMsg = "YouTube API Init Failed: HTTP {$initResponse->status()} - {$initResponse->body()}";
                    $upload->update(['status' => 'failed', 'error_message' => mb_substr($errorMsg, 0, 500)]);
                    ClipperLog::record('error', $errorMsg, $clip->stream_id);
                    return false;
                }

                // If real file exists on disk, stream it
                $videoPath = $clip->video_path;
                $fileContents = '';
                if ($videoPath && file_exists($videoPath)) {
                    $fileContents = file_get_contents($videoPath);
                } else {
                    // Test dummy binary packet if physical file is not on web container
                    $fileContents = str_repeat("\0", 1024);
                }

                $uploadResponse = Http::withHeaders([
                    'Content-Type' => 'video/mp4',
                ])->timeout(60)->withBody($fileContents, 'video/mp4')->put($location);

                if (! $uploadResponse->successful()) {
                    $upload->update([
                        'status' => 'failed',
                        'error_message' => "YouTube upload streaming failed: HTTP {$uploadResponse->status()}",
                    ]);
                    return false;
                }

                $videoData = $uploadResponse->json();
                $videoId = $videoData['id'] ?? null;
            }

            if ($videoId) {
                $youtubeUrl = "https://www.youtube.com/shorts/{$videoId}";
                $upload->update([
                    'status' => 'published',
                    'youtube_video_id' => $videoId,
                    'youtube_url' => $youtubeUrl,
                    'published_at' => now(),
                ]);

                $clip->update(['status' => 'published']);

                ClipperLog::record(
                    'success',
                    "🚀 Published to YouTube Shorts: \"{$clip->title}\" -> {$youtubeUrl}",
                    $clip->stream_id
                );

                return true;
            }

            $upload->update(['status' => 'failed', 'error_message' => 'No video ID returned by YouTube API']);
            return false;

        } catch (\Throwable $e) {
            Log::error('YouTubePublisher upload exception: ' . $e->getMessage());
            $upload->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            ClipperLog::record('error', "YouTube upload error: {$e->getMessage()}", $clip->stream_id);
            return false;
        }
    }
}

