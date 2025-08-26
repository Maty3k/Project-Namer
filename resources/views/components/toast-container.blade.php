{{-- Toast Container Component for Logo Generation Notifications --}}
<div 
    x-data="toastContainer()"
    x-init="initToasts()"
    class="fixed top-4 right-4 z-50 space-y-2 max-w-sm"
    aria-live="polite"
    role="region"
    aria-label="Notifications"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div 
            x-show="toast.visible"
            x-transition:enter="transform ease-out duration-300 transition"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg shadow-lg ring-1 ring-black/5"
            :class="getToastClasses(toast.type)"
            role="alert"
            :aria-describedby="'toast-' + toast.id"
        >
            <div class="p-4">
                <div class="flex items-start">
                    {{-- Icon --}}
                    <div class="flex-shrink-0">
                        <template x-if="toast.type === 'success'">
                            <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </template>
                        <template x-if="toast.type === 'error'">
                            <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </template>
                        <template x-if="toast.type === 'warning'">
                            <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </template>
                        <template x-if="toast.type === 'info'">
                            <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        </template>
                    </div>

                    {{-- Content --}}
                    <div class="ml-3 w-0 flex-1">
                        <p class="text-sm font-medium" :class="getTextClasses(toast.type)" x-text="toast.title" x-show="toast.title"></p>
                        <p class="text-sm" :class="getTextClasses(toast.type)" x-text="toast.message" :id="'toast-' + toast.id"></p>
                        
                        {{-- Progress bar for timed toasts --}}
                        <div x-show="toast.progress !== undefined" class="mt-2">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full transition-all duration-300" 
                                     :class="getProgressClasses(toast.type)"
                                     :style="'width: ' + toast.progress + '%'"></div>
                            </div>
                        </div>

                        {{-- Action buttons --}}
                        <div x-show="toast.actions && toast.actions.length > 0" class="mt-3">
                            <div class="flex space-x-2">
                                <template x-for="action in toast.actions" :key="action.label">
                                    <button
                                        @click="handleAction(toast, action)"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                                        :class="getActionClasses(toast.type)"
                                        x-text="action.label"
                                    ></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Dismiss button --}}
                    <div class="ml-4 flex flex-shrink-0">
                        <button
                            @click="dismissToast(toast.id)"
                            class="inline-flex rounded-md p-1.5 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-gray-400"
                            :class="getDismissClasses(toast.type)"
                            aria-label="Dismiss notification"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function toastContainer() {
    return {
        toasts: [],
        nextId: 1,

        initToasts() {
            // Listen for toast events from Livewire
            window.addEventListener('toast', (event) => {
                this.addToast(event.detail);
            });

            // Listen for custom toast events
            window.addEventListener('show-toast', (event) => {
                this.addToast(event.detail);
            });
        },

        addToast(data) {
            const toast = {
                id: this.nextId++,
                type: data.type || 'info',
                title: data.title || null,
                message: data.message || 'Notification',
                duration: data.duration || this.getDefaultDuration(data.type),
                progress: data.progress,
                actions: data.actions || [],
                visible: false,
                ...data
            };

            this.toasts.push(toast);
            
            // Make visible after a short delay for transition
            setTimeout(() => {
                toast.visible = true;
            }, 100);

            // Auto-dismiss if duration is set
            if (toast.duration > 0) {
                setTimeout(() => {
                    this.dismissToast(toast.id);
                }, toast.duration);
            }

            // Limit to max 5 toasts
            if (this.toasts.length > 5) {
                this.toasts.splice(0, this.toasts.length - 5);
            }
        },

        dismissToast(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (toast) {
                toast.visible = false;
                setTimeout(() => {
                    const index = this.toasts.findIndex(t => t.id === id);
                    if (index > -1) {
                        this.toasts.splice(index, 1);
                    }
                }, 300);
            }
        },

        handleAction(toast, action) {
            // Emit custom event for action handling
            window.dispatchEvent(new CustomEvent('toast-action', {
                detail: {
                    toastId: toast.id,
                    action: action,
                    toast: toast
                }
            }));

            // Auto-dismiss toast after action unless specified otherwise
            if (action.keepOpen !== true) {
                this.dismissToast(toast.id);
            }
        },

        getDefaultDuration(type) {
            const durations = {
                success: 4000,
                info: 5000,
                warning: 8000,
                error: 10000
            };
            return durations[type] || 5000;
        },

        getToastClasses(type) {
            const classes = {
                success: 'bg-green-50 dark:bg-green-900/20',
                error: 'bg-red-50 dark:bg-red-900/20',
                warning: 'bg-amber-50 dark:bg-amber-900/20',
                info: 'bg-blue-50 dark:bg-blue-900/20'
            };
            return classes[type] || classes.info;
        },

        getTextClasses(type) {
            const classes = {
                success: 'text-green-800 dark:text-green-200',
                error: 'text-red-800 dark:text-red-200',
                warning: 'text-amber-800 dark:text-amber-200',
                info: 'text-blue-800 dark:text-blue-200'
            };
            return classes[type] || classes.info;
        },

        getProgressClasses(type) {
            const classes = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-amber-500',
                info: 'bg-blue-500'
            };
            return classes[type] || classes.info;
        },

        getActionClasses(type) {
            const classes = {
                success: 'bg-green-100 text-green-800 hover:bg-green-200 focus:ring-green-500 dark:bg-green-800 dark:text-green-100 dark:hover:bg-green-700',
                error: 'bg-red-100 text-red-800 hover:bg-red-200 focus:ring-red-500 dark:bg-red-800 dark:text-red-100 dark:hover:bg-red-700',
                warning: 'bg-amber-100 text-amber-800 hover:bg-amber-200 focus:ring-amber-500 dark:bg-amber-800 dark:text-amber-100 dark:hover:bg-amber-700',
                info: 'bg-blue-100 text-blue-800 hover:bg-blue-200 focus:ring-blue-500 dark:bg-blue-800 dark:text-blue-100 dark:hover:bg-blue-700'
            };
            return classes[type] || classes.info;
        },

        getDismissClasses(type) {
            const classes = {
                success: 'text-green-500 hover:text-green-600 focus:ring-green-500',
                error: 'text-red-500 hover:text-red-600 focus:ring-red-500',
                warning: 'text-amber-500 hover:text-amber-600 focus:ring-amber-500',
                info: 'text-blue-500 hover:text-blue-600 focus:ring-blue-500'
            };
            return classes[type] || classes.info;
        }
    };
}
</script>