<aside 
    class="h-full bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 flex flex-col transition-all duration-300 ease-out
           {{ $isCollapsed ? 'w-0 opacity-0 overflow-hidden' : 'w-80' }}"
    x-data="{
        showActionMenu: null,
        focusMode: $wire.isCollapsed,
        handleKeydown(event) {
            if ((event.metaKey || event.ctrlKey) && event.key === '/') {
                event.preventDefault();
                $wire.toggleFocusMode();
            }
        }
    }"
    x-on:keydown.window="handleKeydown"
    x-on:focus-mode-toggled="focusMode = $event.detail.enabled"
>
    <!-- Header -->
    <header class="p-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Sessions</h2>
            <div class="flex items-center gap-2">
                <!-- Starred Filter Toggle -->
                <button
                    wire:click="toggleStarredFilter"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-150
                           {{ $showStarredOnly ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400' : 'text-gray-500 dark:text-gray-400' }}"
                    title="Show starred only"
                >
                    <flux:icon name="star" variant="solid" size="sm" />
                </button>
                
                <!-- Focus Mode Toggle -->
                <button
                    wire:click="toggleFocusMode"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-150 text-gray-500 dark:text-gray-400"
                    title="Toggle focus mode (Cmd+/)"
                >
                    <flux:icon name="eye-slash" size="sm" />
                </button>
            </div>
        </div>

        <!-- New Session Button -->
        <button
            wire:click="createNewSession"
            class="w-full bg-black dark:bg-white text-white dark:text-black rounded-lg px-4 py-3 
                   hover:bg-gray-800 dark:hover:bg-gray-100 transition-all duration-200 
                   flex items-center justify-center gap-2 font-medium shadow-sm hover:shadow-md
                   hover:scale-[1.02] active:scale-[0.98]"
        >
            <flux:icon name="plus" size="sm" class="transform group-hover:rotate-90 transition-transform duration-200" />
            New session
        </button>
    </header>

    <!-- Search Bar -->
    <div class="p-4 flex-shrink-0">
        <div class="relative">
            <flux:input
                wire:model.live.debounce.300ms="searchQuery"
                wire:keydown.escape="clearSearch"
                placeholder="Search sessions..."
                aria-label="Search sessions"
                role="searchbox"
                type="search"
                class="w-full pl-10 pr-4 py-2 text-sm"
            />
            <flux:icon 
                name="magnifying-glass" 
                size="sm" 
                class="absolute left-3 top-2.5 text-gray-400 search-icon"
            />
            
            @if($searchQuery)
                <button
                    wire:click="clearSearch"
                    class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 clear-search"
                    title="Clear search"
                >
                    <flux:icon name="x-mark" size="sm" />
                </button>
            @endif
        </div>
        
        <!-- Screen Reader Search Results Status -->
        <div 
            class="sr-only search-results-status" 
            aria-live="polite"
            wire:key="search-results-{{ $searchQuery }}"
        >
            @if($searchQuery)
                {{ count($sessions) }} result{{ count($sessions) === 1 ? '' : 's' }} found for "{{ $searchQuery }}"
            @endif
        </div>
    </div>

    <!-- Sessions List -->
    <div class="flex-1 overflow-y-auto px-2">
        @if(empty($groupedSessions))
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center h-64 text-center px-4">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                    <flux:icon name="chat-bubble-left" size="lg" class="text-gray-400" />
                </div>
                <p class="text-gray-500 dark:text-gray-400 mb-2">No sessions yet</p>
                <p class="text-sm text-gray-400 dark:text-gray-500">Create your first naming session to get started</p>
            </div>
        @else
            @foreach($groupedSessions as $dateGroup => $sessions)
                <!-- Date Group Header -->
                <div class="sticky top-0 bg-white dark:bg-gray-900 py-2 px-2 mb-2">
                    <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        {{ $dateGroup }}
                    </h3>
                </div>

                <!-- Sessions in Group -->
                @foreach($sessions as $session)
                    <div 
                        class="relative group mb-2"
                        x-data="{ showMenu: false }"
                        x-on:click.away="showMenu = false"
                    >
                        <div 
                            class="p-3 rounded-lg cursor-pointer transition-all duration-150 hover:bg-gray-50 dark:hover:bg-gray-800
                                   hover:scale-[1.02] active:scale-[0.98] border border-transparent hover:border-gray-200 dark:hover:border-gray-700
                                   {{ $session->is_starred ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800' : '' }}"
                            wire:click="loadSession('{{ $session->id }}')"
                        >
                            <!-- Session Header -->
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1 min-w-0 pr-2">
                                    @if($renamingSessionId === $session->id)
                                        <!-- Inline Rename Input -->
                                        <flux:input
                                            wire:model="renameText"
                                            wire:keydown.enter="saveRename('{{ $session->id }}', $wire.renameText)"
                                            wire:keydown.escape="cancelRename"
                                            class="text-sm font-medium w-full"
                                            autofocus
                                            x-on:click.stop
                                        />
                                    @else
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $session->title }}
                                        </h4>
                                    @endif
                                </div>

                                <!-- Star and Action Menu -->
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                    <!-- Star Button -->
                                    <button
                                        wire:click.stop="toggleStar('{{ $session->id }}')"
                                        class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                    >
                                        <flux:icon 
                                            name="star" 
                                            size="xs"
                                            :variant="$session->is_starred ? 'solid' : 'outline'"
                                            class="{{ $session->is_starred ? 'text-yellow-500' : 'text-gray-400 hover:text-gray-600' }}"
                                        />
                                    </button>

                                    <!-- Action Menu -->
                                    <div class="relative">
                                        <button
                                            x-on:click.stop="showMenu = !showMenu"
                                            class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                        >
                                            <flux:icon name="ellipsis-vertical" size="xs" class="text-gray-400" />
                                        </button>

                                        <!-- Dropdown Menu -->
                                        <div 
                                            x-show="showMenu"
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-100"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            class="absolute right-0 top-8 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-20"
                                        >
                                            <!-- Rename -->
                                            <button
                                                wire:click.stop="startRename('{{ $session->id }}')"
                                                x-on:click="showMenu = false"
                                                class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                                            >
                                                <flux:icon name="pencil" size="xs" />
                                                Rename
                                            </button>

                                            <!-- Duplicate -->
                                            <button
                                                wire:click.stop="duplicateSession('{{ $session->id }}')"
                                                x-on:click="showMenu = false"
                                                class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                                            >
                                                <flux:icon name="document-duplicate" size="xs" />
                                                Duplicate
                                            </button>

                                            <!-- Delete -->
                                            <button
                                                wire:click.stop="deleteSession('{{ $session->id }}')"
                                                x-on:click="showMenu = false"
                                                class="w-full text-left px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2"
                                            >
                                                <flux:icon name="trash" size="xs" />
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Session Preview -->
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mb-2">
                                {{ $session->getPreviewText() }}
                            </p>

                            <!-- Session Metadata -->
                            <div class="flex items-center justify-between text-xs text-gray-400 dark:text-gray-500">
                                <span>{{ $session->created_at->diffForHumans() }}</span>
                                <div class="flex items-center gap-1">
                                    @if($session->deep_thinking)
                                        <flux:icon name="light-bulb" size="xs" title="Deep thinking mode" />
                                    @endif
                                    <span class="capitalize">{{ $session->generation_mode }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach

            <!-- Load More Button -->
            @if(count($sessions) >= $limit)
                <div class="p-4 text-center">
                    <button
                        wire:click="loadMore"
                        class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-150"
                    >
                        Load more sessions...
                    </button>
                </div>
            @endif
        @endif
    </div>
</aside>

<!-- Floating Toggle Button (Focus Mode) -->
@if($isCollapsed)
    <button
        wire:click="toggleFocusMode"
        class="fixed top-4 left-4 z-50 bg-black dark:bg-white text-white dark:text-black p-3 rounded-lg shadow-lg 
               hover:bg-gray-800 dark:hover:bg-gray-100 transition-all duration-200 hover:scale-105"
        title="Show sidebar (Cmd+/)"
    >
        <flux:icon name="bars-3" size="sm" />
    </button>
@endif
