<?php

namespace Tests\Feature;

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

        // Streams Index & Create & Show
        $this->actingAs($admin)->get('/clipper/streams')->assertStatus(200)->assertSee('Livestreams');
        $this->actingAs($admin)->get('/clipper/streams/create')->assertStatus(200)->assertSee('Configure New Livestream');
        $this->actingAs($admin)->get('/clipper/streams/1')->assertStatus(200)->assertSee('Clipping Parameters');

        // Clips Library & Show
        $this->actingAs($admin)->get('/clipper/clips')->assertStatus(200)->assertSee('Clips Library');
        $this->actingAs($admin)->get('/clipper/clips/1')->assertStatus(200)->assertSee('9:16 Vertical Video');

        // Upload Queue
        $this->actingAs($admin)->get('/clipper/uploads')->assertStatus(200)->assertSee('Upload Queue');

        // YouTube Settings
        $this->actingAs($admin)->get('/clipper/youtube')->assertStatus(200)->assertSee('YouTube Channel');

        // System Health
        $this->actingAs($admin)->get('/clipper/health')->assertStatus(200)->assertSee('System Health');

        // Logs
        $this->actingAs($admin)->get('/clipper/logs')->assertStatus(200)->assertSee('Stream Logs');
    }

    public function test_admin_logout_clears_session(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/clipper/logout');

        $response->assertRedirect('/clipper/login');
        $this->assertGuest();
    }
}
