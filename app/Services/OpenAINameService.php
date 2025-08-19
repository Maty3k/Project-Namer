<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GenerationCache;
use Exception;
use InvalidArgumentException;
use Prism\Prism\Prism;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

/**
 * Service for generating business names using Prism with OpenAI GPT API.
 *
 * Handles communication with OpenAI API via Prism to generate creative business names
 * based on user input and selected generation modes.
 */
final class OpenAINameService
{
    private const VALID_MODES = ['creative', 'professional', 'brandable', 'tech-focused'];

    private const MAX_INPUT_LENGTH = 2000;

    /**
     * Generate business names using OpenAI API via Prism.
     *
     * @param  string  $businessIdea  The business concept or description
     * @param  string  $mode  Generation mode (creative, professional, brandable, tech-focused)
     * @param  bool  $deepThinking  Whether to use deep thinking mode for enhanced results
     * @return array<int, string> Array of 10 generated business names
     *
     * @throws InvalidArgumentException If input parameters are invalid
     * @throws Exception If API request fails
     */
    public function generateNames(string $businessIdea, string $mode, bool $deepThinking = false): array
    {
        $this->validateInput($businessIdea, $mode);

        // Check cache first
        $inputHash = GenerationCache::generateHash($businessIdea, $mode, $deepThinking);
        $cachedResult = GenerationCache::findByHash($inputHash);

        if ($cachedResult !== null) {
            return $cachedResult->generated_names;
        }

        $systemPrompt = 'You are a creative business naming expert. Generate exactly 10 business names, numbered 1-10, one per line.';
        $userPrompt = $this->buildPrompt($businessIdea, $mode, $deepThinking);

        try {
            $response = Prism::text()
                ->using('openai', 'gpt-4o')
                ->withMessages([
                    new SystemMessage($systemPrompt),
                    new UserMessage($userPrompt),
                ])
                ->withClientOptions([
                    'max_tokens' => 200,
                    'temperature' => $deepThinking ? 0.3 : 0.7,
                ])
                ->asText();

            $names = $this->parseResponse($response->text);

            // Cache the result
            $this->cacheResult($inputHash, $businessIdea, $mode, $deepThinking, $names);

            return $names;
        } catch (\Exception $e) {
            // Map Prism exceptions to our expected exceptions
            $message = $e->getMessage();

            if (str_contains($message, 'timeout') || str_contains($message, 'Timeout')) {
                throw new Exception('OpenAI API timeout');
            }

            if (str_contains($message, 'rate limit') || str_contains($message, '429')) {
                throw new Exception('Rate limit exceeded');
            }

            if (str_contains($message, 'unauthorized') || str_contains($message, '401')) {
                throw new Exception('Invalid API key');
            }

            throw new Exception($message);
        }
    }

    /**
     * Validate input parameters.
     */
    private function validateInput(string $businessIdea, string $mode): void
    {
        if (empty(trim($businessIdea))) {
            throw new InvalidArgumentException('Business idea cannot be empty');
        }

        if (strlen($businessIdea) > self::MAX_INPUT_LENGTH) {
            throw new InvalidArgumentException('Business idea too long');
        }

        if (! in_array($mode, self::VALID_MODES)) {
            throw new InvalidArgumentException('Invalid generation mode');
        }
    }

    /**
     * Build the appropriate prompt based on mode and thinking level.
     */
    private function buildPrompt(string $businessIdea, string $mode, bool $deepThinking): string
    {
        $modePrompts = [
            'creative' => 'Generate creative, unique, and memorable business names that stand out and spark curiosity.',
            'professional' => 'Generate professional, trustworthy business names suitable for corporate environments.',
            'brandable' => 'Generate brandable, catchy names that are easy to remember and could work as domain names.',
            'tech-focused' => 'Generate tech-focused names that appeal to developers and technical audiences.',
        ];

        $basePrompt = $modePrompts[$mode]."\n\nBusiness concept: ".$businessIdea;

        if ($deepThinking) {
            $basePrompt .= "\n\nTake time to consider the target audience, market positioning, and brand personality. Think about names that would resonate with customers and be easy to market.";
        }

        return $basePrompt;
    }

    /**
     * Cache generation result.
     *
     * @param  array<int, string>  $names
     */
    private function cacheResult(string $inputHash, string $businessIdea, string $mode, bool $deepThinking, array $names): void
    {
        GenerationCache::updateOrCreate(
            ['input_hash' => $inputHash],
            [
                'business_description' => $businessIdea,
                'mode' => $mode,
                'deep_thinking' => $deepThinking,
                'generated_names' => $names,
                'cached_at' => now(),
            ]
        );
    }

    /**
     * Clear expired generation cache entries.
     */
    public function clearExpiredCache(): int
    {
        return GenerationCache::expired()->delete();
    }

    /**
     * Parse the API response to extract business names.
     *
     * @return array<int, string>
     */
    private function parseResponse(string $content): array
    {
        if (empty($content)) {
            throw new Exception('Empty response from OpenAI API');
        }

        $lines = explode("\n", $content);

        $names = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^\d+\.\s*(.+)$/', $line, $matches)) {
                $names[] = trim($matches[1]);
            }
        }

        if (count($names) === 0) {
            throw new Exception('Invalid response format');
        }

        // Ensure we return exactly 10 names, pad with generic names if needed
        while (count($names) < 10) {
            $names[] = 'BusinessName'.(count($names) + 1);
        }

        return array_slice($names, 0, 10);
    }
}
