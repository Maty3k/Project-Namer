<?php

declare(strict_types=1);

namespace App\Services;

use Prism\Prism\Enums\Provider;

/**
 * Data Transfer Object for AI prompt configuration and content.
 *
 * Contains all necessary information to make an AI API call via Prism,
 * including provider, model, parameters, and the prompt text itself.
 */
readonly class PromptData
{
    /**
     * Create a new PromptData instance.
     *
     * @param  Provider  $provider  The AI provider (OpenAI, Anthropic, Gemini, XAI)
     * @param  string  $model  The specific model to use (e.g., 'gpt-4o', 'claude-3-5-sonnet-20241022')
     * @param  string  $promptText  The actual prompt text content
     * @param  float|null  $temperature  Generation randomness (0.0-2.0), null uses provider default
     * @param  int|null  $maxTokens  Maximum output tokens, null uses provider default
     * @param  float|null  $deepThinkingTemperature  Alternative temperature for deep thinking mode
     * @param  string|null  $description  Human-readable description of this prompt's purpose
     * @param  array<string, mixed>  $clientOptions  Additional provider-specific options
     */
    public function __construct(
        public Provider $provider,
        public string $model,
        public string $promptText,
        public ?float $temperature = null,
        public ?int $maxTokens = null,
        public ?float $deepThinkingTemperature = null,
        public ?string $description = null,
        public array $clientOptions = []
    ) {}

    /**
     * Export prompt data to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider->value,
            'model' => $this->model,
            'promptText' => $this->promptText,
            'temperature' => $this->temperature,
            'maxTokens' => $this->maxTokens,
            'deepThinkingTemperature' => $this->deepThinkingTemperature,
            'description' => $this->description,
            'clientOptions' => $this->clientOptions,
        ];
    }

    /**
     * Create PromptData instance from array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            provider: Provider::from($data['provider']),
            model: $data['model'],
            promptText: $data['promptText'],
            temperature: $data['temperature'] ?? null,
            maxTokens: $data['maxTokens'] ?? null,
            deepThinkingTemperature: $data['deepThinkingTemperature'] ?? null,
            description: $data['description'] ?? null,
            clientOptions: $data['clientOptions'] ?? []
        );
    }
}
