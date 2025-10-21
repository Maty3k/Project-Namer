@if($suggestion)
<div wire:key="name-card-{{ $suggestion->id }}"
     class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm transition-all duration-300 ease-out hover:shadow-lg hover:shadow-gray-200/50 dark:hover:shadow-gray-800/50 transform hover:-translate-y-1
            {{ $this->isSelected ? 'ring-2 ring-blue-500 bg-primary-50 dark:bg-primary-900/10 shadow-lg shadow-blue-200/30 dark:shadow-blue-800/30' : 'hover:border-gray-300 dark:hover:border-gray-600' }}
            {{ $suggestion->is_hidden ? 'opacity-60 scale-95' : 'scale-100 hover:scale-[1.02]' }}
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
               console.log('⏳ Already checking domains, skipping duplicate request');
               return;
           }

           this.checkingDomains = true;
           console.log('🔍 Checking domains for suggestion:', this.suggestionId);

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

               console.log('📡 Check domains response status:', response.status);

               if (response.ok) {
                   const data = await response.json();
                   console.log('📦 Check domains data:', data);

                   if (data.domains && typeof data.domains === 'object') {
                       // Force Alpine reactivity by creating a new object reference
                       this.domains = {...data.domains};
                       this.domainsChecked = true;
                       console.log('✅ Domains checked successfully:', this.domains);
                       console.log('🔄 Domain keys:', Object.keys(this.domains));
                       console.log('🔄 Domain count:', Object.keys(this.domains).length);

                       // Count domains with DNS records
                       const withDNS = Object.values(this.domains).filter(d => d?.has_dns_records === true).length;
                       const available = Object.values(this.domains).filter(d => d?.available === true).length;
                       const unavailable = Object.values(this.domains).filter(d => d?.available === false).length;

                       console.log('🔵 Domains with DNS records:', withDNS);
                       console.log('✅ Available domains:', available);
                       console.log('❌ Unavailable domains:', unavailable);

                       // Force Alpine to update the UI
                       this.$nextTick(() => {
                           console.log('🎨 Alpine UI should update now');
                       });
                   } else {
                       console.warn('⚠️ Invalid domains data:', data);
                   }

                   this.checkingDomains = false;
               } else {
                   const errorText = await response.text();
                   console.error('❌ Failed to check domains:', response.status, errorText);
                   this.checkingDomains = false;
               }
           } catch (error) {
               console.error('❌ Error checking domains:', error);
               this.checkingDomains = false;
           }
       },
       async startLogoGeneration() {
           if (this.generatingLogos) return;
           this.generatingLogos = true;
           console.log('🎨 Starting logo generation for suggestion:', this.suggestionName);

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

               console.log('📡 Logo generation response status:', response.status);

               if (response.ok) {
                   const data = await response.json();
                   console.log('📦 Logo generation data:', data);

                   if (data.success && data.logo_generation_id) {
                       this.logoGenerationId = data.logo_generation_id;
                       console.log('✅ Logo generation started with ID:', this.logoGenerationId);

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
                       console.warn('⚠️ Invalid logo generation response:', data);
                       this.generatingLogos = false;
                   }
               } else {
                   const errorText = await response.text();
                   console.error('❌ Failed to start logo generation:', response.status, errorText);
                   this.generatingLogos = false;
                   if (window.Livewire) {
                       window.Livewire.dispatch('show-toast', {
                           message: 'Failed to start logo generation. Please try again.',
                           type: 'error'
                       });
                   }
               }
           } catch (error) {
               console.error('❌ Error starting logo generation:', error);
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
           console.log('⏰ Started polling for logo completion');
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
                       console.log('✅ Logo generation completed!');
                       clearInterval(this.pollInterval);
                       this.generatingLogos = false;

                       // Update logos array with the generated logos
                       if (data.logos && Array.isArray(data.logos)) {
                           this.logos = data.logos;
                           console.log('🎨 Loaded 4 logos:', this.logos);
                       }

                       // Show completion toast
                       if (window.Livewire) {
                           window.Livewire.dispatch('show-toast', {
                               message: '4 logos generated successfully!',
                               type: 'success'
                           });
                       }
                   } else if (data.status === 'failed') {
                       console.error('❌ Logo generation failed');
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
               console.error('❌ Error checking logo status:', error);
           }
       },
       async fetchLogos() {
           try {
               console.log('🔄 Fetching logos for suggestion:', this.suggestionId);
               // Refresh the suggestion data from API to get updated logos
               const response = await fetch(`/api/suggestions/${this.suggestionId}/domains`, {
                   headers: {
                       'Accept': 'application/json',
                       'X-Requested-With': 'XMLHttpRequest'
                   }
               });

               console.log('📡 Fetch logos response status:', response.status);

               if (response.ok) {
                   const data = await response.json();
                   console.log('📦 Fetched data:', data);

                   // Update logos with fresh data from server
                   if (data.logos && Array.isArray(data.logos) && data.logos.length > 0) {
                       // Force Alpine reactivity by creating a new array reference
                       this.logos = [...data.logos];
                       console.log('🎨 Logos array updated! Count:', this.logos.length);
                       console.log('🎨 Logo styles:', this.logos.map(l => l.style));
                       console.log('✅ hasLogos computed:', this.hasLogos);

                       // Update logoGenerationId if provided
                       if (data.logoGenerationId) {
                           this.logoGenerationId = data.logoGenerationId;
                           console.log('🆔 LogoGenerationId updated:', this.logoGenerationId);
                       }

                       // Force Alpine to detect the change
                       this.$nextTick(() => {
                           console.log('🔄 Next tick - Alpine should have updated UI');
                       });
                   } else {
                       console.log('⚠️ No logos in response or empty array');
                   }
               } else {
                   console.error('❌ Failed to fetch logos:', response.status);
               }
           } catch (error) {
               console.error('❌ Error fetching logos:', error);
           }
       },
       init() {
           // Load existing domains on mount without triggering Livewire
           (async () => {
               try {
                   const response = await fetch(`/api/suggestions/${this.suggestionId}/domains`, {
                       headers: {
                           'Accept': 'application/json',
                           'X-Requested-With': 'XMLHttpRequest'
                       },
                       credentials: 'same-origin'
                   });
                   console.log('📡 Domains API response status:', response.status);
                   console.log('📋 Response headers:', Object.fromEntries(response.headers.entries()));

                   const text = await response.text();
                   console.log('📄 Response text (first 200 chars):', text.substring(0, 200));

                   try {
                       const data = JSON.parse(text);
                       console.log('📦 Suggestion API data:', data);

                       // Load domains if they exist
                       if (data.domains && Object.keys(data.domains).length > 0) {
                           this.domains = {...data.domains};
                           this.domainsChecked = true;
                           console.log('✅ Loaded existing domains:', this.domains);
                       }

                       // Load logos if they exist
                       if (data.logos && Array.isArray(data.logos) && data.logos.length > 0) {
                           this.logos = [...data.logos];
                           console.log('🎨 Loaded existing logos:', this.logos);
                       }

                       // Load logoGenerationId if it exists
                       if (data.logoGenerationId) {
                           this.logoGenerationId = data.logoGenerationId;
                           console.log('🆔 Loaded logoGenerationId:', this.logoGenerationId);
                       }
                   } catch (parseError) {
                       console.log('❌ JSON parse error:', parseError);
                       console.log('📄 Full response text:', text);
                   }
               } catch (error) {
                   console.log('❌ Error loading domains:', error);
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
                    <div wire:ignore>
                        <flux:button
                            @click.stop="
                                if (typeof isExpanded !== 'undefined') {
                                    isExpanded = !isExpanded;
                                    // Always trigger domain check when expanding to ensure fresh DNS data
                                    if (isExpanded && typeof checkingDomains !== 'undefined' && !checkingDomains && typeof checkDomains === 'function') {
                                        console.log('🎯 Expanding card - checking domains now');
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

                <div x-show="typeof domains !== 'undefined' && domains && Object.keys(domains).length > 0"
                     x-effect="console.log('🎨 Domains display x-show evaluated:', typeof domains !== 'undefined' && domains && Object.keys(domains).length > 0, 'domain count:', Object.keys(domains || {}).length)">
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

                                <div class="flex items-center space-x-2 flex-1">
                                    <span :class="'text-sm font-medium transition-colors duration-200 ' + getTextColorClass()" x-text="domainName"></span>

                                    <!-- DNS Records Badge - More Prominent -->
                                    <template x-if="hasDNS === true && dnsRecords && Object.keys(dnsRecords).length > 0">
                                        <div class="flex items-center gap-1">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200 rounded-md border border-blue-300 dark:border-blue-700"
                                                  :title="'DNS Record Types: ' + Object.keys(dnsRecords).filter(k => dnsRecords[k] && dnsRecords[k].length > 0).join(', ')">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                                                </svg>
                                                <span x-text="'DNS: ' + Object.keys(dnsRecords).filter(k => dnsRecords[k] && dnsRecords[k].length > 0).length + ' records'"></span>
                                            </span>
                                            <span class="text-xs font-medium text-orange-600 dark:text-orange-400">
                                                🔴 ACTIVE
                                            </span>
                                        </div>
                                    </template>

                                    <!-- DNS Check Badge -->
                                    <template x-if="checkMethod === 'dns' && !(hasDNS === true && dnsRecords && Object.keys(dnsRecords).length > 0)">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-md border border-gray-200 dark:border-gray-700"
                                              title="DNS check performed">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            DNS
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
                    <h4 class="font-medium text-gray-900 dark:text-white">Logos</h4>
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
                            <div class="relative aspect-square rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
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
