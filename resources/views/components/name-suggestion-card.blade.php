@props(['suggestion', 'isSelected' => false])

@if($suggestion)
<div class="rounded-lg border shadow-sm transition-all duration-300 ease-out hover:shadow-lg transform hover:-translate-y-1 focus-within:ring-2 focus-within:outline-none @if($suggestion->is_hidden) opacity-60 scale-95 @else scale-100 hover:scale-[1.02] @endif"
     style="background-color: var(--surface-color);
            border-color: var(--text-secondary-color, #6b7280);
            color: var(--text-color);
            @if($isSelected) border-color: var(--primary-color); background-color: var(--primary-color)10; box-shadow: 0 4px 6px var(--primary-color)20; @endif
            focus-within-ring-color: var(--primary-color)50;"
     x-data="{
         isExpanded: false,
         init() {
             this.isExpanded = false;
         },
         toggle() {
             // If expanding for the first time, trigger domain checking
             if (!this.isExpanded) {
                 $wire.call('checkDomainsForSuggestion', '{{ $suggestion->id }}');
             }
             this.isExpanded = !this.isExpanded;
         }
     }"
     x-cloak>

    <!-- Card Header -->
    <div class="p-4 border-b" style="border-color: var(--text-secondary-color, #6b7280)">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <!-- Selection Indicator -->
                @if($isSelected)
                    <div class="w-6 h-6 rounded-full flex items-center justify-center" style="background-color: var(--primary-color)">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                @endif

                <!-- Name -->
                <h3 class="text-lg font-semibold" style="color: {{ $isSelected ? 'var(--primary-color)' : 'var(--text-color)' }}">
                    {{ $suggestion->name }}
                </h3>

                <!-- AI Model Badge -->
                @if($suggestion->generation_metadata && isset($suggestion->generation_metadata['ai_model']))
                    <span class="px-2 py-1 text-xs font-medium rounded-full" style="background-color: var(--background-color); color: var(--text-secondary-color); border: 1px solid var(--text-secondary-color, #6b7280)">
                        {{ $suggestion->generation_metadata['ai_model'] }}
                    </span>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                <!-- Domain & Logo Status -->
                <div class="flex items-center space-x-3 text-sm" style="color: var(--text-secondary-color, #6b7280)">
                    @if($suggestion->domains && count($suggestion->domains) > 0)
                        <span class="flex items-center">
                            <x-app-icon name="globe" size="sm" class="mr-1" />
                            {{ count($suggestion->domains) }} domains
                        </span>
                    @endif
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-2">
                    <!-- Select/Deselect Button -->
                    @if($isSelected)
                        <flux:button
                            wire:click="deselectName({{ $suggestion->id }})"
                            variant="outline"
                            size="sm">
                            Deselect
                        </flux:button>
                    @else
                        <flux:button
                            wire:click="selectName({{ $suggestion->id }})"
                            variant="primary"
                            size="sm">
                            Select
                        </flux:button>
                    @endif

                    <!-- Toggle Details Button -->
                    <flux:button
                        @click="toggle()"
                        variant="ghost"
                        size="sm"
                        ::class="'transition-transform duration-200' + (isExpanded ? ' rotate-180' : '')">
                        <x-app-icon name="chevron-down" size="sm" />
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <!-- Expandable Details -->
    <div x-show="isExpanded"
         x-collapse.duration.300ms
         class="border-t"
         style="border-color: var(--text-secondary-color, #6b7280)">

        <!-- Domains Section -->
        @if($suggestion->domains && count($suggestion->domains) > 0)
            <div class="p-3">
                <h4 class="font-medium mb-2" style="color: var(--text-color)">Domain Availability</h4>
                <div class="space-y-1">
                    @foreach($suggestion->domains as $domain => $info)
                        <div class="flex items-center justify-between px-3 py-1.5 rounded-md text-sm" style="background-color: var(--background-color)">
                            <span class="font-mono">{{ $domain }}</span>
                            <span class="text-xs px-2 py-1 rounded-full
                                @if(isset($info['available']) && $info['available'] === true)
                                    bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif(isset($info['available']) && $info['available'] === false)
                                    bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @else
                                    bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                @endif">
                                @if(isset($info['available']) && $info['available'] === true)
                                    Available
                                @elseif(isset($info['available']) && $info['available'] === false)
                                    Taken
                                @else
                                    Checking...
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Actions Section -->
        <div class="p-4 border-t" style="border-color: var(--text-secondary-color, #6b7280)">
            <div class="flex flex-wrap gap-2">
                @if($suggestion->is_hidden)
                    <flux:button
                        wire:click="showSuggestion({{ $suggestion->id }})"
                        variant="outline"
                        size="sm">
                        <x-app-icon name="eye" size="sm" class="mr-1" />
                        Show
                    </flux:button>
                @else
                    <flux:button
                        wire:click="hideSuggestion({{ $suggestion->id }})"
                        variant="outline"
                        size="sm">
                        <x-app-icon name="eye-slash" size="sm" class="mr-1" />
                        Hide
                    </flux:button>
                @endif

                <flux:button
                    wire:click="deleteSuggestion({{ $suggestion->id }})"
                    variant="danger"
                    size="sm">
                    <x-app-icon name="trash" size="sm" class="mr-1" />
                    Delete
                </flux:button>
            </div>
        </div>
    </div>
</div>
@endif