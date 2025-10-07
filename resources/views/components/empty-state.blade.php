@props([
    'icon' => 'document',
    'title' => 'No items found',
    'description' => null,
    'actionText' => null,
    'actionUrl' => null,
])

@php
$iconPaths = [
    'document' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
    'search' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />',
    'folder' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />',
    'star' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />',
    'lightbulb' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />',
    'inbox' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />',
];

$iconPath = $iconPaths[$icon] ?? $iconPaths['document'];
@endphp

<div class="flex flex-col items-center justify-center py-12 px-4 text-center {{ $attributes->get('class') }}"
     role="status"
     aria-label="{{ $title }}">

    <!-- Icon -->
    <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor"
             stroke-width="2"
             aria-hidden="true">
            {!! $iconPath !!}
        </svg>
    </div>

    <!-- Title -->
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
        {{ $title }}
    </h3>

    <!-- Description -->
    @if($description)
        <p class="text-sm text-gray-600 dark:text-gray-400 max-w-md mb-4">
            {{ $description }}
        </p>
    @endif

    <!-- Custom Slot Content -->
    @if($slot->isNotEmpty())
        <div class="text-sm text-gray-600 dark:text-gray-400 max-w-md mb-4">
            {{ $slot }}
        </div>
    @endif

    <!-- Action Button -->
    @if($actionText && $actionUrl)
        <a href="{{ $actionUrl }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md
                  bg-blue-600 text-white
                  hover:bg-blue-700
                  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                  transition-colors duration-150">
            {{ $actionText }}
        </a>
    @endif
</div>
