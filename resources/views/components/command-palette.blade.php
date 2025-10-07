{{-- Command Palette Component --}}
@props([
    'open' => false,
])

<div x-data="keyboardShortcuts"
     x-show="commandPaletteOpen"
     x-cloak
     @keydown.escape.window="closeCommandPalette()"
     class="fixed inset-0 z-50 overflow-y-auto"
     role="dialog"
     aria-modal="true"
     aria-labelledby="command-palette-title">

    {{-- Backdrop --}}
    <div x-show="commandPaletteOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
         @click="closeCommandPalette()"></div>

    {{-- Command Palette Modal --}}
    <div class="flex min-h-full items-start justify-center p-4 text-center sm:p-0">
        <div x-show="commandPaletteOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg mt-20">

            {{-- Search Input --}}
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center px-4 py-3">
                    <svg class="h-5 w-5 text-gray-400 dark:text-gray-500"
                         xmlns="http://www.w3.org/2000/svg"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input x-ref="commandPaletteInput"
                           type="text"
                           placeholder="Type a command or search..."
                           class="ml-3 flex-1 border-0 bg-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-0 sm:text-sm"
                           autocomplete="off" />
                </div>
            </div>

            {{-- Commands List --}}
            <div class="max-h-96 overflow-y-auto p-2">
                <div class="space-y-1">
                    {{-- New Project Command --}}
                    <button @click="executeCommand('new-project')"
                            class="w-full flex items-center justify-between px-3 py-2 text-left text-sm rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:bg-gray-100 dark:focus:bg-gray-700 focus:outline-none transition-colors">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500 mr-3"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="text-gray-900 dark:text-gray-100">New Project</span>
                        </div>
                        <kbd class="hidden sm:inline-block px-2 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded">
                            ⌘N
                        </kbd>
                    </button>

                    {{-- Theme Customizer Command --}}
                    <button @click="executeCommand('theme-customizer')"
                            class="w-full flex items-center justify-between px-3 py-2 text-left text-sm rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:bg-gray-100 dark:focus:bg-gray-700 focus:outline-none transition-colors">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500 mr-3"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                            <span class="text-gray-900 dark:text-gray-100">Theme Customizer</span>
                        </div>
                        <kbd class="hidden sm:inline-block px-2 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded">
                            Ctrl+T
                        </kbd>
                    </button>

                    {{-- Logo Gallery Command --}}
                    <button @click="executeCommand('logo-gallery')"
                            class="w-full flex items-center justify-between px-3 py-2 text-left text-sm rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:bg-gray-100 dark:focus:bg-gray-700 focus:outline-none transition-colors">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500 mr-3"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-gray-900 dark:text-gray-100">Logo Gallery</span>
                        </div>
                        <kbd class="hidden sm:inline-block px-2 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded">
                            Ctrl+L
                        </kbd>
                    </button>

                    {{-- Dashboard Command --}}
                    <button @click="executeCommand('dashboard')"
                            class="w-full flex items-center justify-between px-3 py-2 text-left text-sm rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:bg-gray-100 dark:focus:bg-gray-700 focus:outline-none transition-colors">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500 mr-3"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="text-gray-900 dark:text-gray-100">Go to Dashboard</span>
                        </div>
                    </button>

                    {{-- Logos Command --}}
                    <button @click="executeCommand('logos')"
                            class="w-full flex items-center justify-between px-3 py-2 text-left text-sm rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:bg-gray-100 dark:focus:bg-gray-700 focus:outline-none transition-colors">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500 mr-3"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-gray-900 dark:text-gray-100">Go to Logos</span>
                        </div>
                    </button>
                </div>
            </div>

            {{-- Footer --}}
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-2 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center justify-between">
                    <span>Press <kbd class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">Ctrl+H</kbd> for keyboard shortcuts</span>
                    <span>Press <kbd class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">ESC</kbd> to close</span>
                </div>
            </div>
        </div>
    </div>
</div>
