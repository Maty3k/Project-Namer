<div class="space-y-8">
    <!-- Header -->
    <div class="space-y-2">
        <h1 class="text-4xl font-bold text-slate-900 dark:text-white">
            Create your account
        </h1>
        <p class="text-lg text-slate-600 dark:text-slate-400">
            Start generating amazing business names today
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status :status="session('status')" />

    <!-- Registration Form -->
    <form wire:submit="register" class="space-y-6">
        <!-- Name -->
        <div>
            <flux:input
                wire:model="name"
                label="Full name"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="John Doe"
            />
        </div>

        <!-- Email -->
        <div>
            <flux:input
                wire:model="email"
                label="Email address"
                type="email"
                required
                autocomplete="email"
                placeholder="you@example.com"
            />
        </div>

        <!-- Password -->
        <div>
            <flux:input
                wire:model="password"
                label="Password"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Create a strong password"
                viewable
            />
        </div>

        <!-- Confirm Password -->
        <div>
            <flux:input
                wire:model="password_confirmation"
                label="Confirm password"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Re-enter your password"
                viewable
            />
        </div>

        <!-- Terms -->
        <p class="text-sm text-slate-500 dark:text-slate-400">
            By creating an account, you agree to our Terms of Service and Privacy Policy
        </p>

        <!-- Submit Button -->
        <flux:button type="submit" variant="primary" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-base py-3">
            Create account
        </flux:button>
    </form>

    <!-- Divider -->
    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-slate-300 dark:border-slate-700"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-4 bg-white dark:bg-slate-950 text-slate-500 dark:text-slate-400">
                Already have an account?
            </span>
        </div>
    </div>

    <!-- Sign In Link -->
    <div class="text-center">
        <flux:link
            :href="route('login')"
            wire:navigate
            class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
        >
            Sign in instead
        </flux:link>
    </div>
</div>
