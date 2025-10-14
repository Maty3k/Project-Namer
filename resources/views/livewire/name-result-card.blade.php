@if($suggestion)
<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm transition-all duration-300 ease-out hover:shadow-lg hover:shadow-gray-200/50 dark:hover:shadow-gray-800/50 transform hover:-translate-y-1
             {{ $this->isSelected ? 'ring-2 ring-blue-500 bg-primary-50 dark:bg-primary-900/10 shadow-lg shadow-blue-200/30 dark:shadow-blue-800/30' : 'hover:border-gray-300 dark:hover:border-gray-600' }}
             {{ $suggestion->is_hidden ? 'opacity-60 scale-95' : 'scale-100 hover:scale-[1.02]' }}
             focus-within:ring-2 focus-within:ring-blue-500/50 focus-within:outline-none"
     x-data="{
         isExpanded: false,
         checkingDomains: false,
         toggle() {
             this.isExpanded = !this.isExpanded;
             // Check domains when card is first expanded
             if (this.isExpanded && !this.checkingDomains) {
                 this.checkDomains();
             }
         },
         checkDomains() {
             // Only check if domains haven't been checked yet
             if (!this.checkingDomains && this.isExpanded) {
                 this.checkingDomains = true;
                 @this.call('checkDomains').then(() => {
                     this.checkingDomains = false;
                 }).catch(() => {
                     this.checkingDomains = false;
                 });
             }
         }
     }"
 
    <!-- Card Header -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <!-- Selection Indicator -->
                @if($this->isSelected)
                    <div class="w-6 h-6 bg-primary-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                @endif

                <!-- Name -->
                <h3 class="text-lg font-semibold transition-all duration-300
                           {{ $this->allDomainsUnavailable ? 'text-red-600 dark:text-red-400 line-through opacity-70' : 'text-gray-900 dark:text-white' }}
                           {{ $this->isSelected ? 'text-primary-900 dark:text-primary-100' : '' }}">
                    {{ $suggestion->name }}
                </h3>

                <!-- Unavailable Indicator -->
                @if($this->allDomainsUnavailable)
                    <div class="flex items-center px-2 py-1 bg-red-100 dark:bg-red-900/30 rounded-full" title="All domains unavailable">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-xs font-medium text-red-600 dark:text-red-400">Unavailable</span>
                    </div>
                @endif

                <!-- AI Model Badge -->
                @if($this->aiModel)
                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-full">
                        {{ $this->aiModel }}
                    </span>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                <!-- Domain & Logo Status -->
                <div class="flex items-center space-x-3 text-sm text-gray-500 dark:text-gray-400">
                    @if($this->hasDomains)
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                            </svg>
                            {{ $this->availableDomainsCount }}/{{ $this->totalDomainsCount }} available
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
    <div class="overflow-hidden transition-all duration-500 ease-in-out border-t border-gray-200 dark:border-gray-700"
         x-show="isExpanded"
         x-collapse
         x-transition:enter="transition-all duration-500 ease-out"
         x-transition:enter-start="opacity-0 max-h-0 -translate-y-4"
         x-transition:enter-end="opacity-100 max-h-screen translate-y-0"
         x-transition:leave="transition-all duration-400 ease-in"
         x-transition:leave-start="opacity-100 max-h-screen translate-y-0"
         x-transition:leave-end="opacity-0 max-h-0 -translate-y-4"
         @click.stop
         style="display: none;">
        <div class="p-4 space-y-4 transform transition-all duration-500 ease-out"
             :class="isExpanded ? 'scale-100 translate-y-0' : 'scale-95 -translate-y-2'">
            <!-- Domains Section -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium text-gray-900 dark:text-white">Domains</h4>

                    <!-- Loading indicator -->
                    <div x-show="checkingDomains" x-cloak class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <svg class="animate-spin w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Checking domains...
                    </div>
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
                                    $hasDNS = $domainData['has_dns_records'] ?? null;
                                    $checkMethod = $domainData['check_method'] ?? null;
                                    $status = $domainData['status'] ?? null;
                                } else {
                                    // Factory/test format: numeric array with 'extension' field
                                    $domainName = ($suggestion->name ?? 'domain') . ($domainData['extension'] ?? '');
                                    $available = $domainData['available'] ?? null;
                                    $hasDNS = $domainData['has_dns_records'] ?? null;
                                    $checkMethod = $domainData['check_method'] ?? null;
                                    $status = $domainData['status'] ?? null;
                                }

                                // Determine tooltip text based on DNS status
                                $tooltipText = '';
                                if ($hasDNS === true) {
                                    $tooltipText = 'Domain has DNS records - Already registered and in use';
                                } elseif ($hasDNS === false) {
                                    $tooltipText = 'No DNS records found - Potentially available';
                                } elseif ($status === 'checking' || $checkMethod === null) {
                                    $tooltipText = 'Checking DNS records...';
                                } elseif ($status === 'error') {
                                    $tooltipText = 'Unable to verify - DNS check failed';
                                } else {
                                    $tooltipText = $available === true ? 'Domain appears available' : ($available === false ? 'Domain is taken' : 'Status unknown');
                                }
                            @endphp
                            <div class="group relative flex items-center justify-between p-2 rounded-lg border transition-all duration-300 ease-out hover:scale-105 hover:shadow-md transform
                                        {{ $available === true ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20 hover:border-green-300 hover:bg-green-100' : ($available === false ? 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20 hover:border-red-300 hover:bg-red-100' : 'border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-800 hover:border-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700') }}"
                                 x-data="{ showTooltip: false }"
                                 @mouseenter="showTooltip = true"
                                 @mouseleave="showTooltip = false"
                                 x-transition:enter="transition-all duration-{{ 200 + ($loop->index * 50) }} ease-out"
                                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                                <div class="flex items-center space-x-2 flex-1">
                                    <span class="text-sm font-medium transition-colors duration-200 {{ $available === true ? 'text-green-800 dark:text-green-200' : ($available === false ? 'text-red-800 dark:text-red-200' : 'text-gray-800 dark:text-gray-200') }}">
                                        {{ $domainName }}
                                    </span>

                                    @if($checkMethod === 'dns')
                                        <span class="px-1.5 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded">
                                            DNS
                                        </span>
                                    @endif
                                </div>

                                <div class="flex items-center space-x-1">
                                    @if($status === 'checking')
                                        <!-- Loading spinner -->
                                        <svg class="animate-spin w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    @elseif($available === true)
                                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    @elseif($available === false)
                                        <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @endif
                                </div>

                                <!-- Tooltip -->
                                <div x-show="showTooltip"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform scale-95"
                                     x-transition:enter-end="opacity-100 transform scale-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 transform scale-100"
                                     x-transition:leave-end="opacity-0 transform scale-95"
                                     class="absolute z-50 bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 text-xs font-medium text-white bg-gray-900 dark:bg-gray-700 rounded-lg shadow-lg whitespace-nowrap pointer-events-none"
                                     style="display: none;">
                                    {{ $tooltipText }}
                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                                        <div class="border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                        </svg>
                        <p class="text-sm">No domains checked yet</p>
                    </div>
                @endif
            </div>

            <!-- Logos Section -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium text-gray-900 dark:text-white">Logos</h4>
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
                            <div class="aspect-square rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
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
                    <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm">No logos generated yet</p>
                    </div>
                @endif
            </div>

            <!-- Generation Metadata (if available) -->
            @if($suggestion && $suggestion->generation_metadata && is_array($suggestion->generation_metadata))
                <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                    <details class="group">
                        <summary class="flex items-center cursor-pointer text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                            <svg class="w-4 h-4 mr-1 transform group-open:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            Generation Details
                        </summary>
                        <div class="mt-2 pl-5 text-xs text-gray-500 dark:text-gray-400">
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