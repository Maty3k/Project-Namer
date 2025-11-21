<div>
    <flux:header class="mb-6
                        sm:mb-8">
        <flux:heading size="xl" class="text-xl
                                        sm:text-2xl
                                        !mb-2
                                        sm:!mb-3">
            Domain Checker
        </flux:heading>
        <flux:subheading class="text-xs
                                sm:text-sm
                                !mt-1">
            Check domain availability
        </flux:subheading>
    </flux:header>

    <div class="max-w-3xl mx-auto">
        <!-- Input Form -->
        <flux:card class="mb-4
                          sm:mb-6">
            <form wire:submit="checkDomain">
                <flux:field>
                    <flux:label class="text-sm">Name or Domain</flux:label>
                    <flux:input
                        wire:model.live="nameInput"
                        placeholder="e.g., MyBrand or mybrand.com"
                        class="w-full text-sm
                               sm:text-base"
                    />
                    <flux:description class="text-xs
                                             sm:text-sm">
                        Check .com, .io, .co, .net availability
                    </flux:description>
                    <flux:error name="nameInput" />
                </flux:field>

                <div class="mt-3 flex flex-col gap-2
                            sm:mt-4 sm:flex-row sm:gap-3">
                    <flux:button
                        type="submit"
                        variant="primary"
                        :disabled="!$nameInput || $isChecking"
                        class="w-full justify-center text-sm
                               sm:w-auto"
                    >
                        <flux:icon.magnifying-glass wire:loading.remove wire:target="checkDomain" class="w-4 h-4
                                                                                                              sm:w-5 sm:h-5" />
                        <flux:icon.arrow-path class="animate-spin w-4 h-4
                                                     sm:w-5 sm:h-5" wire:loading wire:target="checkDomain" />
                        <span wire:loading.remove wire:target="checkDomain">Check</span>
                        <span wire:loading wire:target="checkDomain">Checking...</span>
                    </flux:button>

                    @if(!empty($domainResults))
                        <flux:button
                            type="button"
                            variant="ghost"
                            wire:click="resetChecker"
                            class="w-full justify-center text-sm
                                   sm:w-auto"
                        >
                            <flux:icon.arrow-path class="w-4 h-4
                                                         sm:w-5 sm:h-5" />
                            <span class="hidden
                                         sm:inline">Check Another</span>
                            <span class="sm:hidden">New Check</span>
                        </flux:button>
                    @endif
                </div>
            </form>
        </flux:card>

        <!-- Error Message -->
        @if($errorMessage)
            <flux:card class="mb-4 border-l-4 border-red-500
                              sm:mb-6">
                <div class="flex items-start gap-2
                            sm:gap-3">
                    <flux:icon.exclamation-triangle class="w-5 h-5 text-red-500 shrink-0
                                                           sm:w-6 sm:h-6" />
                    <div>
                        <h3 class="text-sm font-semibold text-red-900 dark:text-red-100
                                   sm:text-base">
                            Error
                        </h3>
                        <p class="text-xs text-red-700 dark:text-red-300
                                  sm:text-sm">
                            {{ $errorMessage }}
                        </p>
                    </div>
                </div>
            </flux:card>
        @endif

        <!-- Results -->
        @if(!empty($domainResults))
            <flux:card>
                <div class="mb-3
                            sm:mb-4">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white
                               sm:text-lg">
                        "{{ $cleanName }}"
                    </h3>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5
                              sm:text-sm sm:mt-1">
                        Availability status
                    </p>
                </div>

                <div class="space-y-2
                            sm:space-y-3">
                    @foreach($domainResults as $domain => $result)
                        <div class="flex items-center justify-between p-2.5 rounded-lg border
                                    sm:p-3
                                    {{ $result['available'] === true ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950' : '' }}
                                    {{ $result['available'] === false ? 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950' : '' }}
                                    {{ $result['available'] === null ? 'border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900' : '' }}">
                            <div class="flex items-center gap-2 min-w-0
                                        sm:gap-3">
                                @if($result['available'] === true)
                                    <flux:icon.check-circle class="w-5 h-5 text-green-600 dark:text-green-400 shrink-0
                                                                   sm:w-6 sm:h-6" />
                                @elseif($result['available'] === false)
                                    <flux:icon.x-circle class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0
                                                               sm:w-6 sm:h-6" />
                                @else
                                    <flux:icon.question-mark-circle class="w-5 h-5 text-zinc-400 shrink-0
                                                                           sm:w-6 sm:h-6" />
                                @endif

                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white truncate
                                              sm:text-base">
                                        {{ $domain }}
                                    </p>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400
                                              sm:text-sm">
                                        @if($result['available'] === true)
                                            <span class="hidden
                                                         sm:inline">Available - </span>Can register
                                        @elseif($result['available'] === false)
                                            <span class="hidden
                                                         sm:inline">Taken - </span>Already registered
                                        @else
                                            Unable to verify
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($result['available'] === true)
                                <flux:badge color="green" class="text-xs shrink-0
                                                                 sm:text-sm">
                                    <span class="hidden
                                                 sm:inline">Available</span>
                                    <span class="sm:hidden">Free</span>
                                </flux:badge>
                            @elseif($result['available'] === false)
                                <flux:badge color="red" class="text-xs shrink-0
                                                               sm:text-sm">Taken</flux:badge>
                            @else
                                <flux:badge color="zinc" class="text-xs shrink-0
                                                                sm:text-sm">
                                    <span class="hidden
                                                 sm:inline">Unknown</span>
                                    <span class="sm:hidden">?</span>
                                </flux:badge>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Generate Logo Button (shown when at least one domain is available) -->
                @if($this->hasAvailableDomain)
                    <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700
                                sm:mt-6 sm:pt-6">
                        <div class="text-center">
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-3
                                      sm:text-sm sm:mb-4">
                                Domain available! Ready to create your brand?
                            </p>
                            <flux:button
                                wire:click="generateLogoForName"
                                variant="primary"
                                class="w-full justify-center text-sm
                                       sm:w-auto"
                            >
                                <flux:icon.sparkles class="w-4 h-4
                                                          sm:w-5 sm:h-5" />
                                <span>Generate Logo for "{{ $cleanName }}"</span>
                            </flux:button>
                        </div>
                    </div>
                @endif

                <!-- Info Message -->
                <div class="mt-4 p-2.5 rounded-lg bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800
                            sm:mt-6 sm:p-4">
                    <div class="flex gap-2
                                sm:gap-3">
                        <flux:icon.information-circle class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5
                                                             sm:w-5 sm:h-5" />
                        <p class="text-xs text-blue-900 dark:text-blue-100
                                  sm:text-sm">
                            <strong class="hidden
                                          sm:inline">Note: </strong>Results checked via DNS<span class="hidden
                                                                                                           sm:inline"> and cached for performance</span>. Verify with registrar<span class="hidden
                                                                                                                                                                                              sm:inline"> for accuracy</span>.
                        </p>
                    </div>
                </div>
            </flux:card>
        @endif

        <!-- Getting Started Guide (shown when no results) -->
        @if(empty($domainResults) && !$errorMessage)
            <flux:card class="mt-4
                              sm:mt-6">
                <div class="text-center py-6
                            sm:py-8">
                    <flux:icon.globe-alt class="w-12 h-12 mx-auto text-zinc-400 dark:text-zinc-600 mb-3
                                                sm:w-16 sm:h-16 sm:mb-4" />
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-1.5
                               sm:text-lg sm:mb-2">
                        Check Domain Availability
                    </h3>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 max-w-md mx-auto px-4
                              sm:text-sm">
                        Enter a name above to check .com, .io, .co, .net availability
                    </p>
                </div>
            </flux:card>
        @endif
    </div>
</div>
