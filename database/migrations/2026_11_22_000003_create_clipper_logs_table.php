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
        Schema::create('clipper_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->nullable()->constrained('clipper_streams')->nullOnDelete();
            $table->string('level')->default('info')->index(); // info, success, warning, error
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clipper_logs');
    }
};
