<?php

declare(strict_types=1);

namespace App\Utils;

use Illuminate\Http\Response;

/**
 * Utility class for formatting user-friendly error messages.
 */
class ErrorMessageFormatter
{
    /**
     * Error code constants for consistent error handling.
     */
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';

    public const AI_SERVICE_ERROR = 'AI_SERVICE_ERROR';

    public const RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';

    public const PERMISSION_DENIED = 'PERMISSION_DENIED';

    public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';

    public const SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';

    public const DOMAIN_CHECK_FAILED = 'DOMAIN_CHECK_FAILED';

    public const LOGO_GENERATION_FAILED = 'LOGO_GENERATION_FAILED';

    public const EXPORT_FAILED = 'EXPORT_FAILED';

    public const NETWORK_ERROR = 'NETWORK_ERROR';

    public const INTERNAL_ERROR = 'INTERNAL_ERROR';

    /**
     * Get user-friendly error message for error code.
     */
    public static function getMessage(string $errorCode): string
    {
        return match ($errorCode) {
            self::VALIDATION_ERROR => 'Please check your input and try again.',
            self::AI_SERVICE_ERROR => 'Our AI service is temporarily unavailable. Please try again in a few moments.',
            self::RATE_LIMIT_EXCEEDED => 'You\'ve made too many requests. Please wait a moment before trying again.',
            self::PERMISSION_DENIED => 'You don\'t have permission to access this resource.',
            self::RESOURCE_NOT_FOUND => 'The requested resource could not be found.',
            self::SERVICE_UNAVAILABLE => 'This service is temporarily unavailable. Please try again later.',
            self::DOMAIN_CHECK_FAILED => 'We couldn\'t check domain availability at this time. Please try again.',
            self::LOGO_GENERATION_FAILED => 'Logo generation failed. Please try again or contact support if the issue persists.',
            self::EXPORT_FAILED => 'Export failed. Please try again or use a different format.',
            self::NETWORK_ERROR => 'Network connection issue. Please check your internet connection and try again.',
            self::INTERNAL_ERROR => 'Something went wrong on our end. Our team has been notified.',
            default => 'An unexpected error occurred. Please try again.',
        };
    }

    /**
     * Get HTTP status code for error code.
     */
    public static function getHttpStatus(string $errorCode): int
    {
        return match ($errorCode) {
            self::VALIDATION_ERROR => Response::HTTP_UNPROCESSABLE_ENTITY,
            self::AI_SERVICE_ERROR => Response::HTTP_SERVICE_UNAVAILABLE,
            self::RATE_LIMIT_EXCEEDED => Response::HTTP_TOO_MANY_REQUESTS,
            self::PERMISSION_DENIED => Response::HTTP_FORBIDDEN,
            self::RESOURCE_NOT_FOUND => Response::HTTP_NOT_FOUND,
            self::SERVICE_UNAVAILABLE => Response::HTTP_SERVICE_UNAVAILABLE,
            self::DOMAIN_CHECK_FAILED => Response::HTTP_BAD_GATEWAY,
            self::LOGO_GENERATION_FAILED => Response::HTTP_BAD_GATEWAY,
            self::EXPORT_FAILED => Response::HTTP_INTERNAL_SERVER_ERROR,
            self::NETWORK_ERROR => Response::HTTP_BAD_GATEWAY,
            self::INTERNAL_ERROR => Response::HTTP_INTERNAL_SERVER_ERROR,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }

    /**
     * Format error response array.
     *
     * @param  array<string, mixed>  $additionalData
     * @return array<string, mixed>
     */
    public static function formatResponse(string $errorCode, ?string $customMessage = null, array $additionalData = []): array
    {
        return array_merge([
            'error' => true,
            'error_code' => $errorCode,
            'message' => $customMessage ?? self::getMessage($errorCode),
            'timestamp' => now()->toIso8601String(),
        ], $additionalData);
    }

    /**
     * Check if error is retryable.
     */
    public static function isRetryable(string $errorCode): bool
    {
        return in_array($errorCode, [
            self::AI_SERVICE_ERROR,
            self::SERVICE_UNAVAILABLE,
            self::DOMAIN_CHECK_FAILED,
            self::LOGO_GENERATION_FAILED,
            self::NETWORK_ERROR,
        ]);
    }

    /**
     * Get suggested retry delay in seconds.
     */
    public static function getRetryDelay(string $errorCode): int
    {
        return match ($errorCode) {
            self::RATE_LIMIT_EXCEEDED => 60,
            self::AI_SERVICE_ERROR, self::SERVICE_UNAVAILABLE => 30,
            self::DOMAIN_CHECK_FAILED, self::LOGO_GENERATION_FAILED => 15,
            self::NETWORK_ERROR => 5,
            default => 0,
        };
    }

    /**
     * Get error severity level.
     */
    public static function getSeverity(string $errorCode): string
    {
        return match ($errorCode) {
            self::VALIDATION_ERROR => 'warning',
            self::RATE_LIMIT_EXCEEDED => 'warning',
            self::PERMISSION_DENIED, self::RESOURCE_NOT_FOUND => 'info',
            self::AI_SERVICE_ERROR, self::SERVICE_UNAVAILABLE => 'error',
            self::DOMAIN_CHECK_FAILED, self::LOGO_GENERATION_FAILED => 'error',
            self::NETWORK_ERROR => 'error',
            self::INTERNAL_ERROR, self::EXPORT_FAILED => 'critical',
            default => 'error',
        };
    }

    /**
     * Get user-friendly action suggestion.
     */
    public static function getActionSuggestion(string $errorCode): ?string
    {
        return match ($errorCode) {
            self::VALIDATION_ERROR => 'Please review the highlighted fields and correct any issues.',
            self::AI_SERVICE_ERROR => 'Wait a few moments and click the retry button.',
            self::RATE_LIMIT_EXCEEDED => 'Wait for the cooldown period to expire, then try again.',
            self::PERMISSION_DENIED => 'Contact the resource owner for access.',
            self::RESOURCE_NOT_FOUND => 'Check the URL and try again, or return to the dashboard.',
            self::SERVICE_UNAVAILABLE => 'Check our status page for updates.',
            self::DOMAIN_CHECK_FAILED => 'Try checking fewer domains at once.',
            self::LOGO_GENERATION_FAILED => 'Try generating with a different style or business name.',
            self::EXPORT_FAILED => 'Try exporting to a different format (PDF, CSV, or JSON).',
            self::NETWORK_ERROR => 'Check your internet connection and try again.',
            self::INTERNAL_ERROR => 'If the issue persists, please contact support.',
            default => null,
        };
    }
}
