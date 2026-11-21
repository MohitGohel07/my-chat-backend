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
        Schema::table('clipper_youtube_accounts', function (Blueprint $table) {
            $table->string('channel_avatar')->nullable()->after('channel_title');
            $table->string('channel_url')->nullable()->after('channel_avatar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clipper_youtube_accounts', function (Blueprint $table) {
            $table->dropColumn(['channel_avatar', 'channel_url']);
        });
    }
};
