<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserAIPreferences;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAIPreferences>
 */
final class UserAIPreferencesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'preferred_models' => $this->faker->randomElements(['gpt-4o', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta'], 2),
            'default_generation_mode' => $this->faker->randomElement(['creative', 'professional', 'brandable', 'tech-focused']),
            'default_deep_thinking' => $this->faker->boolean(30),
            'model_priorities' => [
                'gpt-4o' => $this->faker->numberBetween(1, 4),
                'claude-3.5-sonnet' => $this->faker->numberBetween(1, 4),
                'gemini-1.5-pro' => $this->faker->numberBetween(1, 4),
                'grok-beta' => $this->faker->numberBetween(1, 4),
            ],
            'custom_parameters' => [
                'temperature' => $this->faker->randomFloat(1, 0.1, 1.0),
                'max_tokens' => $this->faker->numberBetween(500, 2000),
            ],
            'notification_settings' => [
                'email_on_completion' => $this->faker->boolean(),
                'email_on_failure' => $this->faker->boolean(80),
                'push_notifications' => $this->faker->boolean(),
            ],
            'auto_select_best_model' => $this->faker->boolean(70),
            'enable_model_comparison' => $this->faker->boolean(80),
            'max_concurrent_generations' => $this->faker->numberBetween(1, 5),
        ];
    }

    /**
     * Indicate the user prefers creative generation.
     */
    public function creative(): self
    {
        return $this->state([
            'default_generation_mode' => 'creative',
            'default_deep_thinking' => true,
            'custom_parameters' => [
                'temperature' => $this->faker->randomFloat(1, 0.8, 1.0),
                'max_tokens' => $this->faker->numberBetween(1000, 2000),
            ],
        ]);
    }

    /**
     * Indicate the user prefers professional generation.
     */
    public function professional(): self
    {
        return $this->state([
            'default_generation_mode' => 'professional',
            'default_deep_thinking' => false,
            'custom_parameters' => [
                'temperature' => $this->faker->randomFloat(1, 0.3, 0.7),
                'max_tokens' => $this->faker->numberBetween(500, 1000),
            ],
        ]);
    }

    /**
     * Indicate the user prefers specific models.
     *
     * @param  array<string>  $models
     */
    public function withPreferredModels(array $models): self
    {
        $priorities = [];
        foreach ($models as $index => $model) {
            $priorities[$model] = $index + 1;
        }

        return $this->state([
            'preferred_models' => $models,
            'model_priorities' => $priorities,
        ]);
    }

    /**
     * Indicate the user wants all notifications.
     */
    public function allNotifications(): self
    {
        return $this->state([
            'notification_settings' => [
                'email_on_completion' => true,
                'email_on_failure' => true,
                'push_notifications' => true,
            ],
        ]);
    }

    /**
     * Indicate the user wants no notifications.
     */
    public function noNotifications(): self
    {
        return $this->state([
            'notification_settings' => [
                'email_on_completion' => false,
                'email_on_failure' => false,
                'push_notifications' => false,
            ],
        ]);
    }
}