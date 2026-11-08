<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile_and_avatar(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/profile', [
            'name' => 'Updated Name',
            'avatar' => 'avatar_1',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.avatar', 'avatar_1');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'avatar' => 'avatar_1',
        ]);
    }

    public function test_user_can_update_disappearing_messages_setting(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::create(['is_group' => false, 'disappearing_duration' => 'off']);
        $conversation->participants()->attach([$user1->id, $user2->id]);

        $response = $this->actingAs($user1, 'sanctum')
            ->patchJson("/api/conversations/{$conversation->id}/disappearing", [
                'disappearing_duration' => '1_hour',
            ]);

        $response->assertOk()
            ->assertJsonPath('conversation.disappearing_duration', '1_hour');

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'disappearing_duration' => '1_hour',
        ]);
    }

    public function test_disappearing_messages_are_cleaned_up_on_fetch(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::create(['is_group' => false, 'disappearing_duration' => '1_hour']);
        $conversation->participants()->attach([$user1->id, $user2->id]);

        // Old message (> 1 hour old)
        $oldMessage = $conversation->messages()->create([
            'user_id' => $user2->id,
            'body' => 'Old secret message',
            'read_at' => now()->subHours(2),
            'created_at' => now()->subHours(2),
        ]);

        // Recent message (< 1 hour old)
        $recentMessage = $conversation->messages()->create([
            'user_id' => $user2->id,
            'body' => 'Recent message',
            'created_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->getJson("/api/conversations/{$conversation->id}/messages");

        $response->assertOk();
        $this->assertDatabaseMissing('messages', ['id' => $oldMessage->id]);
        $this->assertDatabaseHas('messages', ['id' => $recentMessage->id]);
    }
}

