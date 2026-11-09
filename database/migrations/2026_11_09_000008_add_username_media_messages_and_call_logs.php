<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 60)->nullable()->unique()->after('name');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->string('type', 30)->default('text')->after('body');
            $table->text('attachment_url')->nullable()->after('type');
            $table->json('metadata')->nullable()->after('attachment_url');
        });

        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20)->default('voice'); // voice, video
            $table->string('status', 30)->default('outgoing'); // outgoing, incoming, missed, ended
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'attachment_url', 'metadata']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};

