{{-- Modern Mobile Header - Profile button only, positioned at top --}}
<div class="lg:hidden fixed top-4 right-4 z-50">
    {{-- User Profile Button - Click to go to Settings --}}
    <a href="{{ route('settings.profile') }}" wire:navigate>
        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors cursor-pointer">
            <span class="text-sm font-medium text-zinc-900 dark:text-white">
                {{ auth()->user()->initials() }}
            </span>
        </div>
    </a>
</div>
