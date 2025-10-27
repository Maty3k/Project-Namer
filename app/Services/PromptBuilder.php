<?php

declare(strict_types=1);

namespace App\Services;

/**
 * PromptBuilder Service - Builds optimized prompts for AI name generation.
 *
 * Loads prompts from markdown files and interpolates template variables.
 * Supports multiple generation modes (creative, professional, brandable, tech-focused)
 * and intelligent business type detection for contextually relevant prompts.
 */
final class PromptBuilder
{
    /**
     * Map model IDs to prompt file suffixes.
     */
    private const MODEL_SUFFIX_MAP = [
        'gpt-4' => 'gpt4',
        'claude-3.5-sonnet' => 'claude',
        'gemini-1.5-pro' => 'gemini',
        'grok-beta' => 'grok',
    ];

    public function __construct(
        private PromptLoaderService $promptLoader
    ) {}
    /**
     * Build complete prompt with system and user components.
     *
     * @return array{system: string, user: string}
     */
    public function build(
        string $businessIdea,
        string $model,
        int $count,
        string $mode,
        bool $deepThinking
    ): array {
        return [
            'system' => $this->buildSystemPrompt($model, $count, $mode, $deepThinking),
            'user' => $this->buildUserPrompt($businessIdea, $model, $mode, $deepThinking),
        ];
    }

    /**
     * Build complete prompt with system and user components, plus configuration from markdown.
     *
     * @return array{system: string, user: string, config: PromptData}
     */
    public function buildWithConfig(
        string $businessIdea,
        string $model,
        int $count,
        string $mode,
        bool $deepThinking
    ): array {
        // Get model suffix and mode slug
        $modelSuffix = self::MODEL_SUFFIX_MAP[$model] ?? 'gpt4';
        $modeSlug = match ($mode) {
            'creative' => 'creative',
            'professional' => 'professional',
            'brandable' => 'brandable',
            'tech-focused' => 'tech-focused',
            default => 'creative',
        };

        // Load configuration from markdown
        $promptFileName = "name-generation-{$modeSlug}-{$modelSuffix}-system";
        $promptData = $this->promptLoader->loadWithCache($promptFileName);

        return [
            'system' => $this->buildSystemPrompt($model, $count, $mode, $deepThinking),
            'user' => $this->buildUserPrompt($businessIdea, $model, $mode, $deepThinking),
            'config' => $promptData,
        ];
    }

    /**
     * Build system prompt optimized for the generation mode and model.
     */
    public function buildSystemPrompt(string $model, int $count, string $mode, bool $deepThinking): string
    {
        // Get model suffix for filename (e.g., 'gpt-4' -> 'gpt4')
        $modelSuffix = self::MODEL_SUFFIX_MAP[$model] ?? 'gpt4';

        // Build prompt filename: name-generation-{mode}-{model}-system
        $modeSlug = match ($mode) {
            'creative' => 'creative',
            'professional' => 'professional',
            'brandable' => 'brandable',
            'tech-focused' => 'tech-focused',
            default => 'creative',
        };

        $promptFileName = "name-generation-{$modeSlug}-{$modelSuffix}-system";

        // Load prompt from markdown
        $promptData = $this->promptLoader->loadWithCache($promptFileName);

        // Build deep thinking instructions if enabled
        $deepThinkingInstructions = $deepThinking
            ? "\n\nDEEP THINKING MODE: Take extra time to analyze the business concept deeply. Consider market positioning, target demographics, competitive landscape, and long-term brand potential. Think about how each name would work across different marketing channels and customer touchpoints. Ensure names have strong commercial viability and avoid generic terms."
            : '';

        // Interpolate variables
        return $this->promptLoader->interpolate($promptData->promptText, [
            'count' => $count,
            'deepThinkingInstructions' => $deepThinkingInstructions,
        ]);
    }

    /**
     * Build user prompt with the business concept and contextual guidance.
     */
    public function buildUserPrompt(string $businessIdea, string $model, string $mode, bool $deepThinking): string
    {
        // Analyze business type for better context
        $businessType = 'General Business';
        $audience = 'General Public';
        $examples = '';

        $lowerIdea = strtolower($businessIdea);

        if (str_contains($lowerIdea, 'app') || str_contains($lowerIdea, 'software') || str_contains($lowerIdea, 'platform')) {
            $businessType = 'Technology/Software';
            $audience = 'Tech Users';
            $examples = "\n\nGood examples for tech businesses: GitHub, Stripe, Slack, Figma\nNotice how these avoid generic tech terms.";
        } elseif (str_contains($lowerIdea, 'food') || str_contains($lowerIdea, 'restaurant') || str_contains($lowerIdea, 'bakery')) {
            $businessType = 'Food & Beverage';
            $audience = 'Food Lovers';
            $examples = "\n\nGood examples for food businesses: Sweetgreen, Chipotle, Panera, Starbucks\nNotice how these focus on the food experience, not generic terms.";
        } elseif (str_contains($lowerIdea, 'shop') || str_contains($lowerIdea, 'store') || str_contains($lowerIdea, 'retail')) {
            $businessType = 'Retail/E-commerce';
            $audience = 'Shoppers';
            $examples = "\n\nGood examples for retail: Amazon, Etsy, Warby Parker, Casper\nNotice how these are brandable and memorable.";
        } elseif (str_contains($lowerIdea, 'consult') || str_contains($lowerIdea, 'service') || str_contains($lowerIdea, 'agency')) {
            $businessType = 'Professional Services';
            $audience = 'Business Clients';
            $examples = "\n\nGood examples for services: McKinsey, Deloitte, Accenture, IDEO\nNotice how these convey expertise and authority.";
        }

        // Load user prompt from markdown
        $promptData = $this->promptLoader->loadWithCache('name-generation-user');

        // Interpolate variables
        return $this->promptLoader->interpolate($promptData->promptText, [
            'businessIdea' => $businessIdea,
            'businessType' => $businessType,
            'audience' => $audience,
            'examples' => $examples,
        ]);
    }

    /**
     * Legacy compatibility method - combines system and user prompts into single string.
     * Used by existing code that expects a single combined prompt.
     */
    public function optimizePrompt(string $modelId, string $basePrompt, string $mode, bool $deepThinking): string
    {
        $prompts = $this->build(
            businessIdea: $basePrompt,
            model: $modelId,
            count: 10,
            mode: $mode,
            deepThinking: $deepThinking
        );

        return $prompts['system']."\n\n".$prompts['user'];
    }
}
