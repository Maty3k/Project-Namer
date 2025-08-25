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

    public static function connectionFailed(): self
    {
        return new self(
            'Logo generation service is temporarily unavailable. Please try again later.',
            'CONNECTION_FAILED',
            60,
            503
        );
    }

    public static function rateLimited(int $retryAfter = 120): self
    {
        return new self(
            'We\'re experiencing high demand. Please wait a moment before trying again.',
            'RATE_LIMITED',
            $retryAfter,
            429
        );
    }

    public static function invalidResponse(): self
    {
        return new self(
            'Unable to process logo generation. Our team has been notified.',
            'INVALID_API_RESPONSE',
            null,
            422
        );
    }

    public static function quotaExceeded(): self
    {
        return new self(
            'Logo generation is temporarily limited. Please try again tomorrow.',
            'QUOTA_EXCEEDED',
            null,
            503
        );
    }

    public static function downloadFailed(): self
    {
        return new self(
            'Unable to prepare download. Please try again.',
            'DOWNLOAD_PREPARATION_FAILED',
            null,
            503
        );
    }

    public static function fileNotFound(): self
    {
        return new self(
            'Logo file not found. It may have been removed or is being regenerated.',
            'FILE_NOT_FOUND',
            null,
            404
        );
    }

    public static function colorProcessingFailed(): self
    {
        return new self(
            'Unable to apply color scheme. The logo file may be corrupted.',
            'COLOR_PROCESSING_FAILED',
            null,
            422
        );
    }
}
