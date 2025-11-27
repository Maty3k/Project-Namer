<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-3 text-center">
        <flux:heading size="xl" class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent dark:from-indigo-400 dark:to-purple-400">
            {{ __('Welcome back') }}
        </flux:heading>
        <flux:subheading class="text-slate-600 dark:text-slate-400">
            {{ __('Enter your credentials to access your account') }}
        </flux:subheading>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-5">
        <!-- Email Address -->
        <div class="space-y-2">
            <flux:input
                wire:model="email"
                :label="__('Email address')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="you@example.com"
                class="transition-all duration-200 focus:ring-2 focus:ring-indigo-500"
            />
        </div>

        <!-- Password -->
        <div class="space-y-2">
            <div class="relative">
                <flux:input
                    wire:model="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Enter your password')"
                    viewable
                    class="transition-all duration-200 focus:ring-2 focus:ring-indigo-500"
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute end-0 top-0 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot?') }}
                    </flux:link>
                @endif
            </div>
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <flux:checkbox wire:model="remember" :label="__('Remember me for 30 days')" class="text-sm" />
        </div>

        <div class="flex flex-col gap-4 pt-2">
            <flux:button variant="primary" type="submit" class="w-full h-12 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl">
                <div class="flex items-center justify-center gap-2 text-base font-semibold">
                    {{ __('Sign in') }}
                    <x-app-icon name="arrow-right" size="sm" />
                </div>
            </flux:button>
        </div>
    </form>

    @if (Route::has('register'))
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-slate-200 dark:border-slate-700"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="bg-white px-4 text-slate-500 dark:bg-slate-900 dark:text-slate-400">or</span>
            </div>
        </div>

        <div class="text-center">
            <span class="text-sm text-slate-600 dark:text-slate-400">{{ __("Don't have an account?") }}</span>
            <flux:link :href="route('register')" wire:navigate class="ml-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                {{ __('Create one now') }}
            </flux:link>
        </div>
    @endif
</div>
