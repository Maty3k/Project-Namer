<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header Section --}}
    <div class="mb-6">
        <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">
            My Shares
        </flux:heading>
        <flux:subheading class="mt-1">
            Manage your shared logo designs and view analytics
        </flux:subheading>
    </div>

    {{-- Filters and Search Section --}}
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Search --}}
            <flux:field>
                <flux:label>Search</flux:label>
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Search shares..."
                />
            </flux:field>

            {{-- Status Filter --}}
            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model.live="filterStatus">
                    <option value="all">All Shares</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="expired">Expired</option>
                </flux:select>
            </flux:field>

            {{-- Sort Field --}}
            <flux:field>
                <flux:label>Sort By</flux:label>
                <flux:select wire:model.live="sortField">
                    <option value="created_at">Date Created</option>
                    <option value="view_count">View Count</option>
                    <option value="title">Title</option>
                </flux:select>
            </flux:field>

            {{-- Sort Direction --}}
            <flux:field>
                <flux:label>Direction</flux:label>
                <flux:select wire:model.live="sortDirection">
                    <option value="desc">Descending</option>
                    <option value="asc">Ascending</option>
                </flux:select>
            </flux:field>
        </div>

        {{-- Reset Filters Button --}}
        @if($search || $filterStatus !== 'all' || $sortField !== 'created_at' || $sortDirection !== 'desc')
            <div class="mt-4">
                <flux:button
                    wire:click="resetFilters"
                    variant="ghost"
                    size="sm"
                >
                    Reset Filters
                </flux:button>
            </div>
        @endif
    </div>

    {{-- Shares List --}}
    @if($shares->count() > 0)
        <div class="space-y-4">
            @foreach($shares as $share)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        {{-- Share Info --}}
                        <div class="flex-1">
                            <div class="flex items-start gap-3">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">
                                        {{ $share->title ?: 'Untitled Share' }}
                                    </h3>

                                    @if($share->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ Str::limit($share->description, 120) }}
                                        </p>
                                    @endif

                                    {{-- Badges and Meta --}}
                                    <div class="flex flex-wrap items-center gap-3 mt-3">
                                        {{-- Share Type Badge --}}
                                        <flux:badge
                                            :variant="$share->share_type === 'public' ? 'success' : 'warning'"
                                            size="sm"
                                        >
                                            @if($share->share_type === 'public')
                                                Public
                                            @else
                                                Password Protected
                                            @endif
                                        </flux:badge>

                                        {{-- Active Status Badge --}}
                                        <flux:badge
                                            :variant="$share->is_active ? 'success' : 'secondary'"
                                            size="sm"
                                        >
                                            {{ $share->is_active ? 'Active' : 'Inactive' }}
                                        </flux:badge>

                                        {{-- View Count --}}
                                        <span class="text-xs text-gray-600 dark:text-gray-400">
                                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            {{ $share->view_count }} views
                                        </span>

                                        {{-- Unique Visitors --}}
                                        @if($share->unique_visitors)
                                            <span class="text-xs text-gray-600 dark:text-gray-400">
                                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>
                                                {{ $share->unique_visitors }} unique
                                            </span>
                                        @endif

                                        {{-- Created Date --}}
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            Created {{ $share->created_at->diffForHumans() }}
                                        </span>

                                        {{-- Expiration Status --}}
                                        @if($share->expires_at)
                                            <span class="text-xs {{ $share->isExpired() ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                                                @if($share->isExpired())
                                                    Expired {{ $share->expires_at->diffForHumans() }}
                                                @else
                                                    Expires {{ $share->expires_at->diffForHumans() }}
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                Never expires
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-wrap items-center gap-2">
                            {{-- Copy Link --}}
                            <flux:button
                                wire:click="copyShareUrl({{ $share->id }})"
                                variant="ghost"
                                size="sm"
                                x-data
                                x-on:click="navigator.clipboard.writeText('{{ $share->getShareUrl() }}'); $dispatch('show-toast', { message: 'Link copied!', type: 'success' })"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Copy
                            </flux:button>

                            {{-- View Share --}}
                            <flux:button
                                href="{{ $share->getShareUrl() }}"
                                target="_blank"
                                variant="ghost"
                                size="sm"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                View
                            </flux:button>

                            {{-- Toggle Active Status --}}
                            <flux:button
                                wire:click="toggleShareStatus({{ $share->id }})"
                                variant="{{ $share->is_active ? 'ghost' : 'filled' }}"
                                size="sm"
                            >
                                @if($share->is_active)
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Pause
                                @else
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Activate
                                @endif
                            </flux:button>

                            {{-- Delete --}}
                            <flux:button
                                wire:click="deleteShare({{ $share->id }})"
                                wire:confirm="Are you sure you want to delete this share? This action cannot be undone."
                                variant="danger"
                                size="sm"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $shares->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                No shares found
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                @if($search || $filterStatus !== 'all')
                    No shares match your current filters.
                @else
                    You haven't created any shares yet. Start sharing your logo designs!
                @endif
            </p>
            @if($search || $filterStatus !== 'all')
                <flux:button
                    wire:click="resetFilters"
                    variant="outline"
                >
                    Clear Filters
                </flux:button>
            @endif
        </div>
    @endif
</div>
