<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm">
            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-luna-ink focus:ring-luna-gold" name="remember">
            {{ __('Remember me') }}
        </label>

        <x-primary-button class="w-full py-3">{{ __('Log in') }}</x-primary-button>

        <p class="text-center text-sm">
            <a class="link" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
        </p>
    </form>
</x-guest-layout>
