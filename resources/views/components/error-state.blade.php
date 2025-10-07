@props([
    'errorCode' => null,
    'message' => null,
    'showRetry' => true,
    'retryAction' => null,
    'severity' => 'error',
])

@php
use App\Utils\ErrorMessageFormatter;

$errorCode = $errorCode ?? ErrorMessageFormatter::INTERNAL_ERROR;
$message = $message ?? ErrorMessageFormatter::getMessage($errorCode);
$severity = $severity ?? ErrorMessageFormatter::getSeverity($errorCode);
$isRetryable = ErrorMessageFormatter::isRetryable($errorCode);
$actionSuggestion = ErrorMessageFormatter::getActionSuggestion($errorCode);

$severityClasses = match($severity) {
    'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-900 dark:text-yellow-100',
    'info' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-900 dark:text-blue-100',
    'error' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-900 dark:text-red-100',
    'critical' => 'bg-red-100 dark:bg-red-900/30 border-red-300 dark:border-red-700 text-red-950 dark:text-red-50',
    default => 'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800 text-gray-900 dark:text-gray-100',
};

$iconClasses = match($severity) {
    'warning' => 'text-yellow-600 dark:text-yellow-400',
    'info' => 'text-blue-600 dark:text-blue-400',
    'error' => 'text-red-600 dark:text-red-400',
    'critical' => 'text-red-700 dark:text-red-300',
    default => 'text-gray-600 dark:text-gray-400',
};

$icon = match($severity) {
    'warning' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
    'info' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
    'error', 'critical' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
    default => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
};
@endphp

<div role="alert"
     aria-live="assertive"
     aria-atomic="true"
     class="rounded-lg border p-4 {{ $severityClasses }} {{ $attributes->get('class') }}"
     x-data="{ retrying: false }">

    <!-- Error Icon & Message -->
    <div class="flex items-start gap-3">
        <div class="shrink-0">
            <svg class="h-5 w-5 {{ $iconClasses }}"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="2"
                 aria-hidden="true">
                {!! $icon !!}
            </svg>
        </div>

        <div class="flex-1 space-y-2">
            <!-- Main Error Message -->
            <p class="text-sm font-semibold">
                {{ $message }}
            </p>

            <!-- Action Suggestion -->
            @if($actionSuggestion)
                <p class="text-sm opacity-90">
                    {{ $actionSuggestion }}
                </p>
            @endif

            <!-- Custom Slot Content -->
            @if($slot->isNotEmpty())
                <div class="text-sm">
                    {{ $slot }}
                </div>
            @endif

            <!-- Action Buttons -->
            @if($showRetry && $isRetryable || $retryAction)
                <div class="flex gap-2 mt-3">
                    @if($showRetry && $isRetryable && $retryAction)
                        <button type="button"
                                wire:click="{{ $retryAction }}"
                                x-on:click="retrying = true"
                                x-bind:disabled="retrying"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-md
                                       bg-white dark:bg-gray-800
                                       border border-gray-300 dark:border-gray-600
                                       hover:bg-gray-50 dark:hover:bg-gray-700
                                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                                       disabled:opacity-50 disabled:cursor-not-allowed
                                       transition-colors duration-150">
                            <svg x-show="retrying"
                                 class="animate-spin h-4 w-4"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24">
                                <circle class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"></circle>
                                <path class="opacity-75"
                                      fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>

                            <svg x-show="!retrying"
                                 class="h-4 w-4"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor"
                                 stroke-width="2">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>

                            <span x-text="retrying ? 'Retrying...' : 'Retry'"></span>
                        </button>
                    @endif

                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-md
                              text-gray-700 dark:text-gray-300
                              hover:text-gray-900 dark:hover:text-gray-100
                              hover:underline
                              focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                              transition-colors duration-150">
                        Go to Dashboard
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
