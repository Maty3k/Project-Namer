<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UploadedLogo>
 */
class UploadedLogoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileTypes = [
            ['svg+xml', 'svg', 'logo-design.svg'],
            ['png', 'png', 'company-logo.png'],
            ['jpeg', 'jpg', 'brand-logo.jpg'],
        ];

        $selectedType = $this->faker->randomElement($fileTypes);
        $mimeType = 'image/'.$selectedType[0];
        $extension = $selectedType[1];
        $filename = $selectedType[2];

        return [
            'session_id' => $this->faker->uuid(),
            'user_id' => $this->faker->optional(0.3)->passthrough(User::factory()),
            'original_name' => $filename,
            'file_path' => 'logos/uploaded/'.uniqid().'.'.$extension,
            'file_size' => $this->faker->numberBetween(10000, 500000), // 10KB to 500KB
            'mime_type' => $mimeType,
            'image_width' => $this->faker->numberBetween(100, 2048),
            'image_height' => $this->faker->numberBetween(100, 2048),
            'category' => $this->faker->optional(0.6)->randomElement(['brand', 'icon', 'wordmark', 'symbol']),
            'description' => $this->faker->optional(0.4)->sentence(),
        ];
    }

    /**
     * Create an SVG uploaded logo.
     */
    public function svg(): static
    {
        return $this->state([
            'mime_type' => 'image/svg+xml',
            'original_name' => $this->faker->word().'.svg',
            'file_path' => 'logos/uploaded/'.uniqid().'.svg',
        ]);
    }

    /**
     * Create a PNG uploaded logo.
     */
    public function png(): static
    {
        return $this->state([
            'mime_type' => 'image/png',
            'original_name' => $this->faker->word().'.png',
            'file_path' => 'logos/uploaded/'.uniqid().'.png',
        ]);
    }

    /**
     * Create a JPEG uploaded logo.
     */
    public function jpeg(): static
    {
        return $this->state([
            'mime_type' => 'image/jpeg',
            'original_name' => $this->faker->word().'.jpg',
            'file_path' => 'logos/uploaded/'.uniqid().'.jpg',
        ]);
    }

    /**
     * Create an uploaded logo for a specific session.
     */
    public function forSession(string $sessionId): static
    {
        return $this->state([
            'session_id' => $sessionId,
        ]);
    }
}
