<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service for building optimized prompts for AI name generation.
 *
 * Handles sophisticated prompt engineering including business analysis,
 * mode-specific instructions, and contextual guidance for better AI results.
 */
final class PromptBuilder
{
    private const VALID_MODES = ['creative', 'professional', 'brandable', 'tech-focused'];

    /**
     * Build system prompt optimized for the generation mode.
     */
    public function buildSystemPrompt(string $model, int $count, string $mode, bool $deepThinking = false): string
    {
        // Core rules that apply to all modes
        $coreRules = '
CRITICAL RULES:
- Names must be directly relevant to the business concept
- NO generic tech suffixes (App, Tech, Labs, Solutions, Systems, Digital, Hub, Pro, etc.)
- NO unnecessary prefixes (My, Get, The, etc.) unless they add meaningful value
- Names should sound like actual business names, not product features
- Focus on the CORE business value, not the technology behind it
- Make names memorable, pronounceable, and brandable
- Avoid overused words like "Smart", "Cloud", "AI", "Sync", "Connect"';

        $modeSystemPrompts = [
            'creative' => "You are an expert business naming consultant who creates compelling brand names. Generate exactly {$count} unique business names, numbered 1-{$count}, one per line.

{$coreRules}

CREATIVE MODE: Generate creative and artistic names that evoke emotion and curiosity. Think of names like \"Spotify\", \"Airbnb\", or \"Etsy\" - unique, memorable, and meaningful without being literal. Use wordplay, metaphors, or invented words that capture the essence of the business.",

            'professional' => "You are an expert business naming consultant who creates compelling brand names. Generate exactly {$count} unique business names, numbered 1-{$count}, one per line.

{$coreRules}

PROFESSIONAL MODE: Generate sophisticated, trustworthy names suitable for B2B environments. Think of names like \"Goldman Sachs\", \"McKinsey\", or \"Deloitte\" - authoritative, credible, and corporate-appropriate. Use strong, confident language that conveys expertise and reliability.",

            'brandable' => "You are an expert business naming consultant who creates compelling brand names. Generate exactly {$count} unique business names, numbered 1-{$count}, one per line.

{$coreRules}

BRANDABLE MODE: Generate catchy, market-ready names perfect for consumer brands. Think of names like \"Google\", \"Amazon\", or \"Nike\" - short, punchy, and easy to remember. Focus on names that would work well in advertising, social media, and word-of-mouth marketing.",

            'tech-focused' => "You are an expert business naming consultant who creates compelling brand names. Generate exactly {$count} unique business names, numbered 1-{$count}, one per line.

{$coreRules}

TECH-FOCUSED MODE: Generate modern names that appeal to technical audiences without using obvious tech jargon. Think of names like \"GitHub\", \"Stripe\", or \"Slack\" - developer-friendly but not generic. Focus on the problem being solved, not the technology used.",
        ];

        $systemPrompt = $modeSystemPrompts[$mode] ?? $modeSystemPrompts['creative'];

        if ($deepThinking) {
            $systemPrompt .= "\n\nDEEP THINKING MODE: Take extra time to analyze the business concept deeply. Consider market positioning, target demographics, competitive landscape, and long-term brand potential. Think about how each name would work across different marketing channels and customer touchpoints. Ensure names have strong commercial viability and avoid generic terms.";
        }

        return $systemPrompt;
    }

    /**
     * Build user prompt with the business concept and contextual analysis.
     */
    public function buildUserPrompt(string $businessIdea, string $model, string $mode, bool $deepThinking): string
    {
        $analysis = $this->analyzeBusinessType($businessIdea);

        $contextualGuidance = "\n\nBusiness Analysis:
- Type: {$analysis['type']}
- Target Audience: {$analysis['audience']}

Consider these factors:
- What problem does this business solve?
- What makes it different from competitors?
- What emotion should the brand evoke?
- Who is the primary customer?{$analysis['examples']}";

        return "Business concept: {$businessIdea}{$contextualGuidance}";
    }

