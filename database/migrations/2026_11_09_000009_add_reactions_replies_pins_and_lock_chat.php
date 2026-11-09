<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->json('reactions')->nullable()->after('metadata');
            $table->json('reply_to')->nullable()->after('reactions');
            $table->boolean('is_pinned')->default(false)->after('reply_to');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('disappearing_duration');
            $table->string('lock_pin', 10)->nullable()->after('is_locked');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['is_locked', 'lock_pin']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['reactions', 'reply_to', 'is_pinned']);
        });
    }
};

