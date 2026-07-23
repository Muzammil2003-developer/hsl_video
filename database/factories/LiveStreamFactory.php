<?php

namespace Database\Factories;

use App\Models\LiveStream;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LiveStreamFactory extends Factory
{
    protected $model = LiveStream::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'stream_key' => LiveStream::generateStreamKey(),
            'rtmp_url' => 'rtmp://localhost:1935/live',
            'hls_playlist_url' => 'http://localhost:8888/live/' . fake()->uuid() . '.m3u8',
            'status' => 'scheduled',
            'viewer_count' => 0,
            'peak_viewer_count' => 0,
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 week'),
            'category' => fake()->randomElement(['Gaming', 'Music', 'Education', 'Tech', 'Entertainment']),
        ];
    }

    public function live(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'live',
            'started_at' => now(),
            'viewer_count' => fake()->numberBetween(10, 500),
            'peak_viewer_count' => fake()->numberBetween(50, 1000),
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ended',
            'started_at' => fake()->dateTimeBetween('-1 week', '-1 hour'),
            'ended_at' => now(),
        ]);
    }
}