    /**
     * Analyze business type for better context and examples.
     *
     * @return array{type: string, audience: string, examples: string}
     */
    public function analyzeBusinessType(string $businessIdea): array
    {
        $lowerIdea = strtolower($businessIdea);

        if (str_contains($lowerIdea, 'app') || str_contains($lowerIdea, 'software') || str_contains($lowerIdea, 'platform')) {
            return [
                'type' => 'Technology/Software',
                'audience' => 'Tech Users',
                'examples' => "\n\nGood examples for tech businesses: GitHub, Stripe, Slack, Figma\nNotice how these avoid generic tech terms."
            ];
        }

        if (str_contains($lowerIdea, 'food') || str_contains($lowerIdea, 'restaurant') || str_contains($lowerIdea, 'bakery')) {
            return [
                'type' => 'Food & Beverage',
                'audience' => 'Food Lovers',
                'examples' => "\n\nGood examples for food businesses: Sweetgreen, Chipotle, Panera, Starbucks\nNotice how these focus on the food experience, not generic terms."
            ];
        }

        if (str_contains($lowerIdea, 'shop') || str_contains($lowerIdea, 'store') || str_contains($lowerIdea, 'retail')) {
            return [
                'type' => 'Retail/E-commerce',
                'audience' => 'Shoppers',
                'examples' => "\n\nGood examples for retail: Amazon, Etsy, Warby Parker, Casper\nNotice how these are brandable and memorable."
            ];
        }

        if (str_contains($lowerIdea, 'consult') || str_contains($lowerIdea, 'service') || str_contains($lowerIdea, 'agency')) {
            return [
                'type' => 'Professional Services',
                'audience' => 'Business Clients',
                'examples' => "\n\nGood examples for services: McKinsey, Deloitte, Accenture, IDEO\nNotice how these convey expertise and authority."
            ];
        }

        return [
            'type' => 'General Business',
            'audience' => 'General Public',
            'examples' => ''
        ];
    }

    /**
     * Optimize prompt for specific model and mode.
     */
    public function optimizePrompt(string $modelId, string $basePrompt, string $mode, bool $deepThinking): string
    {
        $systemPrompt = 'You are an expert business naming consultant who creates compelling brand names. Generate exactly 10 unique business names, numbered 1-10, one per line.

CRITICAL RULES:
- Names must be directly relevant to the business concept
- NO generic tech suffixes (App, Tech, Labs, Solutions, Systems, Digital, Hub, Pro, etc.)
- NO unnecessary prefixes (My, Get, The, etc.) unless they add meaningful value
- Names should sound like actual business names, not product features
- Focus on the CORE business value, not the technology behind it
- Make names memorable, pronounceable, and brandable
- Avoid overused words like "Smart", "Cloud", "AI", "Sync", "Connect"';

        $modeInstructions = match ($mode) {
            'creative' => 'Generate creative and artistic names that evoke emotion and curiosity. Think of names like "Spotify", "Airbnb", or "Etsy" - unique, memorable, and meaningful without being literal. Use wordplay, metaphors, or invented words that capture the essence of the business.',

            'professional' => 'Generate sophisticated, trustworthy names suitable for B2B environments. Think of names like "Goldman Sachs", "McKinsey", or "Deloitte" - authoritative, credible, and corporate-appropriate. Use strong, confident language that conveys expertise and reliability.',

            'brandable' => 'Generate catchy, market-ready names perfect for consumer brands. Think of names like "Google", "Amazon", or "Nike" - short, punchy, and easy to remember. Focus on names that would work well in advertising, social media, and word-of-mouth marketing.',

            'tech-focused' => 'Generate modern names that appeal to technical audiences without using obvious tech jargon. Think of names like "GitHub", "Stripe", or "Slack" - developer-friendly but not generic. Focus on the problem being solved, not the technology used.',

            default => 'Generate creative and memorable business names that capture the essence of the business concept.'
        };

        // Basic business analysis based on keywords
        $analysis = $this->analyzeBusinessType($basePrompt);

        $contextualGuidance = "Business Type: {$analysis['type']}
Target Audience: {$analysis['audience']}

Consider these factors when creating names:
- What problem does this business solve?
- What makes it different from competitors?
- What emotion should the brand evoke?
- Who is the primary customer?";

        $thinkingInstruction = $deepThinking
            ? 'Take extra time to analyze the business concept deeply. Consider market positioning, target demographics, competitive landscape, and long-term brand potential. Think about how each name would work across different marketing channels and customer touchpoints.'
            : 'Focus on creating names that immediately communicate the business value and appeal to the target audience.';

        // Examples based on mode
        $examples = match ($mode) {
            'creative' => 'Good examples: Spotify, Airbnb, Etsy, Headspace, Sweetgreen
Notice how these names are memorable, unique, and avoid obvious descriptive terms.',
            'professional' => 'Good examples: Goldman Sachs, McKinsey, Deloitte, Salesforce
Notice how these names convey authority, expertise, and trustworthiness.',
            'brandable' => 'Good examples: Google, Amazon, Nike, Apple, Tesla
Notice how these names are short, punchy, and perfect for marketing.',
            'tech-focused' => 'Good examples: GitHub, Stripe, Slack, Figma
Notice how these appeal to developers without using generic tech terms.',
            default => 'Good examples: Apple, Google, Amazon, Nike, Tesla'
        };

        return "{$systemPrompt}\n\n{$modeInstructions}\n\n{$contextualGuidance}\n\n{$thinkingInstruction}\n\n{$examples}\n\nBusiness concept: {$basePrompt}";
    }

    /**
     * Check if a generation mode is valid.
     */
    public function isValidMode(string $mode): bool
    {
        return in_array($mode, self::VALID_MODES);
    }

    /**
     * Get all valid generation modes.
     *
     * @return array<int, string>
     */
    public function getValidModes(): array
    {
        return self::VALID_MODES;
    }
}
