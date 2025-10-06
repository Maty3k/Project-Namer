{{-- Keyboard Shortcuts Help Overlay --}}
@props([])

<div x-data="keyboardShortcuts"
     x-show="helpOverlayOpen"
     x-cloak
     @keydown.escape.window="closeHelpOverlay()"
     class="fixed inset-0 z-50 overflow-y-auto"
     role="dialog"
     aria-modal="true"
     aria-labelledby="shortcuts-help-title">

    {{-- Backdrop --}}
    <div x-show="helpOverlayOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
         @click="closeHelpOverlay()"></div>

    {{-- Help Modal --}}
    <div class="flex min-h-full items-start justify-center p-4 text-center sm:p-0">
        <div x-show="helpOverlayOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl mt-20">

            {{-- Header --}}
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 id="shortcuts-help-title"
                        class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Keyboard Shortcuts
                    </h2>
                    <button @click="closeHelpOverlay()"
                            class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded">
                        <svg class="h-6 w-6"
                             xmlns="http://www.w3.org/2000/svg"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Shortcuts List --}}
            <div class="px-6 py-4">
                <div class="space-y-6">
                    {{-- General Section --}}
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
                            General
                        </h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Open command palette</span>
                                <kbd class="inline-flex items-center px-2 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded">
                                    <span class="mr-1">⌘</span>
                                    <span>K</span>
                                </kbd>
                            </div>
                            <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Show keyboard shortcuts</span>
                                <kbd class="inline-flex items-center px-2 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded">
                                    ?
                                </kbd>
                            </div>
                            <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Close modals</span>
                                <kbd class="inline-flex items-center px-2 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded">
                                    ESC
                                </kbd>
                            </div>
                        </div>
                    </div>

                    {{-- Actions Section --}}
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
                            Actions
                        </h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Create new project</span>
                                <kbd class="inline-flex items-center px-2 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded">
                                    <span class="mr-1">⌘</span>
                                    <span>N</span>
                                </kbd>
                            </div>
                            <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Generate names</span>
                                <kbd class="inline-flex items-center px-2 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded">
                                    <span class="mr-1">⌘</span>
                                    <span>G</span>
                                </kbd>
                            </div>
                        </div>
                    </div>

                    {{-- Windows/Linux Note --}}
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-medium">Note:</span> On Windows/Linux, use <kbd class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">Ctrl</kbd> instead of <kbd class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">⌘</kbd>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-900/50">
                <button @click="closeHelpOverlay()"
                        class="w-full inline-flex justify-center items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
