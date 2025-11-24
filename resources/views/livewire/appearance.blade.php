<section class="w-full">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">
                {{ __('Appearance') }}
            </h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Choose your theme and mode preferences') }}
            </p>
        </div>

        {{-- Confirmation Message --}}
        @if($selectedThemeData)
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-500 dark:bg-green-800"
                 x-data="{ show: true }"
                 x-show="show"
                 x-transition>
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 text-green-600 dark:text-green-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-green-900 dark:text-white">
                            Theme successfully updated!
                        </p>

                        {{-- Color Swatches - Desktop Only --}}
                        <div class="mt-3 hidden md:block">
                            <p class="text-xs font-medium text-green-800 dark:text-green-100 mb-2">
                                Theme Colors:
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($selectedThemeData as $colorName => $colorValue)
                                    <div class="flex items-center gap-1.5 rounded-md bg-white dark:bg-zinc-700 px-2 py-1 border border-zinc-200 dark:border-zinc-500">
                                        <div class="w-4 h-4 rounded border border-zinc-300 dark:border-zinc-400"
                                             style="background-color: {{ $colorValue }};"></div>
                                        <span class="text-xs text-zinc-700 dark:text-white capitalize">
                                            {{ str_replace('-', ' ', $colorName) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <button @click="show = false"
                            class="flex-shrink-0 text-green-600 hover:text-green-800 dark:text-green-200 dark:hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- Custom Themes Section --}}
        @if($customThemes->isNotEmpty())
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ __('Your Custom Themes') }}
                    </h3>
                </div>

                <div class="grid grid-cols-2 gap-3
                            sm:grid-cols-3 sm:gap-4
                            md:grid-cols-4
                            lg:grid-cols-5
                            xl:grid-cols-6">
                    @foreach($customThemes as $customTheme)
                        <div class="group relative">
                            <button
                                wire:click="selectCustomTheme({{ $customTheme->id }})"
                                class="w-full flex flex-col items-center gap-3 rounded-xl border p-3 transition-all duration-200
                                       sm:gap-3.5 sm:p-4
                                       {{ $currentTheme === $customTheme->getThemeIdentifier()
                                          ? 'border-blue-500 bg-blue-50 shadow-md dark:border-blue-400 dark:bg-blue-950/50'
                                          : 'border-zinc-200 bg-white hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600' }}"
                            >
                                {{-- Current Theme Indicator --}}
                                @if($currentTheme === $customTheme->getThemeIdentifier())
                                    <div class="absolute right-2 top-2 text-blue-500 text-sm">
                                        ✓
                                    </div>
                                @endif

                                {{-- Color Preview --}}
                                <div class="flex items-center justify-center w-14 h-14 rounded-xl overflow-hidden transition-transform duration-200
                                            sm:w-16 sm:h-16
                                            ring-1 ring-zinc-200 dark:ring-zinc-700
                                            group-hover:scale-105"
                                     style="background: linear-gradient(135deg, {{ $customTheme->primary_color }} 0%, {{ $customTheme->accent_color }} 100%);">
                                </div>

                                {{-- Theme Name --}}
                                <div class="text-center w-full">
                                    <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 leading-tight mb-1 truncate
                                                sm:text-base sm:mb-1.5">
                                        {{ $customTheme->name }}
                                    </div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $customTheme->is_dark_mode ? 'Dark' : 'Light' }}
                                    </div>
                                </div>
                            </button>

                            {{-- Action Buttons --}}
                            <div class="absolute top-1 right-1 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                {{-- Edit Button --}}
                                <button
                                    wire:click.stop="openEditModal({{ $customTheme->id }})"
                                    class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-500 text-white hover:bg-blue-600 transition-colors shadow-sm"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </button>

                                {{-- Delete Button --}}
                                <button
                                    wire:click.stop="confirmDeleteTheme({{ $customTheme->id }})"
                                    class="flex items-center justify-center w-6 h-6 rounded-full bg-red-500 text-white hover:bg-red-600 transition-colors shadow-sm"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Theme Grid --}}
        <div class="space-y-6">
            <div>
                <h3 class="mb-4 font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('Available Themes') }}
                </h3>

                <div class="grid grid-cols-2 gap-3
                            sm:grid-cols-3 sm:gap-4
                            md:grid-cols-4
                            lg:grid-cols-5
                            xl:grid-cols-6">
                    {{-- Create Theme Card --}}
                    <button
                        wire:click="openCreateModal"
                        class="group relative flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed p-3 transition-all duration-200
                               sm:gap-3.5 sm:p-4
                               border-zinc-300 bg-zinc-50 hover:border-blue-400 hover:bg-blue-50
                               dark:border-zinc-600 dark:bg-zinc-800/50 dark:hover:border-blue-500 dark:hover:bg-blue-950/30"
                    >
                        <div class="flex items-center justify-center w-14 h-14 rounded-xl transition-all duration-200
                                    sm:w-16 sm:h-16
                                    bg-zinc-200 dark:bg-zinc-700
                                    group-hover:bg-blue-100 dark:group-hover:bg-blue-900/50
                                    group-hover:scale-105">
                            <svg class="w-6 h-6 text-zinc-400 dark:text-zinc-500 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <div class="text-center w-full">
                            <div class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 leading-tight mb-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors
                                        sm:text-base sm:mb-1.5">
                                Create Theme
                            </div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-500">
                                Custom colors
                            </div>
                        </div>
                    </button>

                    @foreach($themes as $theme)
                        <button
                            wire:click="selectTheme('{{ $theme['name'] }}')"
                            class="group relative flex flex-col items-center gap-3 rounded-xl border p-3 transition-all duration-200
                                   sm:gap-3.5 sm:p-4
                                   {{ $currentTheme === $theme['name']
                                      ? 'border-blue-500 bg-blue-50 shadow-md dark:border-blue-400 dark:bg-blue-950/50'
                                      : 'border-zinc-200 bg-white hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600' }}"
                        >
                            {{-- Current Theme Indicator --}}
                            @if($currentTheme === $theme['name'])
                                <div class="absolute right-2 top-2 text-blue-500 text-sm">
                                    ✓
                                </div>
                            @endif

                            {{-- Theme Preview Image --}}
                            <div class="flex items-center justify-center w-14 h-14 rounded-xl overflow-hidden transition-transform duration-200
                                        sm:w-16 sm:h-16
                                        {{ $theme['is_dark_mode']
                                           ? 'bg-slate-100 dark:bg-slate-800'
                                           : 'bg-white dark:bg-zinc-800' }}
                                        ring-1 ring-zinc-200 dark:ring-zinc-700
                                        group-hover:scale-105">
                                @php
                                    $imagePath = null;
                                    foreach (['svg', 'jpg', 'png'] as $ext) {
                                        if (file_exists(public_path('images/theme-previews/' . $theme['name'] . '.' . $ext))) {
                                            $imagePath = 'images/theme-previews/' . $theme['name'] . '.' . $ext;
                                            break;
                                        }
                                    }
                                @endphp
                                @if($imagePath)
                                    <img src="{{ asset($imagePath) }}"
                                         alt="{{ $theme['display_name'] }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <span class="text-3xl">{{ $themeEmojis[$theme['name']] ?? '🎨' }}</span>
                                @endif
                            </div>

                            {{-- Theme Name --}}
                            <div class="text-center w-full">
                                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 leading-tight mb-1
                                            sm:text-base sm:mb-1.5">
                                    {{ $theme['display_name'] }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $theme['is_dark_mode'] ? 'Dark' : 'Light' }}
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Create/Edit Custom Theme Modal --}}
    <flux:modal wire:model="showCreateModal" class="max-w-md">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                {{ $editingThemeId ? 'Edit Custom Theme' : 'Create Custom Theme' }}
            </h2>

            <form wire:submit="createCustomTheme" class="space-y-4">
                {{-- Theme Name --}}
                <flux:field>
                    <flux:label>Theme Name</flux:label>
                    <flux:input
                        wire:model="customThemeName"
                        placeholder="My Custom Theme"
                        maxlength="50"
                    />
                    <flux:error name="customThemeName" />
                </flux:field>

                {{-- Primary Color --}}
                <flux:field>
                    <flux:label>Primary Color</flux:label>
                    <div class="flex items-center gap-3">
                        <input
                            type="color"
                            wire:model.live="customPrimaryColor"
                            class="w-12 h-10 rounded cursor-pointer border border-zinc-300 dark:border-zinc-600"
                        />
                        <flux:input
                            wire:model.live="customPrimaryColor"
                            placeholder="#3b82f6"
                            class="flex-1 font-mono"
                        />
                    </div>
                    <flux:error name="customPrimaryColor" />
                </flux:field>

                {{-- Accent Color --}}
                <flux:field>
                    <flux:label>Accent Color</flux:label>
                    <div class="flex items-center gap-3">
                        <input
                            type="color"
                            wire:model.live="customAccentColor"
                            class="w-12 h-10 rounded cursor-pointer border border-zinc-300 dark:border-zinc-600"
                        />
                        <flux:input
                            wire:model.live="customAccentColor"
                            placeholder="#059669"
                            class="flex-1 font-mono"
                        />
                    </div>
                    <flux:error name="customAccentColor" />
                </flux:field>

                {{-- Color Preview --}}
                <div class="p-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Preview</p>
                    <div class="h-12 rounded-lg"
                         style="background: linear-gradient(135deg, {{ $customPrimaryColor }} 0%, {{ $customAccentColor }} 100%);">
                    </div>
                </div>

                {{-- Dark Mode Toggle --}}
                <div class="flex items-center justify-between">
                    <flux:label>Dark Mode</flux:label>
                    <flux:switch wire:model.live="customIsDarkMode" />
                </div>

                {{-- Actions --}}
                <div class="flex gap-3 pt-4">
                    <flux:button
                        type="button"
                        variant="ghost"
                        wire:click="closeCreateModal"
                        class="flex-1"
                    >
                        Cancel
                    </flux:button>
                    <flux:button
                        type="submit"
                        variant="primary"
                        class="flex-1"
                    >
                        {{ $editingThemeId ? 'Save Changes' : 'Create Theme' }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <div class="p-6 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>

            <h3 class="mb-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                Delete Theme
            </h3>

            <p class="mb-6 text-sm text-zinc-600 dark:text-zinc-400">
                Are you sure you want to delete "<span class="font-medium">{{ $deletingThemeName }}</span>"? This action cannot be undone.
            </p>

            <div class="flex gap-3">
                <flux:button
                    wire:click="cancelDelete"
                    variant="ghost"
                    class="flex-1"
                >
                    Cancel
                </flux:button>
                <flux:button
                    wire:click="deleteCustomTheme"
                    variant="danger"
                    class="flex-1"
                >
                    Delete
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
