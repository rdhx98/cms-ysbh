<x-layouts::auth :title="__('auth.title_page')">
  <div
    class="relative flex min-h-dvh w-full flex-col overflow-x-hidden md:h-dvh md:flex-row-reverse"
  >
    {{-- Pilihan bahasa (mengambang di kanan atas) --}}
    <div class="absolute top-6 right-6 z-50 md:top-8 md:right-8">
      <div
        role="group"
        aria-label="Language"
        class="border-forest/20 flex items-center rounded-full border bg-white/40 p-1 shadow-sm backdrop-blur-md"
      >
        <a
          href="{{ route('language.switch', 'id') }}"
          @if (app()->getLocale() === "id") aria-current="true" @endif
          class="rounded-full px-3 py-1 text-xs font-bold transition-all {{ app()->getLocale() === 'id' ? 'bg-forest text-white shadow-md' : 'text-forest/70 hover:bg-forest/10 hover:text-forest' }}"
        >
          ID
        </a>
        <a
          href="{{ route('language.switch', 'en') }}"
          @if (app()->getLocale() === "en") aria-current="true" @endif
          class="rounded-full px-3 py-1 text-xs font-bold transition-all {{ app()->getLocale() === 'en' ? 'bg-forest text-white shadow-md' : 'text-forest/70 hover:bg-forest/10 hover:text-forest' }}"
        >
          EN
        </a>
      </div>
    </div>

    {{-- Sisi form. Di ponsel halaman ikut menggulir (min-h-dvh); di layar lebar kolom ini yang menggulir bila perlu. --}}
    <div
      class="text-forest z-0 flex min-h-dvh w-full shrink-0 flex-col md:h-full md:min-h-0 md:w-1/2 md:overflow-y-auto"
    >
      <div
        class="bg-sage-soft flex w-full flex-1 flex-col items-center justify-center p-8"
      >
        <h1 class="font-montserrat">
          {{
            __(
              "auth.title",
            )
          }}
        </h1>

        @if (session("status"))
          <p role="status" class="text-forest mt-4 w-full max-w-sm rounded-lg bg-white/70 px-4 py-3 text-sm">
            {{
              session(
                "status",
              )
            }}
          </p>
        @endif

        {{-- novalidate: pesan galat datang dari server dalam bahasa aktif, bukan dari balon bawaan browser --}}
        <form
          method="POST"
          action="{{ route('login.store') }}"
          novalidate
          x-data="{ showPassword: false }"
          class="w-full max-w-sm"
        >
          @csrf

          {{-- Email --}}
          <div class="mt-6">
            <div class="relative">
              <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
                @if ($errors->has("email")) aria-invalid="true" aria-describedby="email-error" @endif
                class="peer w-full rounded-lg border-2 bg-transparent px-4 py-3 text-forest placeholder-transparent transition-all focus:border-terracotta focus:text-terracotta focus:outline-none {{ $errors->has('email') ? 'border-terracotta' : 'border-forest' }}"
              />

              <label
                for="email"
                class="bg-sage-soft text-forest peer-placeholder-shown:text-forest peer-focus:text-terracotta peer-[:not(:placeholder-shown)]:text-forest pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 rounded-4xl px-1 text-base transition-all peer-placeholder-shown:top-1/2 peer-placeholder-shown:text-base peer-focus:top-0 peer-focus:text-xs peer-[:not(:placeholder-shown)]:top-0 peer-[:not(:placeholder-shown)]:text-xs"
              >
                {{
                  __(
                    "auth.form.email",
                  )
                }}
              </label>
            </div>

            {{-- "Kredensial tidak cocok" dan "terlalu banyak percobaan" juga mendarat di kunci email --}}
            @if ($errors->has("email"))
              <p id="email-error" role="alert" class="text-coral mt-1.5 text-sm">{{
                $errors->first(
                  "email",
                )
              }}</p>
            @endif
          </div>

          {{-- Kata sandi --}}
          <div class="mt-6">
            <div class="relative">
              {{-- type="password" statis: bila Alpine belum jalan, sandi tidak pernah terbaca sebagai teks biasa --}}
              <input
                type="password"
                :type="showPassword ? 'text' : 'password'"
                id="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="************"
                @if ($errors->has("password")) aria-invalid="true" aria-describedby="password-error" @endif
                class="peer w-full rounded-lg border-2 bg-transparent py-3 pr-12 pl-4 text-forest placeholder-transparent transition-all focus:border-terracotta focus:text-terracotta focus:outline-none {{ $errors->has('password') ? 'border-terracotta' : 'border-forest' }}"
              />

              <label
                for="password"
                class="bg-sage-soft text-forest peer-placeholder-shown:text-forest peer-focus:text-terracotta peer-[:not(:placeholder-shown)]:text-forest pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 rounded-4xl px-1 text-base transition-all peer-placeholder-shown:top-1/2 peer-placeholder-shown:text-base peer-focus:top-0 peer-focus:text-xs peer-[:not(:placeholder-shown)]:top-0 peer-[:not(:placeholder-shown)]:text-xs"
              >
                {{
                  __(
                    "auth.form.password",
                  )
                }}
              </label>

              <button
                type="button"
                @click="showPassword = !showPassword"
                :aria-pressed="showPassword.toString()"
                aria-label="{{ app()->getLocale() === 'id' ? 'Tampilkan atau sembunyikan kata sandi' : 'Show or hide password' }}"
                class="text-forest/70 hover:text-forest focus:border-forest absolute top-1/2 right-4 z-20 -translate-y-1/2 focus:rounded-lg focus:border-2 focus:outline-none"
              >
                <svg
                  x-show="!showPassword"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="2"
                  stroke="currentColor"
                  class="h-5 w-5"
                  aria-hidden="true"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>

                <svg
                  x-show="showPassword"
                  x-cloak
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="2"
                  stroke="currentColor"
                  class="text-terracotta h-5 w-5"
                  aria-hidden="true"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                </svg>
              </button>
            </div>

            @if ($errors->has("password"))
              <p id="password-error" role="alert" class="text-coral mt-1.5 text-sm">{{
                $errors->first(
                  "password",
                )
              }}</p>
            @endif
          </div>

          {{-- Ingat saya --}}
          <div class="mt-4 flex items-center">
            <input
              type="checkbox"
              id="remember"
              name="remember"
              {{
                old("remember")
                  ? "checked"
                  : ""
              }}
              class="border-forest accent-forest focus:ring-forest h-4 w-4 rounded border-2"
            />
            <label
              for="remember"
              class="text-forest/80 ml-2 cursor-pointer text-sm select-none"
            >
              {{
                __(
                  "auth.button.persist",
                )
              }}
            </label>
          </div>

          <div class="mt-4 flex items-center justify-end">
            <button
              type="submit"
              data-test="login-button"
              class="bg-forest w-full cursor-pointer rounded-2xl p-4 text-white"
            >
              {{
                __(
                  "auth.button.login",
                )
              }}
            </button>
          </div>
        </form>
      </div>
    </div>

    {{-- Sisi gambar (dekoratif) --}}
    <div
      class="absolute inset-0 z-10 flex h-full w-full shrink-0 flex-col items-center justify-center p-8 text-gray-900 [clip-path:ellipse(100%_100%_at_50%_-75%)] md:relative md:w-1/2 md:[clip-path:none]"
    >
      <img
        class="absolute inset-0 h-full w-full object-cover opacity-90"
        src="{{ asset('images/bg-02d.png') }}"
        alt=""
        aria-hidden="true"
        fetchpriority="high"
      />
    </div>
  </div>
</x-layouts::auth>
