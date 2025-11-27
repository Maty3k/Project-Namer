<div class="space-y-8">
    <!-- Header -->
    <div class="space-y-2">
        <h1 class="text-4xl font-bold text-slate-900">
            Forgot password
        </h1>
        <p class="text-lg text-slate-600">
            Enter your email to receive a password reset link
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status :status="session('status')" />

    <!-- Forgot Password Form -->
    <form wire:submit="sendPasswordResetLink" class="space-y-6">
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

        <!-- Submit Button -->
        <flux:button type="submit" variant="primary" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-base py-3">
            Email password reset link
        </flux:button>
    </form>

    <!-- Divider -->
    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-slate-300"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-4 bg-white text-slate-500">
                Remember your password?
            </span>
        </div>
    </div>

    <!-- Back to Login Link -->
    <div class="text-center">
        <flux:link
            :href="route('login')"
            wire:navigate
            class="font-semibold text-indigo-600 hover:text-indigo-700"
        >
            Back to login
        </flux:link>
    </div>
</div>
