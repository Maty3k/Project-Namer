<div>
    <div class="relative mb-6 w-full max-w-4xl mx-auto">
        <flux:heading size="xl" level="1">{{ __('Keyboard Shortcuts') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Customize and manage keyboard shortcuts') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="max-w-4xl mx-auto">
        <div class="my-6 w-full space-y-8">
            {{-- Shortcuts List --}}
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Available Shortcuts
                </h3>

                @foreach($shortcuts as $action => $shortcut)
                    <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg
                                @if(!$shortcut['enabled']) opacity-50 @endif">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $shortcut['description'] }}
                                </h4>
                                @if(!$shortcut['enabled'])
                                    <span class="text-xs px-2 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                        Disabled
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 mt-2">
                                <code class="text-sm px-2 py-1 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">
                                    {{ $this->formatKeyCombo($action) }}
                                </code>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:button
                                size="sm"
                                variant="ghost"
                                wire:click="openEditModal('{{ $action }}')"
                            >
                                Edit
                            </flux:button>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                wire:click="toggleShortcut('{{ $action }}')"
                            >
                                @if(in_array($action, $disabledShortcuts))
                                    Enable
                                @else
                                    Disable
                                @endif
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Reset Button --}}
            <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Reset to Defaults
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Reset all shortcuts to their default configuration
                    </p>
                </div>
                <flux:button
                    variant="danger"
                    wire:click="resetAllShortcuts"
                    wire:confirm="Are you sure you want to reset all keyboard shortcuts to defaults?"
                >
                    Reset All
                </flux:button>
            </div>

            {{-- Help Text --}}
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p class="text-sm text-blue-900 dark:text-blue-100">
                    <strong>Tip:</strong> Press <kbd class="px-2 py-0.5 text-xs bg-white dark:bg-gray-800 border border-blue-300 dark:border-blue-600 rounded">Ctrl+H</kbd> anytime to view all available keyboard shortcuts.
                </p>
            </div>
        </div>
    </div>

    {{-- Edit Shortcut Modal --}}
    <flux:modal wire:model="showEditModal" class="max-w-md">
        <form wire:submit="saveShortcut">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Edit Keyboard Shortcut</flux:heading>
                    <flux:subheading>
                        @if($editingAction)
                            Customize shortcut for: {{ $shortcuts[$editingAction]['description'] ?? '' }}
                        @endif
                    </flux:subheading>
                </div>

                <flux:separator />

                <div class="space-y-4">
                    <div>
                        <flux:legend>Key</flux:legend>
                        <flux:input
                            wire:model.live="editingKey"
                            placeholder="Press a key..."
                            x-on:keydown.prevent="$wire.captureKey($event.key)"
                        />
                        <flux:description>Press the key you want to use (a-z, 0-9, or special keys)</flux:description>
                    </div>

                    <div>
                        <flux:legend>Modifiers</flux:legend>
                        <div class="space-y-2">
                            <flux:checkbox wire:model.live="editingModifiers.cmd">Ctrl</flux:checkbox>
                            <flux:checkbox wire:model.live="editingModifiers.alt">Alt</flux:checkbox>
                            <flux:checkbox wire:model.live="editingModifiers.shift">Shift</flux:checkbox>
                        </div>
                    </div>

                    @if($editingKey)
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded">
                            <flux:legend>Preview</flux:legend>
                            <div class="mt-2">
                                <code class="text-sm px-2 py-1 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">
                                    {{ $this->getPreviewKeyCombo() }}
                                </code>
                            </div>
                        </div>
                    @endif
                </div>

                <flux:separator />

                <div class="flex justify-between">
                    <flux:button type="button" variant="ghost" wire:click="closeEditModal">Cancel</flux:button>
                    <div class="flex gap-2">
                        <flux:button type="button" variant="ghost" wire:click="resetEditingShortcut">Reset to Default</flux:button>
                        <flux:button type="submit" variant="primary">Save</flux:button>
                    </div>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
