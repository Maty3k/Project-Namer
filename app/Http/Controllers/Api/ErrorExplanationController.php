<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ErrorExplanationController extends Controller
{
    /**
     * Get detailed explanation for error codes.
     */
    public function show(string $code): JsonResponse
    {
        $explanations = [
            'QUOTA_EXCEEDED' => [
                'code' => 'QUOTA_EXCEEDED',
                'title' => 'Generation Limit Reached',
                'explanation' => 'You\'ve reached the maximum number of logo generations for today.',
                'solution' => 'Your limit will reset at midnight. Consider upgrading for higher limits.',
            ],
            'CONNECTION_FAILED' => [
                'code' => 'CONNECTION_FAILED',
                'title' => 'Service Temporarily Unavailable',
                'explanation' => 'We\'re having trouble connecting to the logo generation service.',
                'solution' => 'Please wait a moment and try again. If the problem persists, check your internet connection.',
            ],
            'RATE_LIMITED' => [
                'code' => 'RATE_LIMITED',
                'title' => 'High Demand',
                'explanation' => 'We\'re experiencing high demand for logo generation.',
                'solution' => 'Please wait a moment before trying again to help manage server load.',
            ],
            'INVALID_API_RESPONSE' => [
                'code' => 'INVALID_API_RESPONSE',
                'title' => 'Processing Error',
                'explanation' => 'We received an unexpected response while generating your logos.',
                'solution' => 'Our team has been notified. Please try again with a different business description.',
            ],
            'DOWNLOAD_PREPARATION_FAILED' => [
                'code' => 'DOWNLOAD_PREPARATION_FAILED',
                'title' => 'Download Error',
                'explanation' => 'We couldn\'t prepare your files for download.',
                'solution' => 'Please try downloading again. If the problem persists, individual file downloads might work better.',
            ],
            'FILE_NOT_FOUND' => [
                'code' => 'FILE_NOT_FOUND',
                'title' => 'File Missing',
                'explanation' => 'The logo file you\'re looking for isn\'t available.',
                'solution' => 'The file may have been moved or is being regenerated. Try refreshing or regenerating the logos.',
            ],
            'COLOR_PROCESSING_FAILED' => [
                'code' => 'COLOR_PROCESSING_FAILED',
                'title' => 'Color Application Error',
                'explanation' => 'We couldn\'t apply the color scheme to your logo.',
                'solution' => 'Try a different color scheme or regenerate the logos if the file appears corrupted.',
            ],
        ];

        $explanation = $explanations[$code] ?? [
            'code' => $code,
            'title' => 'Unknown Error',
            'explanation' => 'An unexpected error occurred.',
            'solution' => 'Please try again or contact support if the problem persists.',
        ];

        return response()->json($explanation);
    }
}
