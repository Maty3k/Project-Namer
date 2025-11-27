<div class="space-y-8">
    <!-- Header -->
    <div class="space-y-2">
        <h1 class="text-4xl font-bold text-slate-900">
            Welcome back
        </h1>
        <p class="text-lg text-slate-600">
            Sign in to continue creating amazing names
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status :status="session('status')" />

    <!-- Login Form -->
    <form wire:submit="login" class="space-y-6">
        <!-- Email -->
        <div>
            <flux:input
                wire:model="email"
                label="Email address"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="you@example.com"
            />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <flux:label>Password</flux:label>
                @if (Route::has('password.request'))
                    <flux:link
                        :href="route('password.request')"
                        wire:navigate
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        Forgot password?
                    </flux:link>
                @endif
            </div>
            <flux:input
                wire:model="password"
                type="password"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
                viewable
            />
        </div>

        <!-- Remember Me -->
        <div>
            <flux:checkbox wire:model="remember" label="Remember me for 30 days" />
        </div>

        <!-- Submit Button -->
        <flux:button type="submit" variant="primary" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-base py-3">
            Sign in
        </flux:button>
    </form>

    <!-- Divider -->
    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-slate-300 dark:border-slate-700"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-4 bg-white text-slate-500">
                New to {{ config('app.name') }}?
            </span>
        </div>
    </div>

    <!-- Sign Up Link -->
    @if (Route::has('register'))
        <div class="text-center">
            <flux:link
                :href="route('register')"
                wire:navigate
                class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                Create an account
            </flux:link>
        </div>
    @endif
</div>
