<x-layouts::auth :title="__('Log in')" >
    {{-- CETAKAN SVG LUBANG KUSTOM (objectBoundingBox) --}}

    <div class="w-full h-screen flex flex-col md:flex-row-reverse overflow-hidden relative">

        {{-- Left side content --}}
        <div class="w-full h-screen md:w-1/2 shrink-0 text-forest z-0 flex flex-col justify-center items-center md:p-0 ">
            {{-- form wrapper --}}
            {{-- [clip-path:ellipse(100%_100%_at_50%_115%)] --}}
            <div
            class="bg-sage-soft w-full h-full md:max-w-none md:h-full flex flex-col justify-center items-center p-8">
            <div class="font-montserrat">
                Yayasan Sinar Bhakti Husada
            </div>
                <form method="POST" action="{{ route('login.store') }}" class=" ">
                    @csrf

                    <!-- Email Address -->
                    <div class="relative mt-6">
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="email@example.com"
                            class="peer w-full px-4 py-3 border-2 border-forest rounded-lg bg-transparent text-forest placeholder-transparent focus:outline-none focus:border-forest transition-all"
                        />

                        <label
                            for="email"
                            class="absolute left-4 top-1/2 -translate-y-1/2 bg-sage-soft rounded-4xl bg px-1 text-forest text-base pointer-events-none transition-all
                            peer-placeholder-shown:top-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-forest
                            peer-focus:top-0 peer-focus:text-xs peer-focus:text-forest
                            peer-[:not(:placeholder-shown)]:top-0 peer-[:not(:placeholder-shown)]:text-xs peer-[:not(:placeholder-shown)]:text-forest"
                        >
                            {{ __("e-mail address") }}
                        </label>
                    </div>

                    <div class="relative mt-6" x-data="{ showPassword: false }">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            name="password"
                            value="{{ old('password') }}"
                            required
                            autofocus
                            autocomplete="current-password"
                            placeholder="************"
                            class="peer w-full pl-4 pr-12 py-3 border-2 border-forest rounded-lg bg-transparent text-forest placeholder-transparent focus:outline-none focus:border-forest transition-all"
                        />

                        <label
                            for="password"
                            class="absolute left-4 top-1/2 -translate-y-1/2 bg-sage-soft px-1 text-forest text-base pointer-events-none transition-all
                            peer-placeholder-shown:top-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-forest
                            peer-focus:top-0 peer-focus:text-xs peer-focus:text-forest
                            peer-[:not(:placeholder-shown)]:top-0 peer-[:not(:placeholder-shown)]:text-xs peer-[:not(:placeholder-shown)]:text-forest"
                        >
                            {{ __("password") }}
                        </label>

                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-forest/70 hover:text-forest focus:outline-none z-20"
                        >
                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>

                            <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center mt-4">
                        <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }} class="rounded-lg w-4 h-4 accent-forest border-2 border-forest rounded focus:ring-forest">
                        <label for="remember" class="ml-2 text-sm text-forest/80 cursor-pointer select-none">
                                {{ __('Remember me') }}
                        </label>
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <button variant="primary" type="submit" class="bg-forest p-4 rounded-2xl w-full text-white" data-test="login-button">
                            {{ __('Log in') }}
                        </button>
                    </div>
                </form>
            </div>


            {{-- <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                <span>{{ __("Don't have an account?") }}</span>
                <a :href="route('register')" wire:navigate>{{ __('Sign up') }}</a>
            </div> --}}
        </div>

        {{-- Right side content --}}
        <div class="absolute inset-0 w-full h-full md:relative md:w-1/2 shrink-0 z-10 text-gray-900 flex flex-col justify-center items-center p-8 [clip-path:ellipse(100%_100%_at_50%_-75%)] md:[clip-path:none]">

            <img class="absolute inset-0 h-full w-full object-cover opacity-90 transition-opacity duration-300"
                src="{{ asset('images/bg-02d.png') }}"
                alt="Login Background">
        </div>

    </div>


</x-layouts::auth>
