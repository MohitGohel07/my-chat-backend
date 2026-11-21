<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Streams Table
        Schema::create('clipper_streams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('source_url');
            $table->string('source_platform')->default('youtube');
            $table->string('status')->default('idle')->index(); // idle, monitoring, capturing, paused, error
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('clip_min_seconds')->default(20);
            $table->unsignedInteger('clip_max_seconds')->default(60);
            $table->unsignedInteger('pre_roll_seconds')->default(10);
            $table->unsignedInteger('post_roll_seconds')->default(15);
            $table->string('highlight_sensitivity')->default('medium');
            $table->boolean('vertical_enabled')->default(true);
            $table->boolean('captions_enabled')->default(true);
            $table->string('caption_language')->default('auto');
            $table->boolean('auto_upload_enabled')->default(false);
            $table->boolean('require_approval')->default(true);
            $table->string('upload_privacy')->default('private');
            $table->unsignedInteger('max_clips_per_day')->default(5);
            $table->timestamp('monitoring_started_at')->nullable();
            $table->timestamp('monitoring_stopped_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        // 2. YouTube Accounts Table (OAuth credentials & tokens)
        Schema::create('clipper_youtube_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('channel_id')->nullable()->index();
            $table->string('channel_title')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->string('token_type')->default('Bearer');
            $table->timestamp('expires_at')->nullable();
            $table->text('scope')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Stream Sessions
        Schema::create('clipper_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained('clipper_streams')->cascadeOnDelete();
            $table->string('worker_id')->nullable();
            $table->string('status')->default('active')->index(); // active, stopped, completed, failed
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('bytes_captured')->default(0);
            $table->unsignedInteger('highlights_count')->default(0);
            $table->unsignedInteger('clips_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // 4. Highlight Candidates
        Schema::create('clipper_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained('clipper_streams')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('clipper_sessions')->nullOnDelete();
            $table->double('start_seconds');
            $table->double('end_seconds');
            $table->double('score')->default(0);
            $table->json('signals')->nullable();
            $table->string('status')->default('detected')->index(); // detected, processing, clipped, discarded
            $table->timestamps();
        });

        // 5. Rendered Clips
        Schema::create('clipper_clips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained('clipper_streams')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('clipper_sessions')->nullOnDelete();
            $table->foreignId('highlight_id')->nullable()->constrained('clipper_highlights')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->double('duration')->default(0);
            $table->string('aspect_ratio')->default('9:16');
            $table->string('video_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('captions_path')->nullable();
            $table->string('storage_disk')->default('local');
            $table->double('score')->default(0);
            $table->string('status')->default('ready')->index(); // pending_render, rendering, ready, failed
            $table->string('approval_status')->default('pending')->index(); // pending, approved, rejected
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });

        // 6. Upload Queue
        Schema::create('clipper_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clip_id')->constrained('clipper_clips')->cascadeOnDelete();
            $table->string('youtube_video_id')->nullable()->index();
            $table->string('youtube_url')->nullable();
            $table->string('status')->default('queued')->index(); // queued, uploading, processing, published, failed
            $table->string('privacy')->default('private');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });

        // 7. Key-Value Settings
        Schema::create('clipper_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->longText('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clipper_uploads');
        Schema::dropIfExists('clipper_clips');
        Schema::dropIfExists('clipper_highlights');
        Schema::dropIfExists('clipper_sessions');
        Schema::dropIfExists('clipper_youtube_accounts');
        Schema::dropIfExists('clipper_streams');
        Schema::dropIfExists('clipper_settings');
    }
};

