<?php

namespace Tests\Feature;

use App\Models\Clipper\ClipperStream;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClipperAdminScaffoldTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_clipper_login(): void
    {
        $response = $this->get('/clipper');

        $response->assertRedirect('/clipper/login');
    }

    public function test_non_admin_user_is_forbidden_from_clipper(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->get('/clipper');

        $response->assertStatus(403);
    }

    public function test_clipper_login_page_renders_successfully(): void
    {
        $response = $this->get('/clipper/login');

        $response->assertStatus(200);
        $response->assertSee('GTA VI');
        $response->assertSee('Admin Email');
    }

    public function test_admin_can_login_and_access_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@gtavi.local',
            'password' => bcrypt('secret123'),
            'is_admin' => true,
        ]);

        $response = $this->post('/clipper/login', [
            'email' => 'admin@gtavi.local',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/clipper');
        $this->assertAuthenticatedAs($admin);

        $dashboardResponse = $this->actingAs($admin)->get('/clipper');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Dashboard Summary');
        $dashboardResponse->assertSee('Active Monitoring');
        $dashboardResponse->assertSee('Capturing Footage');
        $dashboardResponse->assertSee('Clips Today');
    }

    public function test_non_admin_cannot_login_through_clipper_portal(): void
    {
        User::factory()->create([
            'email' => 'user@gtavi.local',
            'password' => bcrypt('secret123'),
            'is_admin' => false,
        ]);

        $response = $this->post('/clipper/login', [
            'email' => 'user@gtavi.local',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_access_all_mobile_scaffold_pages(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $stream = ClipperStream::create([
            'name' => 'GTA VI Launch Gameplay',
            'source_url' => 'https://www.youtube.com/watch?v=mock_video',
            'source_platform' => 'youtube',
            'clip_min_seconds' => 20,
            'clip_max_seconds' => 60,
            'highlight_sensitivity' => 'medium',
            'vertical_enabled' => true,
            'captions_enabled' => true,
            'upload_privacy' => 'private',
        ]);

        // Streams Index & Create & Show
        $this->actingAs($admin)->get('/clipper/streams')->assertStatus(200)->assertSee('Livestreams');
        $this->actingAs($admin)->get('/clipper/streams/create')->assertStatus(200)->assertSee('Configure New Livestream');
        $this->actingAs($admin)->get('/clipper/streams/' . $stream->id)->assertStatus(200)->assertSee('Clipping Parameters');

        $clip = \App\Models\Clipper\ClipperClip::create([
            'stream_id' => $stream->id,
            'title' => 'Test Highlight Clip',
            'duration' => 30.0,
            'score' => 90.0,
            'status' => 'ready',
            'approval_status' => 'pending',
        ]);

        // Clips Library & Show
        $this->actingAs($admin)->get('/clipper/clips')->assertStatus(200)->assertSee('Clips Library');
        $this->actingAs($admin)->get('/clipper/clips/' . $clip->id)->assertStatus(200)->assertSee('9:16 Vertical Video');

        // Upload Queue
        $this->actingAs($admin)->get('/clipper/uploads')->assertStatus(200)->assertSee('Upload Queue');

        // YouTube Settings
        $this->actingAs($admin)->get('/clipper/youtube')->assertStatus(200)->assertSee('YouTube Channel');

        // System Health
        $this->actingAs($admin)->get('/clipper/health')->assertStatus(200)->assertSee('System Health');

        // Logs
        $this->actingAs($admin)->get('/clipper/logs')->assertStatus(200)->assertSee('Stream Logs');
    }

    public function test_admin_can_create_and_manage_stream(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/clipper/streams', [
            'name' => 'Live GTA VI Stream',
            'source_url' => 'https://www.youtube.com/watch?v=test_stream_123',
            'clip_min_seconds' => 25,
            'clip_max_seconds' => 55,
            'highlight_sensitivity' => 'high',
            'vertical_enabled' => '1',
            'captions_enabled' => '1',
            'upload_privacy' => 'private',
        ]);

        $response->assertRedirect('/clipper/streams');
        $this->assertDatabaseHas('clipper_streams', ['name' => 'Live GTA VI Stream']);

        $stream = ClipperStream::where('name', 'Live GTA VI Stream')->first();

        // Toggle monitoring start
        $this->actingAs($admin)->post('/clipper/streams/' . $stream->id . '/toggle', ['action' => 'start'])
            ->assertSessionHas('status');
        $this->assertEquals('monitoring', $stream->fresh()->status);

        // List shows stream
        $this->actingAs($admin)->get('/clipper/streams')
            ->assertStatus(200)
            ->assertSee('Live GTA VI Stream');

        // Test capture highlight
        $captureResponse = $this->actingAs($admin)->post('/clipper/streams/' . $stream->id . '/capture-highlight');
        $captureResponse->assertRedirect('/clipper/clips');
        $this->assertDatabaseHas('clipper_clips', ['stream_id' => $stream->id]);
        $this->assertDatabaseHas('clipper_logs', ['stream_id' => $stream->id]);

        // Toggle monitoring stop
        $this->actingAs($admin)->post('/clipper/streams/' . $stream->id . '/toggle', ['action' => 'stop'])
            ->assertSessionHas('status');
        $this->assertEquals('idle', $stream->fresh()->status);

        // Delete stream
        $this->actingAs($admin)->delete('/clipper/streams/' . $stream->id)
            ->assertRedirect('/clipper/streams');
        $this->assertDatabaseMissing('clipper_streams', ['id' => $stream->id]);
    }

    public function test_admin_can_initiate_youtube_oauth(): void
    {
        config(['clipper.youtube.client_id' => 'test-google-client-id.apps.googleusercontent.com']);
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/clipper/youtube/connect');

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com/o/oauth2/v2/auth', $response->headers->get('Location'));
        $this->assertStringContainsString('test-google-client-id', $response->headers->get('Location'));
    }

    public function test_admin_logout_clears_session(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/clipper/logout');

        $response->assertRedirect('/clipper/login');
        $this->assertGuest();
    }

    public function test_worker_heartbeat_api_records_status_and_telemetry(): void
    {
        $response = $this->postJson('/api/clipper/heartbeat', [
            'worker_id' => 'worker-test-01',
            'endpoint' => 'cloud-worker.internal',
            'cpu_usage' => '15.4%',
            'memory_usage' => '42.1%',
            'disk_free' => '50 GB',
            'active_tasks' => 1,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'acknowledged']);

        $this->assertEquals('online', \App\Models\Clipper\ClipperSetting::get('worker_status'));
    }

    public function test_worker_active_streams_api_returns_monitoring_streams(): void
    {
        $activeStream = ClipperStream::create([
            'name' => 'Active Monitoring Stream',
            'source_url' => 'https://www.youtube.com/watch?v=active_stream',
            'source_platform' => 'youtube',
            'status' => 'monitoring',
        ]);

        ClipperStream::create([
            'name' => 'Idle Stream',
            'source_url' => 'https://www.youtube.com/watch?v=idle_stream',
            'source_platform' => 'youtube',
            'status' => 'idle',
        ]);

        $response = $this->getJson('/api/clipper/streams/active');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'streams');
        $response->assertJsonFragment(['name' => 'Active Monitoring Stream']);
    }

    public function test_worker_clip_created_api_creates_records_and_enqueues_upload(): void
    {
        $stream = ClipperStream::create([
            'name' => 'Highlight Source Stream',
            'source_url' => 'https://www.youtube.com/watch?v=highlight_stream',
            'source_platform' => 'youtube',
            'status' => 'monitoring',
            'require_approval' => false,
        ]);

        \App\Models\Clipper\ClipperYoutubeAccount::create([
            'channel_id' => 'UC_TEST_CHANNEL_123',
            'channel_title' => 'Test Channel',
            'access_token' => 'mock_token',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/clipper/clip-created', [
            'stream_id' => $stream->id,
            'title' => 'EPIC CAR CHASE MOMENT',
            'description' => 'Test clip description',
            'duration' => 32.5,
            'aspect_ratio' => '9:16',
            'score' => 96.2,
            'video_path' => '/storage/clips/test_clip.mp4',
            'thumbnail_path' => '/storage/clips/test_thumb.jpg',
            'start_seconds' => 45.0,
            'end_seconds' => 77.5,
            'file_size' => 12400000,
        ]);

        $response->assertStatus(201);
        $response->assertJson(['status' => 'success', 'approval_status' => 'approved']);

        $this->assertDatabaseHas('clipper_clips', [
            'stream_id' => $stream->id,
            'title' => 'EPIC CAR CHASE MOMENT',
        ]);

        $this->assertDatabaseHas('clipper_uploads', [
            'status' => 'queued',
        ]);
    }

    public function test_admin_can_simulate_worker_heartbeat(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/clipper/health/test-ping');
        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertEquals('online', \App\Models\Clipper\ClipperSetting::get('worker_status'));
    }
}
