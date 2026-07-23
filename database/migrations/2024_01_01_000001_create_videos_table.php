<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('original_filename');
            $table->string('file_path'); // path to original uploaded file
            $table->string('disk')->default('local');
            $table->bigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->string('thumbnail_path')->nullable();
            $table->string('hls_path')->nullable(); // path to master .m3u8
            $table->json('qualities')->nullable(); // ['360p', '720p', '1080p']
            $table->json('processing_metadata')->nullable();
            $table->enum('status', [
                'uploading',
                'uploaded',
                'processing',
                'processing_failed',
                'ready',
                'deleted'
            ])->default('uploading');
            $table->text('error_message')->nullable();
            $table->integer('upload_progress')->default(0); // 0-100
            $table->string('upload_session_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};