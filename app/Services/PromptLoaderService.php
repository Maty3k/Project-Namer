<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidPromptException;
use App\Exceptions\PromptNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Prism\Prism\Enums\Provider;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Service for loading and parsing AI prompt markdown files.
 *
 * Handles YAML frontmatter parsing, configuration resolution via config_source,
 * template variable interpolation, and caching for performance.
 */
final class PromptLoaderService
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Create a new PromptLoaderService instance.
     *
     * @param  string|null  $promptsPath  Path to prompts directory
     */
    public function __construct(
        protected ?string $promptsPath = null
    ) {
        $this->promptsPath = $promptsPath ?? resource_path('prompts');
    }

    /**
     * Load a prompt file and parse its contents.
     *
     * @param  string  $promptName  Prompt filename without .md extension
     *
     * @throws PromptNotFoundException
     * @throws InvalidPromptException
     */
    public function load(string $promptName): PromptData
    {
        $filePath = $this->getFilePath($promptName);

        if (! File::exists($filePath)) {
            throw new PromptNotFoundException("Prompt file not found: {$promptName}");
        }

        $content = File::get($filePath);

        return $this->parse($content, $promptName);
    }

    /**
     * Load a prompt file with caching.
     *
     * @param  string  $promptName  Prompt filename without .md extension
     */
    public function loadWithCache(string $promptName): PromptData
    {
        $cacheKey = $this->getCacheKey($promptName);

        return Cache::remember($cacheKey, self::CACHE_TTL, fn () => $this->load($promptName));
    }

    /**
     * Clear cache for a specific prompt.
     *
     * @param  string  $promptName  Prompt filename without .md extension
     */
    public function clearCache(string $promptName): void
    {
        $cacheKey = $this->getCacheKey($promptName);
        Cache::forget($cacheKey);
    }

    /**
     * Get all available prompt names in the prompts directory.
     *
     * @return array<int, string>
     */
    public function getAllPrompts(): array
    {
        $files = File::files($this->promptsPath);

        return collect($files)
            ->filter(fn ($file) => $file->getExtension() === 'md')
            ->map(fn ($file) => $file->getFilenameWithoutExtension())
            ->values()
            ->all();
    }

    /**
     * Interpolate template variables in prompt text.
     *
     * @param  string  $template  Template string with {$variable} placeholders
     * @param  array<string, mixed>  $variables  Variables to interpolate
     */
    public function interpolate(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // Convert value to string (handles numbers, objects with __toString, etc.)
            $stringValue = is_scalar($value) ? (string) $value : json_encode($value);

            $template = str_replace('{$'.$key.'}', $stringValue, $template);
        }

        return $template;
    }

    /**
     * Parse markdown content with YAML frontmatter.
     *
     * @throws InvalidPromptException
     */
    protected function parse(string $content, string $promptName): PromptData
    {
        // Extract frontmatter and prompt text
        [$frontmatter, $promptText] = $this->extractFrontmatter($content, $promptName);

        // Check if this prompt references a config source
        if (isset($frontmatter['config_source'])) {
            return $this->resolveConfigSource($frontmatter, $promptText, $promptName);
        }

        // No config source - validate required fields are present
        $this->validateRequiredFields($frontmatter, $promptName);

        // Parse and return PromptData
        return $this->buildPromptData($frontmatter, $promptText);
    }

    /**
     * Extract YAML frontmatter and prompt text from markdown content.
     *
     * @return array{0: array<string, mixed>, 1: string}
     *
     * @throws InvalidPromptException
     */
    protected function extractFrontmatter(string $content, string $promptName): array
    {
        // Match YAML frontmatter delimited by ---
        if (! preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
            throw new InvalidPromptException("Invalid frontmatter format in: {$promptName}");
        }

        $yamlContent = $matches[1];
        $promptText = trim($matches[2]);

        try {
            $frontmatter = Yaml::parse($yamlContent);
        } catch (ParseException) {
            throw new InvalidPromptException("Invalid YAML frontmatter in: {$promptName}");
        }

        if (! is_array($frontmatter)) {
            throw new InvalidPromptException("Frontmatter must be a YAML object in: {$promptName}");
        }

        return [$frontmatter, $promptText];
    }

    /**
     * Resolve configuration from a config_source reference.
     *
     * @param  array<string, mixed>  $frontmatter
     *
     * @throws InvalidPromptException
     * @throws PromptNotFoundException
     */
    protected function resolveConfigSource(array $frontmatter, string $promptText, string $promptName): PromptData
    {
        $configSource = $frontmatter['config_source'];

        // Check for self-reference
        if ($configSource === $promptName.'.md' || $configSource === $promptName) {
            throw new InvalidPromptException("Config source cannot reference itself in: {$promptName}");
        }

        // Remove .md extension if present
        $configSourceName = str_replace('.md', '', $configSource);

        // Load the config source file
        try {
            $configData = $this->loadWithCache($configSourceName);
        } catch (PromptNotFoundException) {
            throw new PromptNotFoundException("Config source file not found: {$configSource} referenced in {$promptName}");
        }

        // Return new PromptData with config from source but prompt text from current file
        return new PromptData(
            provider: $configData->provider,
            model: $configData->model,
            promptText: $promptText,
            temperature: $configData->temperature,
            maxTokens: $configData->maxTokens,
            deepThinkingTemperature: $configData->deepThinkingTemperature,
            description: $frontmatter['description'] ?? $configData->description,
            clientOptions: $configData->clientOptions
        );
    }

    /**
     * Validate that required fields are present in frontmatter.
     *
     * @param  array<string, mixed>  $frontmatter
     *
     * @throws InvalidPromptException
     */
    protected function validateRequiredFields(array $frontmatter, string $promptName): void
    {
        if (! isset($frontmatter['provider'])) {
            throw new InvalidPromptException("Missing required field 'provider' in: {$promptName}");
        }

        if (! isset($frontmatter['model'])) {
            throw new InvalidPromptException("Missing required field 'model' in: {$promptName}");
        }
    }

    /**
     * Build PromptData from parsed frontmatter and prompt text.
     *
     * @param  array<string, mixed>  $frontmatter
     *
     * @throws InvalidPromptException
     */
    protected function buildPromptData(array $frontmatter, string $promptText): PromptData
    {
        // Parse provider enum
        try {
            $provider = Provider::from($frontmatter['provider']);
        } catch (\ValueError) {
            $validProviders = implode(', ', array_map(fn ($p) => $p->value, Provider::cases()));
            throw new InvalidPromptException(
                "Invalid provider '{$frontmatter['provider']}'. Must be one of: {$validProviders}"
            );
        }

        return new PromptData(
            provider: $provider,
            model: $frontmatter['model'],
            promptText: $promptText,
            temperature: isset($frontmatter['temperature']) ? (float) $frontmatter['temperature'] : null,
            maxTokens: isset($frontmatter['max_tokens']) ? (int) $frontmatter['max_tokens'] : null,
            deepThinkingTemperature: isset($frontmatter['deep_thinking_temperature']) ? (float) $frontmatter['deep_thinking_temperature'] : null,
            description: $frontmatter['description'] ?? null,
            clientOptions: $frontmatter['client_options'] ?? []
        );
    }

    /**
     * Get full file path for a prompt.
     */
    protected function getFilePath(string $promptName): string
    {
        return $this->promptsPath.'/'.$promptName.'.md';
    }

    /**
     * Get cache key for a prompt.
     */
    protected function getCacheKey(string $promptName): string
    {
        return "prompt:{$promptName}:parsed";
    }
}
