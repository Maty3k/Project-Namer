<?php

declare(strict_types=1);

namespace App\Services;

/**
 * PromptBuilder Service - Builds optimized prompts for AI name generation.
 *
 * Extracts and centralizes all prompt engineering logic for generating business names.
 * Supports multiple generation modes (creative, professional, brandable, tech-focused)
 * and intelligent business type detection for contextually relevant prompts.
 */
final class PromptBuilder
{
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
     * Build system prompt optimized for the generation mode.
     */
    public function buildSystemPrompt(string $model, int $count, string $mode, bool $deepThinking): string
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

        $contextualGuidance = "\n\nBusiness Analysis:
- Type: {$businessType}
- Target Audience: {$audience}

Consider these factors:
- What problem does this business solve?
- What makes it different from competitors?
- What emotion should the brand evoke?
- Who is the primary customer?{$examples}";

        return "Business concept: {$businessIdea}{$contextualGuidance}";
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
