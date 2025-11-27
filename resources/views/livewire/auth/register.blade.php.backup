<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-3 text-center">
        <flux:heading size="xl" class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent dark:from-indigo-400 dark:to-purple-400">
            {{ __('Create your account') }}
        </flux:heading>
        <flux:subheading class="text-slate-600 dark:text-slate-400">
            {{ __('Start generating amazing business names today') }}
        </flux:subheading>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-5">
        <!-- Name -->
        <div class="space-y-2">
            <flux:input
                wire:model="name"
                :label="__('Full name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('John Doe')"
                class="transition-all duration-200 focus:ring-2 focus:ring-indigo-500"
            />
        </div>

        <!-- Email Address -->
        <div class="space-y-2">
            <flux:input
                wire:model="email"
                :label="__('Email address')"
                type="email"
                required
                autocomplete="email"
                placeholder="you@example.com"
                class="transition-all duration-200 focus:ring-2 focus:ring-indigo-500"
            />
        </div>

        <!-- Password -->
        <div class="space-y-2">
            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Create a strong password')"
                viewable
                class="transition-all duration-200 focus:ring-2 focus:ring-indigo-500"
            />
        </div>

        <!-- Confirm Password -->
        <div class="space-y-2">
            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Re-enter your password')"
                viewable
                class="transition-all duration-200 focus:ring-2 focus:ring-indigo-500"
            />
        </div>

        <div class="flex flex-col gap-4 pt-2">
            <flux:button type="submit" variant="primary" class="w-full h-12 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl">
                <div class="flex items-center justify-center gap-2 text-base font-semibold">
                    <x-app-icon name="add" size="sm" />
                    {{ __('Create account') }}
                </div>
            </flux:button>
        </div>

        <p class="text-xs text-center text-slate-500 dark:text-slate-400 leading-relaxed">
            By creating an account, you agree to our Terms of Service and Privacy Policy
        </p>
    </form>

    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-slate-200 dark:border-slate-700"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="bg-white px-4 text-slate-500 dark:bg-slate-900 dark:text-slate-400">or</span>
        </div>
    </div>

    <div class="text-center">
        <span class="text-sm text-slate-600 dark:text-slate-400">{{ __('Already have an account?') }}</span>
        <flux:link :href="route('login')" wire:navigate class="ml-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
            {{ __('Sign in instead') }}
        </flux:link>
    </div>
</div>
