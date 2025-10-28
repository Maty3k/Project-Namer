<div>
    <flux:header class="mb-8">
        <flux:heading size="xl" class="mb-6">Domain Checker</flux:heading>
        <flux:subheading>Quickly check domain availability for your business name ideas</flux:subheading>
    </flux:header>

    <div class="max-w-3xl mx-auto">
        <!-- Input Form -->
        <flux:card class="mb-6">
            <form wire:submit="checkDomain">
                <flux:field>
                    <flux:label>Business Name or Domain</flux:label>
                    <flux:input
                        wire:model.live="nameInput"
                        placeholder="e.g., MyAwesomeBrand or myawesomebrand.com"
                        class="w-full"
                    />
                    <flux:description>
                        Enter a business name or domain. We'll check availability for .com, .io, .co, and .net
                    </flux:description>
                    <flux:error name="nameInput" />
                </flux:field>

                <div class="mt-4 flex gap-3">
                    <flux:button
                        type="submit"
                        variant="primary"
                        :disabled="!$nameInput || $isChecking"
                    >
                        <flux:icon.magnifying-glass wire:loading.remove wire:target="checkDomain" />
                        <flux:icon.arrow-path class="animate-spin" wire:loading wire:target="checkDomain" />
                        <span wire:loading.remove wire:target="checkDomain">Check Availability</span>
                        <span wire:loading wire:target="checkDomain">Checking...</span>
                    </flux:button>

                    @if(!empty($domainResults))
                        <flux:button
                            type="button"
                            variant="ghost"
                            wire:click="resetChecker"
                        >
                            <flux:icon.arrow-path />
                            Check Another
                        </flux:button>
                    @endif
                </div>
            </form>
        </flux:card>

        <!-- Error Message -->
        @if($errorMessage)
            <flux:card class="mb-6 border-l-4 border-red-500">
                <div class="flex items-start gap-3">
                    <flux:icon.exclamation-triangle class="size-6 text-red-500 shrink-0" />
                    <div>
                        <h3 class="font-semibold text-red-900 dark:text-red-100">Error</h3>
                        <p class="text-sm text-red-700 dark:text-red-300">{{ $errorMessage }}</p>
                    </div>
                </div>
            </flux:card>
        @endif

        <!-- Results -->
        @if(!empty($domainResults))
            <flux:card>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        Results for "{{ $cleanName }}"
                    </h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                        Domain availability status for popular TLDs
                    </p>
                </div>

                <div class="space-y-3">
                    @foreach($domainResults as $domain => $result)
                        <div class="flex items-center justify-between p-4 rounded-lg border
                                    {{ $result['available'] === true ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950' : '' }}
                                    {{ $result['available'] === false ? 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950' : '' }}
                                    {{ $result['available'] === null ? 'border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900' : '' }}">
                            <div class="flex items-center gap-3">
                                @if($result['available'] === true)
                                    <flux:icon.check-circle class="size-6 text-green-600 dark:text-green-400" />
                                @elseif($result['available'] === false)
                                    <flux:icon.x-circle class="size-6 text-red-600 dark:text-red-400" />
                                @else
                                    <flux:icon.question-mark-circle class="size-6 text-zinc-400" />
                                @endif

                                <div>
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $domain }}</p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                        @if($result['available'] === true)
                                            Available - This domain can be registered
                                        @elseif($result['available'] === false)
                                            Taken - This domain is already registered
                                        @else
                                            Unknown - Unable to verify availability
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($result['available'] === true)
                                <flux:badge color="green" size="sm">Available</flux:badge>
                            @elseif($result['available'] === false)
                                <flux:badge color="red" size="sm">Taken</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">Unknown</flux:badge>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Info Message -->
                <div class="mt-6 p-4 rounded-lg bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800">
                    <div class="flex gap-3">
                        <flux:icon.information-circle class="size-5 text-blue-600 dark:text-blue-400 shrink-0" />
                        <p class="text-sm text-blue-900 dark:text-blue-100">
                            <strong>Note:</strong> Domain availability is checked in real-time using DNS lookups.
                            Results are cached for better performance. For the most accurate information,
                            verify with your preferred domain registrar.
                        </p>
                    </div>
                </div>
            </flux:card>
        @endif

        <!-- Getting Started Guide (shown when no results) -->
        @if(empty($domainResults) && !$errorMessage)
            <flux:card class="mt-6">
                <div class="text-center py-8">
                    <flux:icon.globe-alt class="size-16 mx-auto text-zinc-400 dark:text-zinc-600 mb-4" />
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">
                        Check Domain Availability
                    </h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 max-w-md mx-auto">
                        Enter a business name or domain above to instantly check if it's available
                        across the most popular domain extensions (.com, .io, .co, .net).
                    </p>
                </div>
            </flux:card>
        @endif
    </div>
</div>
