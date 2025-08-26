<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class LogoGenerationException extends Exception
{
    public function __construct(
        string $message = 'Logo generation failed',
        private readonly string $errorCode = 'GENERATION_FAILED',
        private readonly ?int $retryAfter = null,
        /** @var array<int, array<string, mixed>>|null */
        private readonly ?array $recoveryActions = null,
        private readonly ?string $userGuidance = null,
        int $code = 500,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getRecoveryActions(): ?array
    {
        return $this->recoveryActions;
    }

    public function getUserGuidance(): ?string
    {
        return $this->userGuidance;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'retry_after' => $this->retryAfter,
            'recovery_actions' => $this->recoveryActions,
            'user_guidance' => $this->userGuidance,
            'timestamp' => now()->toISOString(),
        ];
    }

    public static function connectionFailed(): self
    {
        return new self(
            'Logo generation service is temporarily unavailable. Please try again later.',
            'CONNECTION_FAILED',
            60,
            [
                ['label' => 'Retry Generation', 'action' => 'retry'],
                ['label' => 'Check Status Page', 'action' => 'status', 'url' => 'https://status.openai.com'],
            ],
            'This usually resolves within a few minutes. You can also check our status page for service updates.',
            503
        );
    }

    public static function rateLimited(int $retryAfter = 120): self
    {
        return new self(
            'We\'re experiencing high demand. Please wait a moment before trying again.',
            'RATE_LIMITED',
            $retryAfter,
            [
                ['label' => 'Wait and Retry', 'action' => 'retry_after', 'delay' => $retryAfter],
                ['label' => 'Generate Fewer Logos', 'action' => 'reduce_count'],
            ],
            'Try generating fewer logos at once to reduce load, or wait for the busy period to pass.',
            429
        );
    }

    public static function invalidResponse(): self
    {
        return new self(
            'Unable to process logo generation. Our team has been notified.',
            'INVALID_API_RESPONSE',
            null,
            [
                ['label' => 'Try Again', 'action' => 'retry'],
                ['label' => 'Contact Support', 'action' => 'support'],
            ],
            'This is usually a temporary issue. If it persists, please contact our support team.',
            422
        );
    }

    public static function quotaExceeded(): self
    {
        return new self(
            'Logo generation is temporarily limited. Please try again tomorrow.',
            'QUOTA_EXCEEDED',
            null,
            [
                ['label' => 'Try Tomorrow', 'action' => 'retry_tomorrow'],
                ['label' => 'View Existing Logos', 'action' => 'view_existing'],
            ],
            'Your daily generation limit has been reached. Limits reset at midnight UTC.',
            503
        );
    }

    public static function downloadFailed(): self
    {
        return new self(
            'Unable to prepare download. Please try again.',
            'DOWNLOAD_PREPARATION_FAILED',
            null,
            [
                ['label' => 'Try Download Again', 'action' => 'retry_download'],
                ['label' => 'Generate New Logos', 'action' => 'regenerate'],
            ],
            'The file may be temporarily unavailable. Try downloading again or regenerate the logos.',
            503
        );
    }

    public static function fileNotFound(): self
    {
        return new self(
            'Logo file not found. It may have been removed or is being regenerated.',
            'FILE_NOT_FOUND',
            null,
            [
                ['label' => 'Regenerate Logos', 'action' => 'regenerate'],
                ['label' => 'Go Back to Gallery', 'action' => 'back_to_gallery'],
            ],
            'Files are automatically cleaned up after 30 days. You can generate new logos anytime.',
            404
        );
    }

    public static function colorProcessingFailed(): self
    {
        return new self(
            'Unable to apply color scheme. The logo file may be corrupted.',
            'COLOR_PROCESSING_FAILED',
            null,
            [
                ['label' => 'Try Different Color', 'action' => 'try_different_color'],
                ['label' => 'Download Original', 'action' => 'download_original'],
                ['label' => 'Report Issue', 'action' => 'report_issue'],
            ],
            'Try selecting a different color scheme or download the original logo instead.',
            422
        );
    }

    public static function partialFailure(int $completed, int $total): self
    {
        return new self(
            "Generated {$completed} of {$total} logos successfully. Some logos failed to generate.",
            'PARTIAL_FAILURE',
            30,
            [
                ['label' => 'Complete Generation', 'action' => 'complete_generation'],
                ['label' => 'Use Current Logos', 'action' => 'use_current'],
                ['label' => 'Start Over', 'action' => 'restart'],
            ],
            'You can try to complete the remaining logos or use the ones that generated successfully.',
            206
        );
    }

    public static function serviceUnavailable(string $reason = 'maintenance'): self
    {
        $message = match ($reason) {
            'maintenance' => 'Logo generation is temporarily down for maintenance.',
            'overload' => 'Our servers are currently overloaded. Please try again shortly.',
            'api_down' => 'The AI service is temporarily unavailable.',
            default => 'Logo generation service is currently unavailable.',
        };

        return new self(
            $message,
            'SERVICE_UNAVAILABLE',
            300, // 5 minutes
            [
                ['label' => 'Try Again Later', 'action' => 'retry_later'],
                ['label' => 'View Existing Logos', 'action' => 'view_existing'],
                ['label' => 'Check Status', 'action' => 'check_status'],
            ],
            'Service interruptions are usually brief. You can view previously generated logos while you wait.',
            503
        );
    }

    public static function fileSystemError(): self
    {
        return new self(
            'Unable to save logo files. Storage system may be full.',
            'FILESYSTEM_ERROR',
            60,
            [
                ['label' => 'Try Again', 'action' => 'retry'],
                ['label' => 'Contact Support', 'action' => 'support'],
            ],
            'This is usually a temporary storage issue. Please try again in a few minutes.',
            507
        );
    }
}
