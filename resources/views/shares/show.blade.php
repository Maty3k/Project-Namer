<x-layouts.guest 
    title="Shared Content" 
    :metadata="$metadata">

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
    <div class="container mx-auto px-4 py-6
                sm:px-6 sm:py-8
                lg:px-8 lg:py-12">
        <div class="max-w-6xl mx-auto">
            <!-- Share Header -->
            <flux:card class="mb-6
                             sm:mb-8">
                <div class="flex flex-col gap-4
                            sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex-1">
                        @if($share->title && ($share->settings['show_title'] ?? true))
                            <flux:heading size="xl" class="mb-2 text-gray-900 dark:text-gray-100">
                                {{ $share->title }}
                            </flux:heading>
                        @endif
                        
                        @if($share->description && ($share->settings['show_description'] ?? true))
                            <flux:text class="text-gray-600 dark:text-gray-400 mb-4">
                                {{ $share->description }}
                            </flux:text>
                        @endif
                        
                        <div class="flex flex-wrap items-center gap-2
                                    sm:gap-3">
                            <flux:badge variant="secondary" size="sm">
                                <flux:icon name="user" class="w-3 h-3" />
                                {{ $share->user->name ?? 'Anonymous' }}
                            </flux:badge>
                            
                            <flux:badge variant="secondary" size="sm">
                                <flux:icon name="clock" class="w-3 h-3" />
                                {{ $share->created_at->diffForHumans() }}
                            </flux:badge>
                            
                            <flux:badge variant="info" size="sm">
                                <flux:icon name="eye" class="w-3 h-3" />
                                {{ number_format($share->view_count) }} {{ Str::plural('view', $share->view_count) }}
                            </flux:badge>
                        </div>
                    </div>
                    
                    <div class="flex flex-col gap-2
                                sm:items-end">
                        @if($share->expires_at)
                            <flux:badge 
                                variant="{{ $share->expires_at->isPast() ? 'danger' : 'warning' }}"
                                size="sm"
                            >
                                <flux:icon name="clock" class="w-3 h-3" />
                                {{ $share->expires_at->isPast() ? 'Expired' : 'Expires' }} {{ $share->expires_at->diffForHumans() }}
                            </flux:badge>
                        @endif
                        
                        <!-- Share Actions -->
                        <div class="flex gap-2">
                            <flux:button 
                                variant="ghost" 
                                size="xs"
                                onclick="copyShareUrl()"
                            >
                                <flux:icon name="link" />
                                Copy Link
                            </flux:button>
                        </div>
                    </div>
                </div>
            </flux:card>

            <!-- Shareable Content -->
            <flux:card class="overflow-hidden">
                @if($shareable['type'] === 'logo_generation')
                    <div class="space-y-6">
                        <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">
                            Logo Designs for {{ $shareable['business_name'] ?? 'Business' }}
                        </flux:heading>
                        
                        @if($shareable['business_description'])
                            <flux:text class="text-gray-600 dark:text-gray-400">
                                {{ $shareable['business_description'] }}
                            </flux:text>
                        @endif
                        
                        @if(!empty($shareable['logos']))
                            <div class="grid grid-cols-1 gap-4
                                        sm:grid-cols-2 sm:gap-6
                                        lg:grid-cols-3 lg:gap-8">
                                @foreach($shareable['logos'] as $logo)
                                    <div class="group relative">
                                        <flux:card class="h-full hover:shadow-lg transition-shadow duration-200">
                                            <!-- Logo Preview -->
                                            @if($logo['preview_url'])
                                                <div class="aspect-square bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-lg overflow-hidden mb-4">
                                                    <img 
                                                        src="{{ $logo['preview_url'] }}" 
                                                        alt="{{ $logo['style'] }} logo design"
                                                        class="w-full h-full object-contain p-4
                                                               sm:p-6
                                                               lg:p-8"
                                                        loading="lazy"
                                                    >
                                                </div>
                                            @endif
                                            
                                            <!-- Logo Info -->
                                            <div class="space-y-3">
                                                <flux:heading size="sm" class="text-center">
                                                    {{ ucfirst($logo['style']) }} Style
                                                </flux:heading>
                                                
                                                @if($share->settings['allow_downloads'] ?? false)
                                                    <!-- Color Variants -->
                                                    @if(!empty($logo['color_variants']))
                                                        <div class="flex flex-wrap justify-center gap-2">
                                                            @foreach($logo['color_variants'] as $variant)
                                                                <flux:tooltip content="{{ ucfirst($variant['color_scheme']) }} variant">
                                                                    <a 
                                                                        href="{{ $variant['download_url'] }}"
                                                                        download
                                                                        class="w-8 h-8 rounded-full border-2 border-gray-300 dark:border-gray-600 hover:border-blue-500 transition-colors relative overflow-hidden"
                                                                    >
                                                                        <img 
                                                                            src="{{ $variant['preview_url'] }}"
                                                                            alt="{{ $variant['color_scheme'] }}"
                                                                            class="w-full h-full object-cover"
                                                                        >
                                                                    </a>
                                                                </flux:tooltip>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    
                                                    <!-- Download Button -->
                                                    <flux:button 
                                                        href="{{ $logo['download_url'] }}"
                                                        download
                                                        variant="primary"
                                                        size="sm"
                                                        class="w-full"
                                                    >
                                                        <flux:icon name="download" />
                                                        Download Original
                                                    </flux:button>
                                                @else
                                                    <flux:text size="sm" class="text-center text-gray-500 dark:text-gray-400">
                                                        Downloads disabled by owner
                                                    </flux:text>
                                                @endif
                                            </div>
                                        </flux:card>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <flux:callout variant="info">
                                <flux:icon name="information-circle" />
                                No logo designs are available in this share.
                            </flux:callout>
                        @endif
                    </div>
                @else
                    <!-- Generic shareable content display -->
                    <div class="prose prose-gray dark:prose-invert max-w-none">
                        @if(isset($shareable['content']))
                            {!! $shareable['content'] !!}
                        @else
                            <flux:callout variant="info">
                                <flux:icon name="information-circle" />
                                Content is not available at this time.
                            </flux:callout>
                        @endif
                    </div>
                @endif
            </flux:card>

            <!-- Social Sharing Section -->
            <flux:card class="mt-6">
                <div class="text-center space-y-4">
                    <flux:heading size="sm" class="text-gray-900 dark:text-gray-100">
                        Share this page
                    </flux:heading>
                    
                    <div class="flex flex-wrap justify-center gap-3">
                        <!-- Twitter/X Share -->
                        <flux:button
                            href="https://twitter.com/intent/tweet?url={{ urlencode(request()->fullUrl()) }}&text={{ urlencode(($share->settings['show_title'] ?? true) ? ($share->title ?? 'Check out this design') : 'Check out this design') }}"
                            target="_blank"
                            variant="ghost"
                            size="sm"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            Share on X
                        </flux:button>
                        
                        <!-- LinkedIn Share -->
                        <flux:button
                            href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(request()->fullUrl()) }}"
                            target="_blank"
                            variant="ghost"
                            size="sm"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                            Share on LinkedIn
                        </flux:button>
                        
                        <!-- Facebook Share -->
                        <flux:button
                            href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->fullUrl()) }}"
                            target="_blank"
                            variant="ghost"
                            size="sm"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            Share on Facebook
                        </flux:button>
                    </div>
                </div>
            </flux:card>

            <!-- Share Footer -->
            <div class="mt-8 text-center">
                <flux:text size="sm" class="text-gray-500 dark:text-gray-400">
                    Want to create your own AI-powered designs?
                </flux:text>
                <flux:button
                    href="{{ route('home') }}"
                    variant="primary"
                    size="sm"
                    class="mt-2"
                >
                    Get Started Free
                </flux:button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for copy functionality -->
<script>
function copyShareUrl() {
    const url = window.location.href;
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(() => {
            // Show success toast
            showToast('Share URL copied to clipboard!');
        }).catch(() => {
            // Fallback
            fallbackCopyToClipboard(url);
        });
    } else {
        // Fallback for older browsers
        fallbackCopyToClipboard(url);
    }
}

function fallbackCopyToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showToast('Share URL copied to clipboard!');
    } catch (err) {
        showToast('Failed to copy URL', 'error');
    }
    
    document.body.removeChild(textArea);
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}
</script>

</x-layouts.guest>