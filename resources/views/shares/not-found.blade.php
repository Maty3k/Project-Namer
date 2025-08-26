<x-layouts.guest title="Share Not Found" :metadata="$metadata ?? []">

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-lg">
        <flux:card class="text-center">
            <!-- 404 Icon -->
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-red-100 to-red-200 dark:from-red-900 dark:to-red-800 rounded-full mb-4">
                    <flux:icon name="exclamation-triangle" class="w-12 h-12 text-red-600 dark:text-red-400" />
                </div>
                
                <flux:heading size="xl" class="text-gray-900 dark:text-gray-100 mb-2">
                    Share Not Found
                </flux:heading>
                
                <flux:text class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ $message ?? "The share you're looking for doesn't exist or has expired." }}
                </flux:text>
            </div>
            
            <!-- Possible Reasons -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-6 text-left">
                <flux:heading size="sm" class="text-gray-900 dark:text-gray-100 mb-3">
                    This could happen because:
                </flux:heading>
                <ul class="space-y-2">
                    <li class="flex items-start gap-2">
                        <flux:icon name="check-circle" class="w-5 h-5 text-gray-400 mt-0.5" />
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                            The share link has expired
                        </flux:text>
                    </li>
                    <li class="flex items-start gap-2">
                        <flux:icon name="check-circle" class="w-5 h-5 text-gray-400 mt-0.5" />
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                            The share has been deactivated by the owner
                        </flux:text>
                    </li>
                    <li class="flex items-start gap-2">
                        <flux:icon name="check-circle" class="w-5 h-5 text-gray-400 mt-0.5" />
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                            The URL was entered incorrectly
                        </flux:text>
                    </li>
                    <li class="flex items-start gap-2">
                        <flux:icon name="check-circle" class="w-5 h-5 text-gray-400 mt-0.5" />
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                            The content is no longer available
                        </flux:text>
                    </li>
                </ul>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-col gap-3
                        sm:flex-row sm:justify-center">
                <flux:button
                    href="{{ route('home') }}"
                    variant="primary"
                >
                    <flux:icon name="home" />
                    Go to Home
                </flux:button>
                
                <flux:button
                    onclick="window.history.back()"
                    variant="ghost"
                >
                    <flux:icon name="arrow-left" />
                    Go Back
                </flux:button>
            </div>
        </flux:card>
        
        <!-- Help Text -->
        <div class="mt-6 text-center">
            <flux:text size="sm" class="text-gray-500 dark:text-gray-400">
                Need help? Contact the person who shared this link with you.
            </flux:text>
        </div>
    </div>
</div>

</x-layouts.guest>