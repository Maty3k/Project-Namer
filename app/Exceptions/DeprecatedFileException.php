<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Exception thrown when deprecated code is executed.
 *
 * This exception is used to identify files that are potentially no longer
 * in use and can be safely removed. It should be thrown at the entry point
 * of deprecated classes or methods to detect if they're still being called
 * during normal application usage.
 */
class DeprecatedFileException extends Exception
{
    /**
     * Create a new DeprecatedFileException instance.
     *
     * @param  string  $filePath  The path to the deprecated file
     * @param  string  $reason  The reason why this file is considered deprecated
     */
    public function __construct(string $filePath, string $reason = 'File appears to be unused')
    {
        $message = "DEPRECATED FILE ACCESSED: {$filePath}\nReason: {$reason}\n"
            ."This file has been marked for removal. If you're seeing this exception, "
            .'the file is still in use and should NOT be deleted.';

        parent::__construct($message);

        // Log the deprecation attempt for tracking
        Log::warning('Deprecated file accessed', [
            'file' => $filePath,
            'reason' => $reason,
            'trace' => $this->getTraceAsString(),
        ]);
    }

    /**
     * Report the exception.
     */
    public function report(): bool
    {
        // Return false to use Laravel's default reporting
        return false;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render()
    {
        // Show clear error page in development
        if (config('app.debug')) {
            return response()->view('errors.deprecated-file', [
                'message' => $this->getMessage(),
                'file' => $this->getFile(),
                'line' => $this->getLine(),
            ], 500);
        }

        // In production, show generic error
        return response()->view('errors.500', [], 500);
    }
}
