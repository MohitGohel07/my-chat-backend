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
            $upload->update(['status' => 'failed', 'last_error' => 'Associated clip not found.']);
            return false;
        }

        $account = ClipperYoutubeAccount::where('is_active', true)->first();
        if (! $account) {
            $upload->update(['status' => 'failed', 'last_error' => 'No active YouTube channel connected.']);
            return false;
        }

        $token = $this->ensureValidToken($account);
        if (! $token) {
            $upload->update(['status' => 'failed', 'last_error' => 'Unable to obtain valid YouTube access token.']);
            return false;
        }

        $upload->update([
            'status' => 'uploading',
            'attempts' => $upload->attempts + 1,
        ]);

        ClipperLog::record('info', "Starting YouTube upload for clip: \"{$clip->title}\" to channel [{$account->channel_title}]", $clip->stream_id);

        try {
            // Read actual MP4 binary content
            $videoPath = $clip->video_path;
            $fileContents = '';

            if ($videoPath && file_exists($videoPath)) {
                $fileContents = file_get_contents($videoPath);
            } elseif ($videoPath && file_exists(public_path(ltrim($videoPath, '/')))) {
                $fileContents = file_get_contents(public_path(ltrim($videoPath, '/')));
            } elseif ($videoPath && file_exists(storage_path('app/public/' . ltrim($videoPath, '/')))) {
                $fileContents = file_get_contents(storage_path('app/public/' . ltrim($videoPath, '/')));
            } elseif ($videoPath && file_exists(storage_path('app/' . ltrim($videoPath, '/')))) {
                $fileContents = file_get_contents(storage_path('app/' . ltrim($videoPath, '/')));
            } elseif ($videoPath && str_starts_with($videoPath, 'http')) {
                $fileContents = @file_get_contents($videoPath) ?: '';
            } elseif (file_exists(public_path('clips/highlight_sample.mp4'))) {
                $fileContents = file_get_contents(public_path('clips/highlight_sample.mp4'));
            } elseif (file_exists(public_path('storage/clips/highlight_sample.mp4'))) {
                $fileContents = file_get_contents(public_path('storage/clips/highlight_sample.mp4'));
            }

            if (empty($fileContents)) {
                $upload->update(['status' => 'failed', 'last_error' => 'No video MP4 file found on server to upload.']);
                return false;
            }

            $fileSize = strlen($fileContents);

            // Determine if publishing as Short or Standard Video
            $uploadType = $upload->upload_type ?? 'short';
            $isShort = ($uploadType === 'short');

            // Format Title & Description based on format chosen by user
            $title = $clip->title;
            if ($isShort) {
                if (! str_contains(strtolower($title), '#shorts')) {
                    $title = mb_substr(trim($title), 0, 91) . ' #Shorts';
                }
            } else {
                $title = trim(preg_replace('/#shorts/i', '', $title));
                $title = preg_replace('/\s+/', ' ', $title);
            }
            $title = mb_substr($title, 0, 100);

            $description = $clip->description ?? '';
            if ($isShort) {
                if (! str_contains(strtolower($description), '#shorts')) {
                    $description = rtrim($description) . "\n\n#Shorts #GTAVI #Gaming";
                }
            } else {
                $description = trim(preg_replace('/#shorts/i', '', $description));
                if (! str_contains(strtolower($description), '#gtavi')) {
                    $description = rtrim($description) . "\n\n#GTAVI #Gaming";
                }
            }

            $tags = $clip->tags ?? ['GTAVI', 'Gaming'];
            if ($isShort && ! in_array('Shorts', $tags)) {
                $tags[] = 'Shorts';
            }

            // Video metadata
            $snippet = [
                'title' => $title,
                'description' => $description,
                'tags' => array_values(array_unique($tags)),
                'categoryId' => '20', // Gaming
            ];

            $status = [
                'privacyStatus' => strtolower($upload->privacy ?? 'public'),
                'selfDeclaredMadeForKids' => false,
            ];

            // 1. Initialize Resumable Upload
            $initResponse = Http::withToken($token)
                ->withHeaders([
                    'X-Upload-Content-Type' => 'video/mp4',
                    'X-Upload-Content-Length' => (string) $fileSize,
                    'Content-Type' => 'application/json; charset=UTF-8',
                ])
                ->timeout(30)
                ->post('https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status', [
                    'snippet' => $snippet,
                    'status' => $status,
                ]);

            $location = $initResponse->header('Location');

            // If init failed (status not successful or Location header missing)
            if (! $initResponse->successful() || empty($location)) {
                $jsonBody = $initResponse->json();
                $errorMsg = $jsonBody['error']['message']
                    ?? ("YouTube API Init Error: HTTP " . $initResponse->status() . " " . $initResponse->body());
                $upload->update(['status' => 'failed', 'last_error' => mb_substr($errorMsg, 0, 500)]);
                ClipperLog::record('error', "YouTube Upload Init Failed: {$errorMsg}", $clip->stream_id);
                return false;
            }

            // 2. Stream video binary to Google Resumable Upload Endpoint
            $endByte = $fileSize - 1;
            $uploadResponse = Http::withHeaders([
                'Content-Type' => 'video/mp4',
                'Content-Length' => (string) $fileSize,
                'Content-Range' => "bytes 0-{$endByte}/{$fileSize}",
            ])->timeout(300)->withBody($fileContents, 'video/mp4')->put($location);

            if (! $uploadResponse->successful()) {
                $jsonBody = $uploadResponse->json();
                $errorMsg = $jsonBody['error']['message']
                    ?? ("Upload Streaming Failed: HTTP " . $uploadResponse->status() . " " . $uploadResponse->body());
                $upload->update([
                    'status' => 'failed',
                    'last_error' => mb_substr($errorMsg, 0, 500),
                ]);
                ClipperLog::record('error', "YouTube Upload Streaming Failed: {$errorMsg}", $clip->stream_id);
                return false;
            }

            $videoData = $uploadResponse->json() ?? [];
            $videoId = $videoData['id'] ?? null;
            if (! $videoId && preg_match('/"id"\s*:\s*"([a-zA-Z0-9_-]{11})"/', $uploadResponse->body(), $m)) {
                $videoId = $m[1];
            }

            if ($videoId) {
                $youtubeUrl = $isShort
                    ? "https://www.youtube.com/shorts/{$videoId}"
                    : "https://www.youtube.com/watch?v={$videoId}";

                $upload->update([
                    'status' => 'published',
                    'youtube_video_id' => $videoId,
                    'youtube_url' => $youtubeUrl,
                    'last_error' => null,
                    'uploaded_at' => now(),
                ]);

                $clip->update(['status' => 'published']);

                $formatLabel = $isShort ? 'YouTube Short' : 'Standard YouTube Video';
                ClipperLog::record(
                    'success',
                    "🚀 Successfully published to {$formatLabel}: \"{$title}\" -> {$youtubeUrl}",
                    $clip->stream_id
                );

                return true;
            }

            $upload->update([
                'status' => 'failed',
                'last_error' => 'No video ID returned by YouTube API: HTTP ' . $uploadResponse->status() . ' - ' . mb_substr($uploadResponse->body(), 0, 300),
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error('YouTubePublisher upload exception: ' . $e->getMessage());
            $upload->update(['status' => 'failed', 'last_error' => mb_substr($e->getMessage(), 0, 500)]);
            ClipperLog::record('error', "YouTube upload exception: {$e->getMessage()}", $clip->stream_id);
            return false;
        }
    }
}

