<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('stream_key', 64)->unique()->index();
            $table->string('rtmp_url')->nullable();
            $table->string('hls_playlist_url')->nullable(); // e.g., http://server/hls/live/{stream_key}.m3u8
            $table->string('thumbnail_path')->nullable();
            $table->enum('status', [
                'scheduled',
                'live',
                'ended',
                'archived',
            ])->default('scheduled');
            $table->integer('viewer_count')->default(0);
            $table->integer('peak_viewer_count')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('category')->nullable();
            $table->json('stream_metadata')->nullable();
            $table->boolean('auto_detect_status')->default(true);
            $table->text('chat_embed_url')->nullable(); // optional third-party chat embed
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_streams');
    }
};