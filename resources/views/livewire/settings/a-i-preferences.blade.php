<div class="space-y-6">
    <x-partials.settings-heading>
        <x-slot:title>AI Preferences</x-slot:title>
        <x-slot:description>
            Manage your AI name generation preferences. These settings will be automatically applied when you create new projects.
        </x-slot:description>
    </x-partials.settings-heading>

    @if($hasPreferences)
        <!-- Current Preferences Display -->
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Current Preferences</h3>

            <div class="space-y-4">
                <!-- Preferred Models -->
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preferred AI Models</h4>
                    <div class="flex flex-wrap gap-2">
                        @if(!empty($selectedAIModels))
                            @foreach($selectedAIModels as $model)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200">
                                    @switch($model)
                                        @case('gpt-4')
                                            GPT-4
                                            @break
                                        @case('claude-3.5-sonnet')
                                            Claude 3.5 Sonnet
                                            @break
                                        @case('gemini-1.5-pro')
                                            Gemini 1.5 Pro
                                            @break
                                        @case('grok-beta')
                                            Grok
                                            @break
                                        @default
                                            {{ $model }}
                                    @endswitch
                                </span>
                            @endforeach
                        @else
                            <span class="text-gray-500 dark:text-gray-400 text-sm">No models selected</span>
                        @endif
                    </div>
                </div>

                <!-- Generation Mode -->
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Default Generation Style</h4>
                    <div class="flex items-center gap-2">
                        @if($generationMode)
                            @php
                                $modeConfig = match($generationMode) {
                                    'creative' => ['emoji' => '🎨', 'label' => 'Creative'],
                                    'professional' => ['emoji' => '💼', 'label' => 'Professional'],
                                    'brandable' => ['emoji' => '🚀', 'label' => 'Brandable'],
                                    'tech-focused' => ['emoji' => '⚡', 'label' => 'Tech-Focused'],
                                    default => ['emoji' => '❓', 'label' => ucfirst($generationMode)],
                                };
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200">
                                <span class="mr-2">{{ $modeConfig['emoji'] }}</span>
                                {{ $modeConfig['label'] }}
                            </span>
                        @else
                            <span class="text-gray-500 dark:text-gray-400 text-sm">No default style set</span>
                        @endif
                    </div>
                </div>

                <!-- Deep Thinking -->
                @if($deepThinking)
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Advanced Features</h4>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">
                            🧠 Deep Thinking Mode Enabled
                        </span>
                    </div>
                @endif
            </div>

            <!-- Clear Button -->
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <flux:button
                    wire:click="clearPreferences"
                    variant="danger"
                    wire:confirm="Are you sure you want to clear your AI preferences? This will remove all saved settings and you'll start with a blank slate on new projects."
                >
                    Clear All Preferences
                </flux:button>
            </div>
        </div>
    @else
        <!-- No Preferences State -->
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
            <div class="text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Preferences Saved</h3>
                <p class="text-sm mb-4">You haven't saved any AI preferences yet.</p>
                <p class="text-sm">
                    To save preferences, go to any project, select your preferred AI models and generation style, then click "Save Preferences".
                </p>
            </div>
        </div>
    @endif

    <!-- Info Box -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    How AI Preferences Work
                </h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    <p>
                        Your AI preferences are automatically applied to new projects, saving you time by pre-selecting your favorite AI models and generation styles.
                        You can always override these settings on individual projects.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
