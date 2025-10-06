@props([
    'src',
    'alt' => '',
    'class' => '',
    'blurDataUrl' => null,
    'loading' => 'lazy',
    'aspectRatio' => null,
])

<div class="relative overflow-hidden {{ $class }}"
     x-data="{ loaded: false }"
     @if($aspectRatio)
         style="aspect-ratio: {{ $aspectRatio }};"
     @endif>

    {{-- Blur placeholder (if provided) --}}
    @if($blurDataUrl)
        <img
            src="{{ $blurDataUrl }}"
            alt=""
            class="absolute inset-0 w-full h-full object-cover blur-lg scale-110 transition-opacity duration-300"
            :class="{ 'opacity-0': loaded }"
            aria-hidden="true"
        >
    @else
        {{-- Simple background placeholder --}}
        <div
            class="absolute inset-0 bg-gray-200 dark:bg-gray-700 animate-pulse transition-opacity duration-300"
            :class="{ 'opacity-0': loaded }"
            aria-hidden="true"
        ></div>
    @endif

    {{-- Actual image --}}
    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        class="relative w-full h-full object-cover transition-opacity duration-300"
        :class="{ 'opacity-0': !loaded }"
        loading="{{ $loading }}"
        @load="loaded = true"
        {{ $attributes->except(['class', 'src', 'alt', 'loading']) }}
    >
</div>
