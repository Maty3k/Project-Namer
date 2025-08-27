@props(['currentRoute' => null])

{{-- Mobile Bottom Action Bar --}}
<div {{ $attributes->merge(['class' => 'fixed bottom-0 left-0 right-0 z-40 glass shadow-soft-xl backdrop-blur-xl border-t border-white/20 dark:border-white/10 lg:hidden']) }}>
    <div class="flex items-center justify-around py-2 px-4 max-w-md mx-auto">
        
        {{-- Generate Names Action --}}
        <a href="{{ route('dashboard') }}" 
           wire:navigate
           class="flex flex-col items-center justify-center touch-target btn-modern focus-modern transition-all duration-300
                  {{ request()->routeIs('dashboard') ? 'text-accent' : 'text-gray-600 dark:text-gray-400' }}"
           aria-label="Generate Names">
            <div class="w-6 h-6 mb-1 flex items-center justify-center">
                @if(request()->routeIs('dashboard'))
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                @endif
            </div>
            <span class="text-xs font-medium">Generate</span>
        </a>

        {{-- History Action --}}
        <button type="button"
                onclick="document.querySelector('[wire\\:click=\"toggleHistory\"]')?.click()"
                class="flex flex-col items-center justify-center touch-target btn-modern focus-modern transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-accent"
                aria-label="View History">
            <div class="w-6 h-6 mb-1 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-xs font-medium">History</span>
        </button>

        {{-- Logo Gallery Action --}}
        <a href="#" 
           onclick="window.location.href = '{{ url('/logo-gallery/placeholder') }}'"
           class="flex flex-col items-center justify-center touch-target btn-modern focus-modern transition-all duration-300 text-gray-600 dark:text-gray-400"
           aria-label="Logo Gallery">
            <div class="w-6 h-6 mb-1 flex items-center justify-center">
                @if(request()->routeIs('logo-gallery.*'))
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                @endif
            </div>
            <span class="text-xs font-medium">Logos</span>
        </a>

        {{-- Share/Export Action --}}
        <button type="button"
                onclick="document.querySelector('[x-data]')?.dispatchEvent(new CustomEvent('open-share-modal'))"
                class="flex flex-col items-center justify-center touch-target btn-modern focus-modern transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-accent"
                aria-label="Share Results">
            <div class="w-6 h-6 mb-1 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                </svg>
            </div>
            <span class="text-xs font-medium">Share</span>
        </button>
        
        {{-- Settings Action --}}
        <a href="{{ route('settings.profile') }}" 
           wire:navigate
           class="flex flex-col items-center justify-center touch-target btn-modern focus-modern transition-all duration-300
                  {{ request()->routeIs('settings.*') ? 'text-accent' : 'text-gray-600 dark:text-gray-400' }}"
           aria-label="Settings">
            <div class="w-6 h-6 mb-1 flex items-center justify-center">
                @if(request()->routeIs('settings.*'))
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M11.828 2.25c-.916 0-1.699.663-1.85 1.567l-.091.549a.798.798 0 01-.517.608 7.45 7.45 0 00-.478.198.798.798 0 01-.796-.064l-.453-.324a1.875 1.875 0 00-2.416.2l-.243.243a1.875 1.875 0 00-.2 2.416l.324.453a.798.798 0 01.064.796 7.448 7.448 0 00-.198.478.798.798 0 01-.608.517l-.549.091A1.875 1.875 0 002.25 11.828v.344c0 .916.663 1.699 1.567 1.85l.549.091c.281.047.508.25.608.517.06.162.127.321.198.478a.798.798 0 01-.064.796l-.324.453a1.875 1.875 0 00.2 2.416l.243.243c.648.648 1.67.733 2.416.2l.453-.324a.798.798 0 01.796-.064c.157.071.316.138.478.198.267.1.47.327.517.608l.091.549c.151.904.934 1.567 1.85 1.567h.344c.916 0 1.699-.663 1.85-1.567l.091-.549a.798.798 0 01.517-.608 7.52 7.52 0 00.478-.198.798.798 0 01.796.064l.453.324a1.875 1.875 0 002.416-.2l.243-.243c.648-.648.733-1.67.2-2.416l-.324-.453a.798.798 0 01-.064-.796c.071-.157.138-.316.198-.478.1-.267.327-.47.608-.517l.549-.091A1.875 1.875 0 0021.75 12.172v-.344c0-.916-.663-1.699-1.567-1.85l-.549-.091a.798.798 0 01-.608-.517 7.507 7.507 0 00-.198-.478.798.798 0 01.064-.796l.324-.453a1.875 1.875 0 00-.2-2.416l-.243-.243a1.875 1.875 0 00-2.416-.2l-.453.324a.798.798 0 01-.796.064 7.462 7.462 0 00-.478-.198.798.798 0 01-.517-.608l-.091-.549A1.875 1.875 0 0012.172 2.25h-.344zM12 15.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z" clip-rule="evenodd"/>
                    </svg>
                @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                @endif
            </div>
            <span class="text-xs font-medium">Settings</span>
        </a>
    </div>
</div>

{{-- Add bottom padding to body content to prevent overlap --}}
<style>
    @media (max-width: 1024px) {
        body {
            padding-bottom: 80px;
        }
    }
</style>