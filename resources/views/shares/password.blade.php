<x-layouts.guest title="Password Required" :metadata="$metadata ?? []">

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-md">
        <flux:card class="relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-5">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%239C92AC" fill-opacity="0.4"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <!-- Content -->
            <div class="relative z-10">
                <!-- Icon and Header -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full mb-4">
                        <flux:icon name="lock-closed" class="w-8 h-8 text-white" />
                    </div>
                    
                    <flux:heading size="lg" class="text-gray-900 dark:text-gray-100 mb-2">
                        Password Required
                    </flux:heading>
                    
                    <flux:text class="text-gray-600 dark:text-gray-400">
                        This share is password protected. Please enter the password to continue.
                    </flux:text>
                </div>
                
                <!-- Password Form -->
                <form method="POST" action="{{ route('public-share.authenticate', $share->uuid) }}" class="space-y-6">
                    @csrf
                    
                    <flux:field>
                        <flux:label for="password">Password</flux:label>
                        <flux:input 
                            type="password"
                            id="password"
                            name="password"
                            required
                            autofocus
                            placeholder="Enter password"
                            class="w-full"
                            :error="$errors->has('password')"
                        />
                        @error('password')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                    
                    <!-- Submit Button -->
                    <flux:button 
                        type="submit"
                        variant="primary"
                        class="w-full"
                    >
                        <flux:icon name="lock-open" />
                        Unlock Content
                    </flux:button>
                </form>
                
                <!-- Help Text -->
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <flux:text size="sm" class="text-center text-gray-500 dark:text-gray-400">
                        Don't have the password?
                    </flux:text>
                    <div class="mt-2 text-center">
                        <flux:button
                            href="{{ route('home') }}"
                            variant="ghost"
                            size="sm"
                        >
                            <flux:icon name="arrow-left" />
                            Return to Home
                        </flux:button>
                    </div>
                </div>
            </div>
        </flux:card>
        
        <!-- Additional Information -->
        @if(($share->title && ($share->settings['show_title'] ?? true)) || ($share->description && ($share->settings['show_description'] ?? true)))
            <flux:card class="mt-4">
                <div class="text-center">
                    @if($share->title && ($share->settings['show_title'] ?? true))
                        <flux:heading size="sm" class="text-gray-900 dark:text-gray-100 mb-1">
                            {{ $share->title }}
                        </flux:heading>
                    @endif
                    
                    @if($share->description && ($share->settings['show_description'] ?? true))
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                            {{ Str::limit($share->description, 150) }}
                        </flux:text>
                    @endif
                    
                    <div class="mt-3 flex justify-center gap-3">
                        <flux:badge variant="secondary" size="xs">
                            <flux:icon name="user" class="w-3 h-3" />
                            {{ $share->user->name ?? 'Anonymous' }}
                        </flux:badge>
                        
                        <flux:badge variant="secondary" size="xs">
                            <flux:icon name="clock" class="w-3 h-3" />
                            {{ $share->created_at->diffForHumans() }}
                        </flux:badge>
                    </div>
                </div>
            </flux:card>
        @endif
    </div>
</div>

<!-- Auto-focus Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus password field
    const passwordField = document.getElementById('password');
    if (passwordField) {
        passwordField.focus();
    }
    
    // Handle form submission animation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.innerHTML = '<svg class="inline-block w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Verifying...';
            }
        });
    }
});
</script>

</x-layouts.guest>