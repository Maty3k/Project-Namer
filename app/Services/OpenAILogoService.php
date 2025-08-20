<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * OpenAI Logo Service.
 *
 * Handles logo generation using OpenAI's DALL-E 3 API with intelligent
 * prompt generation, error handling, rate limiting, and cost tracking.
 */
final class OpenAILogoService
{
    /**
     * Available logo styles with their characteristics.
     *
     * @var array<string, array<string, string>>
     */
    private const LOGO_STYLES = [
        'minimalist' => [
            'description' => 'Clean, simple design with minimal elements',
            'keywords' => 'minimalist, clean, simple, geometric, modern, elegant',
            'avoid' => 'complex details, gradients, multiple colors',
        ],
        'modern' => [
            'description' => 'Contemporary design with current trends',
            'keywords' => 'modern, contemporary, trendy, sleek, professional, innovative',
            'avoid' => 'vintage elements, outdated fonts, old-fashioned styles',
        ],
        'playful' => [
            'description' => 'Fun, energetic design with vibrant elements',
            'keywords' => 'playful, fun, energetic, colorful, dynamic, friendly',
            'avoid' => 'serious tones, corporate formality, muted colors',
        ],
        'corporate' => [
            'description' => 'Professional, trustworthy design for business',
            'keywords' => 'corporate, professional, trustworthy, established, reliable, business',
            'avoid' => 'casual elements, overly creative fonts, bright colors',
        ],
    ];

    /**
     * DALL-E 3 pricing in cents (as of 2024).
     */
    private const DALLE_3_COST_CENTS = 400; // $0.04 per 1024x1024 image

    /**
     * Maximum retry attempts for API calls.
     */
    private const MAX_RETRY_ATTEMPTS = 3;

    /**
     * Rate limiting configuration.
     */
    private int $rateLimitRequests = 50;

    private int $rateLimitWindowSeconds = 3600; // 1 hour

    /**
     * Generate a logo using DALL-E 3.
     *
     * @return array<string, mixed>
     */
    public function generateLogo(string $businessIdea, string $style): array
    {
        $this->validateInputs($businessIdea, $style);

        if (! $this->checkRateLimit()) {
            return $this->errorResponse('Rate limit exceeded', 'rate_limit_local');
        }

        $prompt = $this->generateLogoPrompt($businessIdea, $style);

        return $this->makeApiRequest($prompt, $style);
    }

    /**
     * Generate multiple logos in different styles.
     *
     * @param  array<string>  $styles
     * @return array<string, array<string, mixed>>
     */
    public function generateMultipleLogos(string $businessIdea, array $styles): array
    {
        $results = [];

        foreach ($styles as $style) {
            $results[$style] = $this->generateLogo($businessIdea, $style);
        }

        return $results;
    }

    /**
     * Generate an intelligent logo prompt based on business idea and style.
     */
    public function generateLogoPrompt(string $businessIdea, string $style): string
    {
        if (! isset(self::LOGO_STYLES[$style])) {
            throw new InvalidArgumentException("Invalid style: {$style}");
        }

        $styleConfig = self::LOGO_STYLES[$style];
        $sanitizedIdea = $this->sanitizeBusinessIdea($businessIdea);

        $prompt = "Create a {$style} logo design for: {$sanitizedIdea}. ";
        $prompt .= "Style requirements: {$styleConfig['description']}. ";
        $prompt .= "Include elements: {$styleConfig['keywords']}. ";
        $prompt .= "Avoid: {$styleConfig['avoid']}. ";
        $prompt .= 'The logo should be professional, scalable, and work well in SVG format. ';
        $prompt .= 'Use a clean white background. The design should be distinctive and memorable.';

        return $prompt;
    }

    /**
     * Set custom rate limiting configuration.
     */
    public function setRateLimit(int $requests, int $windowSeconds): void
    {
        $this->rateLimitRequests = $requests;
        $this->rateLimitWindowSeconds = $windowSeconds;
    }

