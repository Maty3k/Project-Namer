<div>
    {{-- Share Management Interface --}}
    
    {{-- Header Section --}}
    <div class="mb-6">
        <div class="flex flex-col gap-4
                    sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">
                    My Shares
                </flux:heading>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Manage your shared logo designs and view analytics
                </p>
            </div>
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="mb-6 grid grid-cols-1 gap-4
                sm:grid-cols-3">
        <flux:field>
            <flux:input
                wire:model.live="search"
                type="search"
                placeholder="Search shares..."
                class="w-full"
            />
        </flux:field>
        
        <flux:field>
            <flux:select wire:model.live="filterType" class="w-full">
                <option value="">All Types</option>
                <option value="public">Public</option>
                <option value="password_protected">Password Protected</option>
            </flux:select>
        </flux:field>
        
        <flux:field>
            <flux:select wire:model.live="filterActive" class="w-full">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </flux:select>
        </flux:field>
    </div>

    {{-- Shares List --}}
    @php
        $sharesData = $this->getShares();
    @endphp
    @if (count($sharesData['data']) > 0)
        <div class="space-y-4">
            @foreach ($sharesData['data'] as $share)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex flex-col gap-4
                                lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex-1">
                            <div class="flex items-start gap-3">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $share->title ?: 'Untitled Share' }}
                                    </h3>
                                    
                                    @if ($share->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ Str::limit($share->description, 120) }}
                                        </p>
                                    @endif
                                    
                                    <div class="flex items-center gap-4 mt-3">
                                        <flux:badge 
                                            :variant="$share->share_type === 'public' ? 'success' : 'warning'"
                                            size="sm"
                                        >
                                            {{ ucfirst(str_replace('_', ' ', $share->share_type)) }}
                                        </flux:badge>
                                        
                                        <flux:badge 
                                            :variant="$share->is_active ? 'success' : 'secondary'"
                                            size="sm"
                                        >
                                            {{ $share->is_active ? 'Active' : 'Inactive' }}
                                        </flux:badge>
                                        
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $share->view_count }} views
                                        </span>
                                        
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            Created {{ $share->created_at->diffForHumans() }}
                                        </span>
                                        
                                        @if ($share->expires_at)
                                            <span class="text-xs {{ $share->isExpired() ? 'text-red-500' : 'text-yellow-500' }}">
                                                {{ $share->isExpired() ? 'Expired' : 'Expires' }} {{ $share->expires_at->diffForHumans() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Actions --}}
                        <div class="flex items-center gap-2">
                            <flux:button
                                wire:click="copyShareUrl('{{ $share->uuid }}')"
                                variant="outline"
                                size="sm"
                            >
                                Copy Link
                            </flux:button>
                            
                            <flux:button
                                href="{{ $share->getShareUrl() }}"
                                target="_blank"
                                variant="outline"
                                size="sm"
                            >
                                View
                            </flux:button>
                            
                            @if ($share->is_active)
                                <flux:button
                                    wire:click="deactivateShare({{ $share->id }})"
                                    wire:confirm="Are you sure you want to deactivate this share?"
                                    variant="danger"
                                    size="sm"
                                >
                                    Deactivate
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        {{-- Pagination --}}
        @if ($sharesData['pagination']['last_page'] > 1)
            <div class="mt-6">
                {{-- Manual pagination since we're not using a paginator object --}}
                <div class="flex justify-center">
                    Page {{ $sharesData['pagination']['current_page'] }} of {{ $sharesData['pagination']['last_page'] }}
                </div>
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                <flux:icon name="share" class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                No shares found
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                @if ($search || $filterType || $filterActive !== '')
                    No shares match your current filters.
                @else
                    You haven't created any shares yet.
                @endif
            </p>
            @if ($search || $filterType || $filterActive !== '')
                <flux:button 
                    wire:click="$set('search', ''); $set('filterType', ''); $set('filterActive', '')"
                    variant="outline"
                >
                    Clear Filters
                </flux:button>
            @endif
        </div>
    @endif

    {{-- Create Share Modal --}}
    <flux:modal wire:model="showCreateModal" class="max-w-2xl">
        <div class="space-y-6">
            {{-- Header --}}
            <div>
                <flux:heading size="lg">Create New Share</flux:heading>
            </div>
            
            {{-- Form Content --}}
            <form wire:submit="createShare" class="space-y-6">
                {{-- Share Details --}}
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Title</flux:label>
                        <flux:input 
                            wire:model="title"
                            type="text"
                            placeholder="Enter a title for your share..."
                            class="w-full"
                        />
                        <flux:error name="title" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea 
                            wire:model="description"
                            placeholder="Add a description (optional)..."
                            rows="3"
                            class="w-full"
                        />
                        <flux:error name="description" />
                    </flux:field>
                </div>
                
                {{-- Share Type --}}
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Share Type</flux:label>
                        <div class="space-y-3">
                            <flux:radio
                                wire:model.live="shareType"
                                value="public"
                                label="Public - Anyone with the link can view"
                            />
                            <flux:radio
                                wire:model.live="shareType"
                                value="password_protected"
                                label="Password Protected - Requires password to view"
                            />
                        </div>
                        <flux:error name="shareType" />
                    </flux:field>
                    
                    @if ($shareType === 'password_protected')
                        <flux:field>
                            <flux:label>Password</flux:label>
                            <flux:input 
                                wire:model="password"
                                type="password"
                                placeholder="Enter password..."
                                class="w-full"
                            />
                            <flux:error name="password" />
                        </flux:field>
                    @endif
                </div>
                
                {{-- Advanced Options --}}
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Expiration (Optional)</flux:label>
                        <flux:input 
                            wire:model="expiresAt"
                            type="datetime-local"
                            class="w-full"
                        />
                        <flux:error name="expiresAt" />
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Leave blank for permanent share
                        </p>
                    </flux:field>
                    
                    <div class="space-y-3">
                        <flux:label>Display Options</flux:label>
                        <div class="space-y-2">
                            <flux:checkbox
                                wire:model="settings.show_title"
                                label="Show title in shared view"
                            />
                            <flux:checkbox
                                wire:model="settings.show_description"
                                label="Show description in shared view"
                            />
                            <flux:checkbox
                                wire:model="settings.allow_downloads"
                                label="Allow viewers to download logos"
                            />
                        </div>
                    </div>
                </div>
                
                @if ($errors->has('general'))
                    <flux:callout variant="danger">
                        {{ $errors->first('general') }}
                    </flux:callout>
                @endif
                
                @if ($errors->has('validation'))
                    <flux:callout variant="danger">
                        {{ $errors->first('validation') }}
                    </flux:callout>
                @endif
            </form>
            
            {{-- Footer --}}
            <div class="flex gap-2">
                <flux:spacer />
                <flux:button 
                    wire:click="closeCreateModal"
                    variant="ghost"
                >
                    Cancel
                </flux:button>
                <flux:button 
                    wire:click="createShare"
                    type="submit"
                    variant="primary"
                >
                    Create Share
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition 
             x-init="setTimeout(() => show = false, 5000)"
             class="fixed top-4 right-4 z-50">
            <flux:callout variant="success" class="max-w-sm">
                {{ session('success') }}
            </flux:callout>
        </div>
    @endif

    {{-- JavaScript for copy functionality --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('copy-to-clipboard', (event) => {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(event.shareUrl).then(() => {
                        // Show success feedback
                        const flash = document.createElement('div');
                        flash.className = 'fixed top-4 right-4 z-50 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded';
                        flash.innerHTML = 'Share URL copied to clipboard!';
                        document.body.appendChild(flash);
                        
                        setTimeout(() => {
                            flash.remove();
                        }, 3000);
                    }).catch(() => {
                        // Fallback - show the URL in an alert
                        alert('Share URL: ' + event.shareUrl);
                    });
                } else {
                    // Fallback for older browsers
                    alert('Share URL: ' + event.shareUrl);
                }
            });
        });
    </script>
</div>
