@props([
    'notifications' => [],
    'position' => 'top-right',
])

<div 
    {{ $attributes->merge(['class' => 'fixed z-50 pointer-events-none']) }}
    x-data="{ 
        notifications: @json($notifications),
        removeNotification(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        },
        addNotification(notification) {
            this.notifications.push({
                id: Date.now(),
                type: notification.type || 'info',
                title: notification.title,
                message: notification.message,
                duration: notification.duration || 5000,
                icon: notification.icon,
                actions: notification.actions || []
            });
            
            // Auto-remove notification
            if (notification.duration > 0) {
                setTimeout(() => {
                    this.removeNotification(notification.id || Date.now());
                }, notification.duration);
            }
        }
    }"
    x-init="
        // Listen for Livewire events
        Livewire.on('ai-generation-started', (data) => {
            addNotification({
                type: 'info',
                title: 'AI Generation Started',
                message: `Generating names using ${data.models?.length || 1} AI model(s)`,
                icon: 'cpu-chip',
                duration: 4000
            });
        });
        
        Livewire.on('ai-generation-progress', (data) => {
            if (data.milestone && data.milestone !== 'progress-update') {
                addNotification({
                    type: 'info',
                    title: 'Generation Update',
                    message: data.message || `${data.progress}% complete`,
                    icon: 'arrow-path',
                    duration: 3000
                });
            }
        });
        
        Livewire.on('ai-generation-completed', (data) => {
            addNotification({
                type: 'success',
                title: 'Generation Complete!',
                message: `Successfully generated ${data.totalNames || 'multiple'} names using ${data.modelsUsed || 1} AI model(s)`,
                icon: 'check-circle',
                duration: 6000,
                actions: [
                    { label: 'View Results', action: 'scrollToResults' }
                ]
            });
        });
        
        Livewire.on('ai-generation-error', (data) => {
            addNotification({
                type: 'error',
                title: 'Generation Failed',
                message: data.message || 'An error occurred during AI generation',
                icon: 'exclamation-triangle',
                duration: 8000,
                actions: [
                    { label: 'Try Again', action: 'retryGeneration' }
                ]
            });
        });
        
        Livewire.on('ai-generation-cancelled', (data) => {
            addNotification({
                type: 'warning',
                title: 'Generation Cancelled',
                message: data.message || 'AI generation was cancelled by user',
                icon: 'x-circle',
                duration: 4000
            });
        });
        
        Livewire.on('ai-model-failed', (data) => {
            addNotification({
                type: 'warning',
                title: `${data.model} Failed`,
                message: data.message || 'This model failed but others may still succeed',
                icon: 'exclamation-triangle',
                duration: 5000
            });
        });
        
        Livewire.on('ai-deep-thinking-activated', (data) => {
            addNotification({
                type: 'info',
                title: 'Deep Thinking Mode',
                message: 'Enhanced processing activated for higher quality results',
                icon: 'sparkles',
                duration: 4000
            });
        });
        
        Livewire.on('ai-preferences-saved', (data) => {
            addNotification({
                type: 'success',
                title: 'Preferences Saved',
                message: 'Your AI generation preferences have been updated',
                icon: 'check',
                duration: 3000
            });
        });
        
        Livewire.on('ai-rate-limit-hit', (data) => {
            addNotification({
                type: 'warning',
                title: 'Rate Limit Reached',
                message: `Please wait ${data.retryAfter || 60} seconds before generating again`,
                icon: 'clock',
                duration: 0 // Persistent until manually dismissed
            });
        });
        
        Livewire.on('ai-cost-warning', (data) => {
            addNotification({
                type: 'warning',
                title: 'Cost Alert',
                message: `This generation will cost approximately $${data.estimatedCost}`,
                icon: 'currency-dollar',
                duration: 0,
                actions: [
                    { label: 'Continue', action: 'proceedWithGeneration' },
                    { label: 'Cancel', action: 'cancelGeneration' }
                ]
            });
        });
    "
    @class([
        'top-4 right-4' => $position === 'top-right',
        'top-4 left-4' => $position === 'top-left',
        'bottom-4 right-4' => $position === 'bottom-right',
        'bottom-4 left-4' => $position === 'bottom-left',
        'top-4 left-1/2 transform -translate-x-1/2' => $position === 'top-center',
        'bottom-4 left-1/2 transform -translate-x-1/2' => $position === 'bottom-center',
    ])
