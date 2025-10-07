@props([
    'field' => null,
    'message' => null,
])

@php
$errorMessage = $message ?? ($field && $errors->has($field) ? $errors->first($field) : null);
@endphp

@if($errorMessage)
    <div role="alert"
         aria-live="polite"
         class="flex items-start gap-2 text-sm text-red-600 dark:text-red-400 mt-1 shake"
         {{ $attributes }}>
        <svg class="h-4 w-4 shrink-0 mt-0.5"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor"
             stroke-width="2"
             aria-hidden="true">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ $errorMessage }}</span>
    </div>
@endif
