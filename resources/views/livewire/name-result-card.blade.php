@if($suggestion)
<div class="rounded-lg border shadow-sm transition-all duration-300 ease-out hover:shadow-lg transform hover:-translate-y-1
             {{ $suggestion->is_hidden ? 'opacity-60 scale-95' : 'scale-100 hover:scale-[1.02]' }}
             {{ $this->shouldHideForDns ? 'hidden' : '' }}
             focus-within:ring-2 focus-within:outline-none"
     style="background-color: var(--surface-color);
            border-color: var(--text-secondary-color, #6b7280);
            color: var(--text-color);
            {{ $this->isSelected ? 'border-color: var(--primary-color); background-color: var(--primary-color)10; box-shadow: 0 4px 6px var(--primary-color)20;' : '' }}
            focus-within-ring-color: var(--primary-color)50;"
     wire:key="suggestion-{{ $suggestion->id }}"
     x-data="{
         isExpanded: false,
         init() {
             this.isExpanded = false;
         },
         toggle() {
             this.isExpanded = !this.isExpanded;
         }
     }"
    
    <!-- Card Header -->
    <div class="p-4 border-b" style="border-color: var(--text-secondary-color, #6b7280)">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <!-- Selection Indicator -->
                @if($this->isSelected)
                    <div class="w-6 h-6 rounded-full flex items-center justify-center" style="background-color: var(--primary-color)">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                @endif

                <!-- Name -->
                <h3 class="text-lg font-semibold" style="color: {{ $this->isSelected ? 'var(--primary-color)' : 'var(--text-color)' }}">
                    {{ $suggestion->name }}
                </h3>

                <!-- AI Model Badge -->
                @if($this->aiModel)
                    <span class="px-2 py-1 text-xs font-medium rounded-full" style="background-color: var(--background-color); color: var(--text-secondary-color); border: 1px solid var(--text-secondary-color, #6b7280)">
                        {{ $this->aiModel }}
                    </span>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                <!-- Domain & Logo Status -->
                <div class="flex items-center space-x-3 text-sm" style="color: var(--text-secondary-color, #6b7280)">
                    @if($this->hasDomains)
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                            </svg>
                            {{ $this->availableDomainsCount }}/{{ $this->totalDomainsCount }} available
                        </span>
                    @endif

                    <!-- DNS Status Indicator -->
                    @if($this->dnsStatus['checked'])
                        @if($this->dnsStatus['appears_available'])
                            <span class="flex items-center text-green-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                DNS Available
                            </span>
                        @else
                            <span class="flex items-center text-red-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                                DNS Taken
                            </span>
                        @endif
                    @elseif($dnsCheckLoading)
                        <span class="flex items-center text-blue-600">
                            <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Checking DNS...
                        </span>
                    @elseif($this->dnsError)
                        <span class="flex items-center text-red-600">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            DNS Error
                        </span>
                    @else
                        <span class="flex items-center text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            DNS Pending
                        </span>
                    @endif

                    @if($this->hasLogos)
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            {{ $this->logoCount }} logos
                        </span>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-1 sm:space-x-2">
                    <!-- Selection Toggle -->
                    @if($this->isSelected)
                        <flux:button
                            wire:click="deselectName"
                            variant="ghost"
                            size="sm"
                            class="text-primary-600 hover:text-primary-700"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="deselectName">Deselect</span>
                            <span wire:loading wire:target="deselectName">Deselecting...</span>
                        </flux:button>
                    @else
                        <flux:button
                            wire:click="selectName"
                            variant="primary"
                            size="sm"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="selectName">Select</span>
                            <span wire:loading wire:target="selectName">Selecting...</span>
                        </flux:button>
                    @endif

                    <!-- Hide/Show Toggle -->
                    @if($suggestion->is_hidden)
                        <flux:button
                            wire:click="showSuggestion"
                            variant="ghost"
                            size="sm"
                            class="text-green-600 hover:text-green-700"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="showSuggestion">Show</span>
                            <span wire:loading wire:target="showSuggestion">Showing...</span>
                        </flux:button>
                    @else
                        <flux:button
                            wire:click="hideSuggestion"
                            variant="ghost"
                            size="sm"
                            class="text-gray-600 hover:text-gray-700"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="hideSuggestion">Hide</span>
                            <span wire:loading wire:target="hideSuggestion">Hiding...</span>
                        </flux:button>
                    @endif

                    <!-- Expand Toggle -->
                    <flux:button
                        @click.stop="toggle()"
                        variant="ghost"
                        size="sm"
                        class="group"
                    >
                        <svg class="w-4 h-4 transition-all duration-300 ease-out transform group-hover:scale-110"
                             :class="isExpanded ? 'rotate-180' : 'rotate-0'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <!-- Expandable Content -->
    <div class="overflow-hidden transition-all duration-500 ease-in-out border-t"
         style="border-color: var(--text-secondary-color, #6b7280)"
         x-show="isExpanded"
         x-collapse
         x-cloak
         @click.stop>
        <div class="p-4 space-y-4 transform transition-all duration-500 ease-out"
             :class="isExpanded ? 'scale-100 translate-y-0' : 'scale-95 -translate-y-2'">
            <!-- Domains Section -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium" style="color: var(--text-color)">Domains</h4>
                    @if(!$this->hasDomains)
                        <flux:button
                            variant="ghost"
                            size="sm"
                            class="text-primary-600 hover:text-primary-700"
                        >
                            Check Domains
                        </flux:button>
                    @elseif(!$this->dnsStatus['checked'] && !$dnsCheckLoading && !$this->dnsError)
                        <flux:button
                            wire:click="triggerDnsCheck"
                            variant="ghost"
                            size="sm"
                            class="text-primary-600 hover:text-primary-700"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="triggerDnsCheck">Check DNS</span>
                            <span wire:loading wire:target="triggerDnsCheck">Checking...</span>
                        </flux:button>
                    @elseif($this->dnsError)
                        <flux:button
                            wire:click="triggerDnsCheck"
                            variant="ghost"
                            size="sm"
                            class="text-red-600 hover:text-red-700"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="triggerDnsCheck">Retry DNS</span>
                            <span wire:loading wire:target="triggerDnsCheck">Retrying...</span>
                        </flux:button>
                    @endif
                </div>

                @if($this->hasDomains)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                        @foreach($suggestion->domains as $key => $domainData)
                            @php
                                // Handle both formats: associative array (domain service) and indexed array (factory/tests)
                                if (is_string($key) && !is_numeric($key)) {
                                    // Domain service format: key is domain name, value is data
                                    $domainName = $key;
                                    $available = $domainData['available'] ?? null;
                                } else {
                                    // Factory/test format: numeric array with 'extension' field
                                    $domainName = ($suggestion->name ?? 'domain') . ($domainData['extension'] ?? '');
                                    $available = $domainData['available'] ?? null;
                                }
                            @endphp
                            <div class="flex items-center justify-between p-2 rounded-lg border transition-all duration-300 ease-out hover:scale-105 hover:shadow-md transform
                                        {{ $available === true ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20 hover:border-green-300 hover:bg-green-100 dark:hover:bg-green-900/30' : ($available === false ? 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20 hover:border-red-300 hover:bg-red-100 dark:hover:bg-red-900/30' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 hover:border-gray-300 hover:bg-gray-100 dark:hover:bg-gray-750') }}"
                                 x-transition:enter="transition-all duration-{{ 200 + ($loop->index * 50) }} ease-out"
                                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                                <span class="text-sm font-medium transition-colors duration-200 {{ $available === true ? 'text-green-800 dark:text-green-200' : ($available === false ? 'text-red-800 dark:text-red-200' : 'text-gray-800 dark:text-gray-200') }}">
                                    {{ $domainName }}
                                </span>
                                @if($available === true)
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @elseif($available === false)
                                    <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4" style="color: var(--text-secondary-color, #6b7280)">
                        <svg class="w-8 h-8 mx-auto mb-2" style="color: var(--text-secondary-color, #6b7280)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                        </svg>
                        <p class="text-sm">No domains checked yet</p>
                    </div>
                @endif
            </div>

            <!-- Logos Section -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium" style="color: var(--text-color)">Logos</h4>
                    @if(!$this->hasLogos)
                        <flux:button
                            wire:click="generateLogos"
                            variant="ghost"
                            size="sm"
                            class="text-primary-600 hover:text-primary-700"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="generateLogos">Generate Logos</span>
                            <span wire:loading wire:target="generateLogos">Generating...</span>
                        </flux:button>
                    @endif
                </div>

                @if($this->hasLogos)
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        @foreach($suggestion->logos as $logo)
                            <div class="aspect-square rounded-lg border overflow-hidden hover:shadow-md transition-shadow" style="border-color: var(--text-secondary-color, #6b7280)">
                                @if(isset($logo['url']))
                                    <img
                                        src="{{ $logo['url'] }}"
                                        alt="Logo for {{ $suggestion->name }}"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    />
                                @endif
                                @if(isset($logo['style']))
                                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 text-center">
                                        {{ $logo['style'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4" style="color: var(--text-secondary-color, #6b7280)">
                        <svg class="w-8 h-8 mx-auto mb-2" style="color: var(--text-secondary-color, #6b7280)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm">No logos generated yet</p>
                    </div>
                @endif
            </div>

            <!-- DNS Status Section -->
            @if($this->dnsStatus['checked'])
                <div class="pt-2 border-t" style="border-color: var(--text-secondary-color, #6b7280)">
                    <h4 class="font-medium mb-2" style="color: var(--text-color)">DNS Status</h4>
                    <div class="text-sm space-y-1" style="color: var(--text-secondary-color, #6b7280)">
                        <div class="flex justify-between items-center">
                            <span>Status:</span>
                            <span class="{{ $this->dnsStatus['appears_available'] ? 'text-green-600' : 'text-red-600' }}">
                                {{ $this->dnsStatus['appears_available'] ? 'Available' : 'Taken' }}
                            </span>
                        </div>
                        @if($this->dnsStatus['checked_at'])
                            <div class="flex justify-between">
                                <span>Checked:</span>
                                <span>{{ \Carbon\Carbon::parse($this->dnsStatus['checked_at'])->diffForHumans() }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @elseif($this->dnsError)
                <div class="pt-2 border-t" style="border-color: var(--text-secondary-color, #6b7280)">
                    <h4 class="font-medium mb-2 text-red-600">DNS Check Failed</h4>
                    <div class="text-sm space-y-2">
                        <p class="text-red-600">{{ $this->dnsError }}</p>
                        <div class="flex items-center space-x-2">
                            <flux:button
                                wire:click="triggerDnsCheck"
                                variant="ghost"
                                size="sm"
                                class="text-red-600 hover:text-red-700"
                            >
                                Retry DNS Check
                            </flux:button>
                            <flux:button
                                wire:click="clearDnsError"
                                variant="ghost"
                                size="sm"
                                class="text-gray-600 hover:text-gray-700"
                            >
                                Dismiss
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Generation Metadata (if available) -->
            @if($suggestion && $suggestion->generation_metadata && is_array($suggestion->generation_metadata))
                <div class="pt-2 border-t" style="border-color: var(--text-secondary-color, #6b7280)">
                    <details class="group">
                        <summary class="flex items-center cursor-pointer text-sm" style="color: var(--text-secondary-color, #6b7280)">
                            <svg class="w-4 h-4 mr-1 transform group-open:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            Generation Details
                        </summary>
                        <div class="mt-2 pl-5 text-xs" style="color: var(--text-secondary-color, #6b7280)">
                            @foreach($suggestion->generation_metadata as $key => $value)
                                <div class="flex justify-between">
                                    <span class="capitalize">{{ str_replace('_', ' ', $key) }}:</span>
                                    <span>{{ is_array($value) ? json_encode($value) : $value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </details>
                </div>
            @endif
        </div>
    </div>
</div>
@endif