    /**
     * Check if the OpenAI API is healthy.
     */
    public function checkApiHealth(): bool
    {
        try {
            $apiKey = $this->getApiKey();

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(10)
                ->get('https://api.openai.com/v1/models');

            return $response->successful() && $response->json('data') !== null;
        } catch (\Exception $e) {
            Log::warning('OpenAI API health check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Make API request to DALL-E 3 with retry logic.
     *
     * @return array<string, mixed>
     */
    private function makeApiRequest(string $prompt, string $style): array
    {
        $apiKey = $this->getApiKey();
        $attemptCount = 0;

        while ($attemptCount < self::MAX_RETRY_ATTEMPTS) {
            $attemptCount++;

            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                    ->timeout(120) // DALL-E can take a while
                    ->post('https://api.openai.com/v1/images/generations', [
                        'model' => 'dall-e-3',
                        'prompt' => $prompt,
                        'size' => '1024x1024',
                        'quality' => 'standard',
                        'n' => 1,
                    ]);

                if ($response->successful()) {
                    return $this->processSuccessfulResponse($response->json(), $style);
                }

                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                $errorType = $this->categorizeError($response->status(), $errorMessage);

                // Retry on transient errors
                if ($this->shouldRetry($response->status()) && $attemptCount < self::MAX_RETRY_ATTEMPTS) {
                    sleep(2 ** $attemptCount); // Exponential backoff

                    continue;
                }

                return $this->errorResponse($errorMessage, $errorType, $attemptCount);

            } catch (RequestException|ConnectionException $e) {
                Log::error('OpenAI API request failed', [
                    'attempt' => $attemptCount,
                    'error' => $e->getMessage(),
                    'style' => $style,
                ]);

                if ($attemptCount < self::MAX_RETRY_ATTEMPTS) {
                    sleep(2 ** $attemptCount);

                    continue;
                }

                return $this->errorResponse($e->getMessage(), 'network', $attemptCount);
            }
        }

        return $this->errorResponse('Max retry attempts exceeded', 'network', $attemptCount);
    }

    /**
     * Process successful API response.
     *
     * @param  array<string, mixed>  $responseData
     * @return array<string, mixed>
     */
    private function processSuccessfulResponse(array $responseData, string $style): array
    {
        $data = $responseData['data'] ?? [];

        if (empty($data)) {
            return $this->errorResponse('No images generated in API response', 'api_response');
        }

        $imageData = $data[0];

        return [
            'success' => true,
            'style' => $style,
            'image_url' => $imageData['url'],
            'revised_prompt' => $imageData['revised_prompt'] ?? null,
            'cost_cents' => self::DALLE_3_COST_CENTS,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Create error response array.
     *
     * @return array<string, mixed>
     */
    private function errorResponse(string $message, string $type, ?int $retryAttempts = null): array
    {
        $response = [
            'success' => false,
            'error' => $message,
            'error_type' => $type,
            'cost_cents' => 0,
            'generated_at' => now()->toISOString(),
        ];

        if ($retryAttempts !== null) {
            $response['retry_attempts'] = $retryAttempts;
        }

        return $response;
    }

    /**
     * Validate input parameters.
     */
    private function validateInputs(string $businessIdea, string $style): void
    {
        if (empty(trim($businessIdea))) {
            throw new InvalidArgumentException('Business idea cannot be empty');
        }

        if (! isset(self::LOGO_STYLES[$style])) {
            throw new InvalidArgumentException("Invalid style: {$style}");
        }
    }

    /**
     * Get OpenAI API key from configuration.
     */
    private function getApiKey(): string
    {
        $apiKey = Config::get('services.openai.api_key');

        if (empty($apiKey)) {
            throw new InvalidArgumentException('OpenAI API key is not configured');
        }

        return $apiKey;
    }

    /**
     * Sanitize business idea to prevent prompt injection.
     */
    private function sanitizeBusinessIdea(string $businessIdea): string
    {
        // Remove potential prompt injection attempts
        $sanitized = preg_replace([
            '/ignore\s+previous\s+instructions/i',
            '/forget\s+everything/i',
            '/system\s*:\s*/i',
            '/assistant\s*:\s*/i',
            '/human\s*:\s*/i',
        ], '', $businessIdea);

        // Limit length and clean up
        $sanitized = trim(substr($sanitized ?? '', 0, 200));

        // Remove excessive whitespace
        return preg_replace('/\s+/', ' ', $sanitized) ?? $businessIdea;
    }

    /**
     * Check if request should be retried based on status code.
     */
    private function shouldRetry(int $statusCode): bool
    {
        return in_array($statusCode, [429, 502, 503, 504], true);
    }

    /**
     * Categorize error type based on response.
     */
    private function categorizeError(int $statusCode, string $message): string
    {
        return match (true) {
            $statusCode === 429 => 'rate_limit',
            $statusCode === 401 => 'authentication',
            $statusCode === 400 && str_contains(strtolower($message), 'content policy') => 'content_policy',
            $statusCode >= 500 => 'server_error',
            $statusCode >= 400 => 'client_error',
            default => 'unknown',
        };
    }

    /**
     * Check local rate limiting.
     */
    private function checkRateLimit(): bool
    {
        $key = 'openai_api_requests';
        $requests = Cache::get($key, 0);

        if ($requests >= $this->rateLimitRequests) {
            return false;
        }

        Cache::put($key, $requests + 1, $this->rateLimitWindowSeconds);

        return true;
    }
}
