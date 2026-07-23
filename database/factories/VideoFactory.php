<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'original_filename' => fake()->slug() . '.mp4',
            'file_path' => 'uploads/' . fake()->uuid() . '/video.mp4',
            'disk' => 'local',
            'file_size' => fake()->numberBetween(1000000, 100000000),
            'mime_type' => 'video/mp4',
            'duration' => fake()->numberBetween(60, 3600),
            'thumbnail_path' => 'thumbnails/' . fake()->uuid() . '.jpg',
            'hls_path' => 'hls/' . fake()->uuid() . '/master.m3u8',
            'qualities' => ['360p', '720p', '1080p'],
            'status' => 'ready',
            'upload_progress' => 100,
            'upload_session_id' => fake()->uuid(),
        ];
    }

    public function uploading(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'uploading',
            'hls_path' => null,
            'thumbnail_path' => null,
            'qualities' => null,
            'duration' => null,
            'upload_progress' => fake()->numberBetween(10, 90),
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'hls_path' => null,
            'thumbnail_path' => null,
            'qualities' => null,
            'duration' => null,
            'upload_progress' => 100,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing_failed',
            'error_message' => fake()->sentence(),
            'hls_path' => null,
            'qualities' => null,
        ]);
    }
}