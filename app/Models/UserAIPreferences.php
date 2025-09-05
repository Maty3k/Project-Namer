<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User AI preferences configuration model.
 *
 * Stores user-specific AI configuration preferences including
 * model selection, generation parameters, and notification settings.
 *
 * @property int $id
 * @property int $user_id
 * @property array<array-key, mixed> $preferred_models
 * @property string $default_generation_mode
 * @property bool $default_deep_thinking
 * @property array<array-key, mixed>|null $model_priorities
 * @property array<array-key, mixed>|null $custom_parameters
 * @property array<array-key, mixed>|null $notification_settings
 * @property bool $auto_select_best_model
 * @property bool $enable_model_comparison
 * @property int $max_concurrent_generations
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 *
 * @method static \Database\Factories\UserAIPreferencesFactory factory($count = null, $state = [])
 * @method static Builder<static>|UserAIPreferences newModelQuery()
 * @method static Builder<static>|UserAIPreferences newQuery()
 * @method static Builder<static>|UserAIPreferences query()
 * @method static Builder<static>|UserAIPreferences whereAutoSelectBestModel($value)
 * @method static Builder<static>|UserAIPreferences whereCreatedAt($value)
 * @method static Builder<static>|UserAIPreferences whereCustomParameters($value)
 * @method static Builder<static>|UserAIPreferences whereDefaultDeepThinking($value)
 * @method static Builder<static>|UserAIPreferences whereDefaultGenerationMode($value)
 * @method static Builder<static>|UserAIPreferences whereEnableModelComparison($value)
 * @method static Builder<static>|UserAIPreferences whereId($value)
 * @method static Builder<static>|UserAIPreferences whereMaxConcurrentGenerations($value)
 * @method static Builder<static>|UserAIPreferences whereModelPriorities($value)
 * @method static Builder<static>|UserAIPreferences whereNotificationSettings($value)
 * @method static Builder<static>|UserAIPreferences wherePreferredModels($value)
 * @method static Builder<static>|UserAIPreferences whereUpdatedAt($value)
 * @method static Builder<static>|UserAIPreferences whereUserId($value)
 * @method static Builder<static>|UserAIPreferences withPreferredModel(string $modelName)
 *
 * @mixin \Eloquent
 */
final class UserAIPreferences extends Model
{
    /** @use HasFactory<\Database\Factories\UserAIPreferencesFactory> */
    use HasFactory;

    protected $table = 'user_ai_preferences';

    protected $fillable = [
        'user_id',
        'preferred_models',
        'default_generation_mode',
        'default_deep_thinking',
        'model_priorities',
        'custom_parameters',
        'notification_settings',
        'auto_select_best_model',
        'enable_model_comparison',
        'max_concurrent_generations',
    ];

    protected function casts(): array
    {
        return [
            'preferred_models' => 'array',
            'default_deep_thinking' => 'boolean',
            'model_priorities' => 'array',
            'custom_parameters' => 'array',
            'notification_settings' => 'array',
            'auto_select_best_model' => 'boolean',
            'enable_model_comparison' => 'boolean',
            'max_concurrent_generations' => 'integer',
        ];
    }

    /**
     * Get the user that owns the preferences.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to find users who prefer a specific model.
     *
     * @param  Builder<UserAIPreferences>  $query
     * @return Builder<UserAIPreferences>
     */
    protected function scopeWithPreferredModel(Builder $query, string $modelName): Builder
    {
        return $query->whereJsonContains('preferred_models', $modelName);
    }

    /**
     * Find or create preferences for user with sensible defaults.
     */
    public static function findOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'preferred_models' => ['gpt-4', 'claude-3.5-sonnet'],
                'default_generation_mode' => 'creative',
                'default_deep_thinking' => false,
                'model_priorities' => [
                    'gpt-4' => 1,
                    'claude-3.5-sonnet' => 2,
                    'gemini-1.5-pro' => 3,
                    'grok-beta' => 4,
                ],
                'custom_parameters' => [],
                'notification_settings' => [
                    'email_on_completion' => false,
                    'email_on_failure' => true,
                ],
                'auto_select_best_model' => true,
                'enable_model_comparison' => true,
                'max_concurrent_generations' => 3,
            ]
        );
    }

    /**
     * Get preferred models ordered by priority.
     *
     * @return array<int, string>
     */
    public function getPreferredModelsOrdered(): array
    {
        if (empty($this->model_priorities)) {
            return $this->preferred_models;
        }

        $modelsWithPriorities = array_intersect_key(
            $this->model_priorities,
            array_flip($this->preferred_models)
        );

        asort($modelsWithPriorities);

        return array_keys($modelsWithPriorities);
    }

    /**
     * Update preferred models list.
     *
     * @param  array<int, string>  $models
     */
    public function updatePreferredModels(array $models): void
    {
        $this->update(['preferred_models' => $models]);
    }

    /**
     * Update model priorities.
     *
     * @param  array<string, int>  $priorities
     */
    public function updateModelPriorities(array $priorities): void
    {
        $this->update(['model_priorities' => $priorities]);
    }

    /**
     * Update custom parameters.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function updateCustomParameters(array $parameters): void
    {
        $this->update(['custom_parameters' => $parameters]);
    }

    /**
     * Update notification settings.
     *
     * @param  array<string, mixed>  $settings
     */
    public function updateNotificationSettings(array $settings): void
    {
        $this->update(['notification_settings' => $settings]);
    }

    /**
     * Check if a model is in preferred models list.
     */
    public function isModelPreferred(string $modelName): bool
    {
        return in_array($modelName, $this->preferred_models);
    }

    /**
     * Get default generation parameters.
     *
     * @return array<string, mixed>
     */
    public function getDefaultGenerationParameters(): array
    {
        return [
            'mode' => $this->default_generation_mode,
            'deep_thinking' => $this->default_deep_thinking,
            'custom_params' => $this->custom_parameters ?? [],
        ];
    }

    /**
     * Validate generation mode.
     */
    public function isValidGenerationMode(string $mode): bool
    {
        return in_array($mode, ['creative', 'professional', 'brandable', 'tech-focused']);
    }

    /**
     * Get AI preferences summary.
     *
     * @return array<string, mixed>
     */
    public function getPreferencesSummary(): array
    {
        return [
            'preferred_models' => $this->getPreferredModelsOrdered(),
            'default_generation_mode' => $this->default_generation_mode,
            'default_deep_thinking' => $this->default_deep_thinking,
            'auto_select_best_model' => $this->auto_select_best_model,
            'enable_model_comparison' => $this->enable_model_comparison,
            'max_concurrent_generations' => $this->max_concurrent_generations,
            'custom_parameters' => $this->custom_parameters ?? [],
            'notification_settings' => $this->notification_settings ?? [],
        ];
    }
}
