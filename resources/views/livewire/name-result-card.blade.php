@if($suggestion)
<div wire:key="name-card-{{ $suggestion->id }}"
     class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm transition-all duration-300 ease-out hover:shadow-lg hover:shadow-gray-200/50 dark:hover:shadow-gray-800/50 transform hover:-translate-y-1
            {{ $this->isSelected ? 'ring-2 ring-blue-500 bg-primary-50 dark:bg-primary-900/10 shadow-lg shadow-blue-200/30 dark:shadow-blue-800/30' : 'hover:border-gray-300 dark:hover:border-gray-600' }}
            scale-100 hover:scale-[1.02]
            focus-within:ring-2 focus-within:ring-blue-500/50 focus-within:outline-none"
     x-data="{
        isExpanded: false,
        checkingDomains: false,
        suggestionId: {{ $suggestion->id }},
       suggestionName: {{ Js::from($suggestion->name) }},
       domainsChecked: false,
       domains: {},
       // Logo state - similar to domains
       generatingLogos: false,
       logos: {{ Js::from($suggestion->logos ?? []) }},
       logoGenerationId: null,
       pollInterval: null,
       get availableCount() {
           return Object.values(this.domains || {}).filter(d => d?.available === true).length;
       },
       get unavailableCount() {
           return Object.values(this.domains || {}).filter(d => d?.available === false).length;
       },
       get totalCount() {
           return Object.keys(this.domains || {}).length;
       },
       get hasLogos() {
           return Array.isArray(this.logos) && this.logos.length > 0;
       },
       getDomainName(key, domainData) {
           // Handle both formats: associative (domain service) and indexed (factory/tests)
           if (typeof key === 'string' && isNaN(parseInt(key))) {
               return key;
           } else {
               return this.suggestionName + (domainData?.extension || '');
           }
       },
        toggle() {
            this.isExpanded = !this.isExpanded;
            // Check domains when card is first expanded
           if (this.isExpanded && !this.domainsChecked && !this.checkingDomains) {
                this.checkDomains();
            }
        },
        async checkDomains() {
            // Check if already checking to prevent duplicate requests
           if (this.checkingDomains) {
               return;
           }

           this.checkingDomains = true;

           try {
               // Call API directly instead of Livewire event
               const csrfMeta = document.querySelector('meta[name=csrf-token]');
               const csrfToken = csrfMeta ? csrfMeta.content : '';
               const response = await fetch(`/api/suggestions/${this.suggestionId}/check-domains`, {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/json',
                       'X-CSRF-TOKEN': csrfToken,
                       'Accept': 'application/json',
                       'X-Requested-With': 'XMLHttpRequest'
                   },
                   credentials: 'same-origin'
               });

               if (response.ok) {
                   const data = await response.json();

                   if (data.domains && typeof data.domains === 'object') {
                       // Force Alpine reactivity by creating a new object reference
                       this.domains = {...data.domains};
                       this.domainsChecked = true;
                   }

                   this.checkingDomains = false;
               } else {
                   this.checkingDomains = false;
               }
           } catch (error) {
               this.checkingDomains = false;
           }
       },
       async startLogoGeneration() {
           if (this.generatingLogos) return;
           this.generatingLogos = true;

           try {
               const csrfMeta = document.querySelector('meta[name=csrf-token]');
               const csrfToken = csrfMeta ? csrfMeta.content : '';
               const response = await fetch(`/api/logos/generate`, {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/json',
                       'X-CSRF-TOKEN': csrfToken,
                       'Accept': 'application/json',
                       'X-Requested-With': 'XMLHttpRequest'
                   },
                   credentials: 'same-origin',
                   body: JSON.stringify({
                       business_name: this.suggestionName,
                       business_description: ''
                   })
               });

               if (response.ok) {
                   const data = await response.json();

                   if (data.success && data.logo_generation_id) {
                       this.logoGenerationId = data.logo_generation_id;

                       // Show success toast
                       if (window.Livewire) {
                           window.Livewire.dispatch('show-toast', {
                               message: 'Generating 4 logos! This may take a minute...',
                               type: 'success'
                           });
                       }

                       // Start polling for completion
                       this.startLogoPolling();
                   } else {
                       this.generatingLogos = false;
                   }
               } else {
                   this.generatingLogos = false;
                   if (window.Livewire) {
                       window.Livewire.dispatch('show-toast', {
                           message: 'Failed to start logo generation. Please try again.',
                           type: 'error'
                       });
                   }
               }
           } catch (error) {
               this.generatingLogos = false;
               if (window.Livewire) {
                   window.Livewire.dispatch('show-toast', {
                       message: 'Failed to start logo generation. Please try again.',
                       type: 'error'
                   });
               }
           }
       },
       startLogoPolling() {
           // Poll every 5 seconds to check logo generation status
           this.pollInterval = setInterval(async () => {
               await this.checkLogoStatus();
           }, 5000);
       },
       async checkLogoStatus() {
           if (!this.logoGenerationId) return;

           try {
               const response = await fetch(`/api/logos/${this.logoGenerationId}`, {
                   headers: {
                       'Accept': 'application/json',
                       'X-Requested-With': 'XMLHttpRequest'
                   }
               });

               if (response.ok) {
                   const data = await response.json();

                   if (data.status === 'completed') {
                       clearInterval(this.pollInterval);
                       this.generatingLogos = false;

                       // Update logos array with the generated logos
                       if (data.logos && Array.isArray(data.logos)) {
                           this.logos = data.logos;
                       }

                       // Show completion toast
                       if (window.Livewire) {
                           window.Livewire.dispatch('show-toast', {
                               message: '4 logos generated successfully!',
                               type: 'success'
                           });
                       }
                   } else if (data.status === 'failed') {
                       clearInterval(this.pollInterval);
                       this.generatingLogos = false;

                       if (window.Livewire) {
                           window.Livewire.dispatch('show-toast', {
                               message: 'Logo generation failed. Please try again.',
                               type: 'error'
                           });
                       }
                   }
               }
           } catch (error) {
               // Silently fail - polling will continue
           }
       },
       async fetchLogos() {
           try {
               // Refresh the suggestion data from API to get updated logos
               const response = await fetch(`/api/suggestions/${this.suggestionId}/domains`, {
                   headers: {
                       'Accept': 'application/json',
                       'X-Requested-With': 'XMLHttpRequest'
                   }
               });

               if (response.ok) {
                   const data = await response.json();

                   // Update logos with fresh data from server
                   if (data.logos && Array.isArray(data.logos) && data.logos.length > 0) {
                       // Force Alpine reactivity by creating a new array reference
                       this.logos = [...data.logos];

                       // Update logoGenerationId if provided
                       if (data.logoGenerationId) {
                           this.logoGenerationId = data.logoGenerationId;
                       }
                   }
               }
           } catch (error) {
               // Silently fail
           }
       },
       init() {
           // Load existing domains and logos on mount
           (async () => {
               try {
                   const response = await fetch(`/api/suggestions/${this.suggestionId}/domains`, {
                       headers: {
                           'Accept': 'application/json',
                           'X-Requested-With': 'XMLHttpRequest'
                       },
                       credentials: 'same-origin'
                   });

                   const text = await response.text();

                   try {
                       const data = JSON.parse(text);

                       // Load domains if they exist
                       if (data.domains && Object.keys(data.domains).length > 0) {
                           this.domains = {...data.domains};
                           this.domainsChecked = true;
                       }

                       // Load logos if they exist
                       if (data.logos && Array.isArray(data.logos) && data.logos.length > 0) {
                           this.logos = [...data.logos];
                       }

                       // Load logoGenerationId if it exists
                       if (data.logoGenerationId) {
                           this.logoGenerationId = data.logoGenerationId;
                       }
                   } catch (parseError) {
                       // Silently fail - card will work without initial data
                   }
               } catch (error) {
                   // Silently fail - card will work without initial data
               }
           })();
       }
    }"
    x-init="init()">

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
                <h3 class="text-lg font-semibold transition-all duration-300 text-gray-900 dark:text-white
                           {{ $this->isSelected ? 'text-primary-900 dark:text-primary-100' : '' }}">
                    {{ $suggestion->name }}
                </h3>


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
                    <!-- Domain Status - Computed entirely in Alpine.js -->
                    <div wire:ignore>
                        <template x-if="typeof domains !== 'undefined' && domains && Object.keys(domains).length > 0">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                                </svg>
                                <span x-text="availableCount + '/' + totalCount + ' available'"></span>
                            </span>
                        </template>
                    </div>

                    <!-- Logo Count - Polling via Alpine.js -->
                    <div wire:ignore>
                        <template x-if="hasLogos">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span x-text="(logos?.length || 0) + ' ' + ((logos?.length === 1) ? 'logo' : 'logos')"></span>
                            </span>
                        </template>
                    </div>
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

                    <!-- Expand Toggle -->
                    <div wire:ignore>
                        <flux:button
                            @click.stop="
                                if (typeof isExpanded !== 'undefined') {
                                    isExpanded = !isExpanded;
                                    // Always trigger domain check when expanding to ensure fresh DNS data
                                    if (isExpanded && typeof checkingDomains !== 'undefined' && !checkingDomains && typeof checkDomains === 'function') {
                                        checkDomains();
                                    }
                                }
                            "
                            variant="ghost"
                            size="sm"
                            class="group"
                        >
                            <svg class="w-4 h-4 transition-all duration-300 ease-out transform group-hover:scale-110"
                                 :class="typeof isExpanded !== 'undefined' && isExpanded ? 'rotate-180' : 'rotate-0'"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expandable Content -->
    <div class="overflow-hidden transition-all duration-500 ease-in-out border-t border-gray-200 dark:border-gray-700"
         x-show="typeof isExpanded !== 'undefined' && isExpanded"
         x-cloak
         @click.stop
         style="display: none;">
        <div class="p-4 space-y-4">
            <!-- Domains Section -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <h4 class="font-medium text-gray-900 dark:text-white">Domain Availability</h4>

                        <!-- Availability Summary -->
                        <div x-show="typeof domains !== 'undefined' && domains && Object.keys(domains).length > 0"
                             class="flex items-center gap-2 text-sm">
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-md font-medium">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span x-text="availableCount"></span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-md font-medium">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                                <span x-text="unavailableCount"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Loading indicator -->
                    <div x-show="typeof checkingDomains !== 'undefined' && checkingDomains" x-cloak class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <svg class="animate-spin w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Checking domains...
                    </div>
                </div>

                <!-- Legend -->
                <div x-show="typeof domains !== 'undefined' && domains && Object.keys(domains).length > 0"
                     class="mb-3 p-2 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Legend:</span>
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 bg-green-500 rounded"></span>
                            Available
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 bg-red-500 rounded"></span>
                            Taken
                        </span>
                        <span class="flex items-center gap-1 px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded border border-blue-200 dark:border-blue-800">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                            </svg>
                            Has DNS Records (In Use)
                        </span>
                    </div>
                </div>

                <div x-show="typeof domains !== 'undefined' && domains && Object.keys(domains).length > 0">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                        <template x-for="[domainKey, domainData] in Object.entries(typeof domains !== 'undefined' ? domains : {})" :key="'domain-' + (typeof suggestionId !== 'undefined' ? suggestionId : 0) + '-' + domainKey">
                            <div x-data="{
                                    domainKey: domainKey,
                                    domainName: domainKey,
                                    showTooltip: false,
                                    // Direct references to domainData for reactivity
                                    get available() { return domainData?.available ?? null; },
                                    get hasDNS() { return domainData?.has_dns_records ?? null; },
                                    get checkMethod() { return domainData?.check_method ?? null; },
                                    get status() { return domainData?.status ?? null; },
                                    get dnsRecords() { return domainData?.dns_records ?? null; },
                                    getTooltipText() {
                                        if (this.hasDNS === true) {
                                            return 'Domain has DNS records - Already registered and in use';
                                        } else if (this.hasDNS === false) {
                                            return 'No DNS records found - Potentially available';
                                        } else if (this.status === 'checking' || this.checkMethod === null) {
                                            return 'Checking DNS records...';
                                        } else if (this.status === 'error') {
                                            return 'Unable to verify - DNS check failed';
                                        } else {
                                            return this.available === true ? 'Domain appears available' : (this.available === false ? 'Domain is taken' : 'Status unknown');
                                        }
                                    },
                                    getBorderColorClass() {
                                        if (this.available === true) {
                                            return 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20 hover:border-green-300 hover:bg-green-100';
                                        } else if (this.available === false) {
                                            return 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20 hover:border-red-300 hover:bg-red-100';
                                        } else {
                                            return 'border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-800 hover:border-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700';
                                        }
                                    },
                                    getTextColorClass() {
                                        if (this.available === true) {
                                            return 'text-green-800 dark:text-green-200';
                                        } else if (this.available === false) {
                                            return 'text-red-800 dark:text-red-200 line-through opacity-70';
                                        } else {
                                            return 'text-gray-800 dark:text-gray-200';
                                        }
                                    }
                                }"
                                 :class="'group relative flex items-center justify-between p-2 rounded-lg border transition-all duration-300 ease-out hover:scale-105 hover:shadow-md transform ' + getBorderColorClass()"
                                 @mouseenter="showTooltip = true"
                                 @mouseleave="showTooltip = false">

                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span :class="'text-sm font-medium transition-colors duration-200 truncate ' + getTextColorClass()" x-text="domainName"></span>

                                    <!-- DNS Records Badge - Cleaner Design -->
                                    <template x-if="hasDNS === true && dnsRecords && Object.keys(dnsRecords).length > 0">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-1 text-xs font-medium bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-md border border-red-200 dark:border-red-800 whitespace-nowrap"
                                              :title="'Active DNS records found: ' + Object.keys(dnsRecords).filter(k => dnsRecords[k] && dnsRecords[k].length > 0).join(', ')">
                                            <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm2.5 3a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm6.207.293a1 1 0 00-1.414 0l-6 6a1 1 0 101.414 1.414l6-6a1 1 0 000-1.414zM12.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3z" clip-rule="evenodd" />
                                            </svg>
                                            <span x-text="Object.keys(dnsRecords).filter(k => dnsRecords[k] && dnsRecords[k].length > 0).length + ' DNS'"></span>
                                        </span>
                                    </template>

                                    <!-- DNS Check Badge - When no records found -->
                                    <template x-if="checkMethod === 'dns' && !(hasDNS === true && dnsRecords && Object.keys(dnsRecords).length > 0)">
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded border border-gray-200 dark:border-gray-700 whitespace-nowrap"
                                              title="DNS check performed - no records found">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </span>
                                    </template>
                                </div>

                                <!-- Status Icons -->
                                <div class="flex items-center space-x-1">
                                    <!-- Checking Spinner -->
                                    <template x-if="status === 'checking'">
                                        <svg class="animate-spin w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </template>

                                    <!-- Available Checkmark -->
                                    <template x-if="available === true">
                                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </template>

                                    <!-- Unavailable X -->
                                    <template x-if="available === false">
                                        <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </template>

                                    <!-- Unknown Question Mark -->
                                    <template x-if="available === null">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </template>
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
                                     x-text="getTooltipText()"
                                     style="display: none;">
                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                                        <div class="border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- DNS Records Detail Section -->
                <div x-show="typeof domains !== 'undefined' && domains && Object.values(domains || {}).some(d => d?.has_dns_records === true)"
                     class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/10 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h5 class="text-sm font-semibold text-blue-900 dark:text-blue-200">Active DNS Records Found</h5>
                    </div>
                    <p class="text-xs text-blue-800 dark:text-blue-300 mb-2">
                        The following domains have active DNS configurations, indicating they are currently registered and in use:
                    </p>
                    <div class="space-y-1">
                        <template x-for="[domainKey, domainData] in Object.entries(domains || {})" :key="'dns-detail-' + domainKey">
                            <div x-show="domainData?.has_dns_records === true"
                                 class="text-xs">
                                <span class="font-medium text-blue-900 dark:text-blue-100" x-text="domainKey"></span>
                                <span class="text-blue-700 dark:text-blue-400" x-show="domainData?.dns_records && Object.keys(domainData.dns_records).filter(k => domainData.dns_records[k] && domainData.dns_records[k].length > 0).length > 0">
                                    - <span x-text="'Found: ' + Object.keys(domainData.dns_records || {}).filter(k => domainData.dns_records[k] && domainData.dns_records[k].length > 0).join(', ')"></span>
                                </span>
                            </div>
                        </template>
                    </div>
                </div>

                <div x-show="typeof domains === 'undefined' || !domains || Object.keys(domains).length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                    </svg>
                    <p class="text-sm">No domains checked yet</p>
                </div>
            </div>

            <!-- Logos Section -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <h4 class="font-medium text-gray-900 dark:text-white">Logos</h4>
                        @if($this->hasGeneratedLogos)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                                {{ $this->totalGeneratedLogosCount }} generated
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2" wire:ignore>
                        <template x-if="hasLogos && logoGenerationId">
                            <a
                                :href="`/logo-gallery/${logoGenerationId}`"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 rounded-lg transition-colors shadow-sm hover:shadow-md"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                View in Gallery
                            </a>
                        </template>
                        <template x-if="!hasLogos">
                            <button
                                @click.prevent.stop="startLogoGeneration()"
                                type="button"
                                class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="generatingLogos"
                                x-text="generatingLogos ? 'Generating...' : 'Generate Logos'"
                            >
                            </button>
                        </template>
                    </div>
                </div>

                <template x-if="hasLogos">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        <template x-for="(logo, logoIndex) in logos" :key="`logo-{{ $suggestion->id }}-${logoIndex}`">
                            <div class="relative aspect-square rounded-lg border-2 border-gray-900 dark:border-gray-100 bg-white overflow-hidden hover:shadow-md transition-shadow">
                                <template x-if="logo.url">
                                    <img
                                        :src="logo.url"
                                        :alt="`Logo for {{ $suggestion->name }}`"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    />
                                </template>
                                <template x-if="logo.style">
                                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 text-center" x-text="logo.style ? logo.style.charAt(0).toUpperCase() + logo.style.slice(1) : ''"></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="!hasLogos">
                    <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm">No logos generated yet</p>
                    </div>
                </template>
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
                                @if($key !== 'deep_thinking')
                                    <div class="flex justify-between">
                                        <span class="capitalize">{{ str_replace('_', ' ', $key) }}:</span>
                                        <span>{{ is_array($value) ? json_encode($value) : $value }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </details>
                </div>
            @endif
        </div>
    </div>
</div>
@endif
