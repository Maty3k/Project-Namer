<div class="space-y-8">
    <!-- Header -->
    <div class="space-y-2">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">
            Welcome back
        </h1>
        <p class="text-slate-600 dark:text-slate-400">
            Sign in to your account to continue
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
                        class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
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
        <flux:button type="submit" variant="primary" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white">
            Sign in
        </flux:button>
    </form>

    <!-- Divider -->
    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-slate-300 dark:border-slate-700"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-4 bg-white dark:bg-slate-950 text-slate-500 dark:text-slate-400">
                Don't have an account?
            </span>
        </div>
    </div>

    <!-- Sign Up Link -->
    @if (Route::has('register'))
        <div class="text-center">
            <flux:link
                :href="route('register')"
                wire:navigate
                class="font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
            >
                Create an account
            </flux:link>
        </div>
    @endif
</div>
