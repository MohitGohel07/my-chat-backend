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
            'username' => 'updated_user',
            'phone' => '+919876543210',
            'avatar' => 'avatar_1',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.username', 'updated_user')
            ->assertJsonPath('user.phone', '+919876543210')
            ->assertJsonPath('user.avatar', 'avatar_1');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'username' => 'updated_user',
            'phone' => '+919876543210',
        ]);
    }

    public function test_users_list_is_empty_without_search_and_finds_by_phone_or_username(): void
    {
        $me = User::factory()->create();
        $target = User::factory()->create([
            'name' => 'Alice Johnson',
            'username' => 'alice_j',
            'phone' => '+919999888877',
            'email' => 'alice@test.com',
        ]);

        // Empty search returns empty for privacy
        $response = $this->actingAs($me, 'sanctum')->getJson('/api/users');
        $response->assertOk()->assertExactJson([]);

        // Search by username
        $resUsername = $this->actingAs($me, 'sanctum')->getJson('/api/users?search=alice_j');
        $resUsername->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $target->id);

        // Search by phone
        $resPhone = $this->actingAs($me, 'sanctum')->getJson('/api/users?search=9999888877');
        $resPhone->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $target->id);
    }

    public function test_user_can_send_media_reply_and_contact_messages(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::create(['is_group' => false]);
        $conversation->participants()->attach([$user1->id, $user2->id]);

        // Image message with reply
        $response = $this->actingAs($user1, 'sanctum')->postJson("/api/conversations/{$conversation->id}/messages", [
            'body' => 'Look at this photo',
            'type' => 'image',
            'attachment_url' => 'https://example.com/photo.jpg',
            'reply_to' => ['sender_name' => 'Alice', 'body' => 'Original text'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('type', 'image')
            ->assertJsonPath('attachment_url', 'https://example.com/photo.jpg')
            ->assertJsonPath('reply_to.sender_name', 'Alice');

        // Contact message
        $resContact = $this->actingAs($user1, 'sanctum')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'contact',
            'metadata' => ['name' => 'Bob Smith', 'phone' => '+919123456789'],
        ]);

        $resContact->assertCreated()
            ->assertJsonPath('type', 'contact')
            ->assertJsonPath('metadata.name', 'Bob Smith');
    }

    public function test_user_can_react_pin_and_delete_single_message(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::create(['is_group' => false]);
        $conversation->participants()->attach([$user1->id, $user2->id]);

        $msg = $conversation->messages()->create([
            'user_id' => $user2->id,
            'body' => 'Hello there',
        ]);

        // React with heart
        $resReact = $this->actingAs($user1, 'sanctum')
            ->postJson("/api/conversations/{$conversation->id}/messages/{$msg->id}/react", [
                'emoji' => '❤️',
            ]);

        $resReact->assertOk()
            ->assertJsonPath('reactions.❤️.0', $user1->id);

        // Pin message
        $resPin = $this->actingAs($user1, 'sanctum')
            ->patchJson("/api/conversations/{$conversation->id}/messages/{$msg->id}/pin");

        $resPin->assertOk()->assertJsonPath('is_pinned', true);

        // Delete single message
        $resDel = $this->actingAs($user1, 'sanctum')
            ->deleteJson("/api/conversations/{$conversation->id}/messages/{$msg->id}");

        $resDel->assertOk();
        $this->assertDatabaseMissing('messages', ['id' => $msg->id]);
    }

    public function test_user_can_lock_and_unlock_conversation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::create(['is_group' => false]);
        $conversation->participants()->attach([$user1->id, $user2->id]);

        $res = $this->actingAs($user1, 'sanctum')
            ->patchJson("/api/conversations/{$conversation->id}/lock", [
                'is_locked' => true,
                'lock_pin' => '1234',
            ]);

        $res->assertOk()
            ->assertJsonPath('conversation.is_locked', true);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'is_locked' => true,
            'lock_pin' => '1234',
        ]);
    }

    public function test_user_can_log_and_fetch_calls(): void
    {
        $caller = User::factory()->create();
        $receiver = User::factory()->create();

        $responseVoice = $this->actingAs($caller, 'sanctum')->postJson('/api/calls', [
            'receiver_id' => $receiver->id,
            'type' => 'voice',
            'status' => 'ended',
            'duration_seconds' => 45,
        ]);

        $responseVoice->assertCreated()
            ->assertJsonPath('type', 'voice')
            ->assertJsonPath('status', 'ended')
            ->assertJsonPath('duration_seconds', 45);

        $responseVideo = $this->actingAs($caller, 'sanctum')->postJson('/api/calls', [
            'receiver_id' => $receiver->id,
            'type' => 'video',
            'status' => 'ended',
            'duration_seconds' => 120,
        ]);

        $responseVideo->assertCreated()
            ->assertJsonPath('type', 'video')
            ->assertJsonPath('status', 'ended')
            ->assertJsonPath('duration_seconds', 120);

        $list = $this->actingAs($caller, 'sanctum')->getJson('/api/calls');
        $list->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_live_call_signaling_lifecycle(): void
    {
        $caller = User::factory()->create(['name' => 'Alice']);
        $receiver = User::factory()->create(['name' => 'Bob']);

        // 1. Caller initiates video call
        $initRes = $this->actingAs($caller, 'sanctum')->postJson('/api/calls/initiate', [
            'receiver_id' => $receiver->id,
            'type' => 'video',
        ]);
        $initRes->assertCreated()
            ->assertJsonPath('call.status', 'ringing')
            ->assertJsonPath('call.type', 'video');
        $callId = $initRes->json('call.id');

        // 2. Receiver polls incoming calls and finds the ringing call
        $incRes = $this->actingAs($receiver, 'sanctum')->getJson('/api/calls/incoming');
        $incRes->assertOk()
            ->assertJsonPath('call.id', $callId)
            ->assertJsonPath('call.status', 'ringing')
            ->assertJsonPath('call.caller.name', 'Alice');

        // 3. Receiver accepts the call
        $acceptRes = $this->actingAs($receiver, 'sanctum')->postJson("/api/calls/{$callId}/accept");
        $acceptRes->assertOk()
            ->assertJsonPath('call.status', 'accepted');

        // 4. Caller polls status and sees accepted!
        $statusRes = $this->actingAs($caller, 'sanctum')->getJson("/api/calls/{$callId}/status");
        $statusRes->assertOk()
            ->assertJsonPath('call.status', 'accepted');

        // 5. Either party ends the call
        $endRes = $this->actingAs($caller, 'sanctum')->postJson("/api/calls/{$callId}/end", [
            'duration_seconds' => 75,
        ]);
        $endRes->assertOk()
            ->assertJsonPath('call.status', 'ended')
            ->assertJsonPath('call.duration_seconds', 75);
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