>
    <div class="space-y-2 max-w-sm w-full">
        <template x-for="notification in notifications" :key="notification.id">
            <div 
                x-show="true"
                x-transition:enter="transform ease-out duration-300 transition"
                x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="pointer-events-auto"
            >
                <!-- Success Toast -->
                <div x-show="notification.type === 'success'" 
                     class="bg-white dark:bg-gray-800 border-l-4 border-green-400 rounded-lg shadow-lg p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <template x-if="notification.icon === 'check-circle'">
                                <flux:icon.check-circle class="size-6 text-green-400" />
                            </template>
                            <template x-if="notification.icon === 'check'">
                                <flux:icon.check class="size-6 text-green-400" />
                            </template>
                            <template x-if="!notification.icon || (notification.icon !== 'check-circle' && notification.icon !== 'check')">
                                <flux:icon.check-circle class="size-6 text-green-400" />
                            </template>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="notification.title"></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="notification.message"></p>
                            
                            <!-- Actions -->
                            <template x-if="notification.actions && notification.actions.length > 0">
                                <div class="flex space-x-2 mt-3">
                                    <template x-for="action in notification.actions" :key="action.label">
                                        <flux:button 
                                            size="xs" 
                                            variant="filled"
                                            class="bg-green-600 hover:bg-green-700 text-white"
                                            x-text="action.label"
                                            @click="
                                                if (action.action === 'scrollToResults') {
                                                    document.querySelector('[wire\\\\:key*=suggestion]')?.scrollIntoView({ behavior: 'smooth' });
                                                }
                                                removeNotification(notification.id);
                                            "
                                        ></flux:button>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <flux:button 
                                variant="ghost" 
                                size="xs"
                                @click="removeNotification(notification.id)"
                                class="text-gray-400 hover:text-gray-500"
                            >
                                <flux:icon.x-mark class="size-4" />
                            </flux:button>
                        </div>
                    </div>
                </div>

                <!-- Error Toast -->
                <div x-show="notification.type === 'error'" 
                     class="bg-white dark:bg-gray-800 border-l-4 border-red-400 rounded-lg shadow-lg p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <template x-if="notification.icon === 'exclamation-triangle'">
                                <flux:icon.exclamation-triangle class="size-6 text-red-400" />
                            </template>
                            <template x-if="notification.icon === 'x-circle'">
                                <flux:icon.x-circle class="size-6 text-red-400" />
                            </template>
                            <template x-if="!notification.icon || (notification.icon !== 'exclamation-triangle' && notification.icon !== 'x-circle')">
                                <flux:icon.exclamation-triangle class="size-6 text-red-400" />
                            </template>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="notification.title"></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="notification.message"></p>
                            
                            <!-- Actions -->
                            <template x-if="notification.actions && notification.actions.length > 0">
                                <div class="flex space-x-2 mt-3">
                                    <template x-for="action in notification.actions" :key="action.label">
                                        <flux:button 
                                            size="xs" 
                                            variant="filled"
                                            class="bg-red-600 hover:bg-red-700 text-white"
                                            x-text="action.label"
                                            @click="
                                                if (action.action === 'retryGeneration') {
                                                    $wire.call('generateNamesWithAI');
                                                }
                                                removeNotification(notification.id);
                                            "
                                        ></flux:button>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <flux:button 
                                variant="ghost" 
                                size="xs"
                                @click="removeNotification(notification.id)"
                                class="text-gray-400 hover:text-gray-500"
                            >
                                <flux:icon.x-mark class="size-4" />
                            </flux:button>
                        </div>
                    </div>
                </div>

                <!-- Warning Toast -->
                <div x-show="notification.type === 'warning'" 
                     class="bg-white dark:bg-gray-800 border-l-4 border-yellow-400 rounded-lg shadow-lg p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <template x-if="notification.icon === 'exclamation-triangle'">
                                <flux:icon.exclamation-triangle class="size-6 text-yellow-400" />
                            </template>
                            <template x-if="notification.icon === 'clock'">
                                <flux:icon.clock class="size-6 text-yellow-400" />
                            </template>
                            <template x-if="notification.icon === 'currency-dollar'">
                                <flux:icon.currency-dollar class="size-6 text-yellow-400" />
                            </template>
                            <template x-if="notification.icon === 'x-circle'">
                                <flux:icon.x-circle class="size-6 text-yellow-400" />
                            </template>
                            <template x-if="!notification.icon || !['exclamation-triangle', 'clock', 'currency-dollar', 'x-circle'].includes(notification.icon)">
                                <flux:icon.exclamation-triangle class="size-6 text-yellow-400" />
                            </template>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="notification.title"></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="notification.message"></p>
                            
                            <!-- Actions -->
                            <template x-if="notification.actions && notification.actions.length > 0">
                                <div class="flex space-x-2 mt-3">
                                    <template x-for="action in notification.actions" :key="action.label">
                                        <flux:button 
                                            size="xs" 
                                            variant="filled"
                                            class="bg-yellow-600 hover:bg-yellow-700 text-white"
                                            x-text="action.label"
                                            @click="
                                                if (action.action === 'proceedWithGeneration') {
                                                    $wire.call('proceedWithCostlyGeneration');
                                                } else if (action.action === 'cancelGeneration') {
                                                    $wire.call('cancelAIGeneration');
                                                }
                                                removeNotification(notification.id);
                                            "
                                        ></flux:button>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <flux:button 
                                variant="ghost" 
                                size="xs"
                                @click="removeNotification(notification.id)"
                                class="text-gray-400 hover:text-gray-500"
                            >
                                <flux:icon.x-mark class="size-4" />
                            </flux:button>
                        </div>
                    </div>
                </div>

                <!-- Info Toast -->
                <div x-show="notification.type === 'info'" 
                     class="bg-white dark:bg-gray-800 border-l-4 border-blue-400 rounded-lg shadow-lg p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <template x-if="notification.icon === 'information-circle'">
                                <flux:icon.information-circle class="size-6 text-blue-400" />
                            </template>
                            <template x-if="notification.icon === 'cpu-chip'">
                                <flux:icon.cpu-chip class="size-6 text-blue-400" />
                            </template>
                            <template x-if="notification.icon === 'arrow-path'">
                                <flux:icon.arrow-path class="size-6 text-blue-400 animate-spin" />
                            </template>
                            <template x-if="notification.icon === 'sparkles'">
                                <flux:icon.sparkles class="size-6 text-blue-400 animate-pulse" />
                            </template>
                            <template x-if="!notification.icon || !['information-circle', 'cpu-chip', 'arrow-path', 'sparkles'].includes(notification.icon)">
                                <flux:icon.information-circle class="size-6 text-blue-400" />
                            </template>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="notification.title"></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="notification.message"></p>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <flux:button 
                                variant="ghost" 
                                size="xs"
                                @click="removeNotification(notification.id)"
                                class="text-gray-400 hover:text-gray-500"
                            >
                                <flux:icon.x-mark class="size-4" />
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>