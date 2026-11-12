{{--
    Kerangka halaman error (403, 404, 419, 500) sesuai desain.
    Halaman 500 mengirim $withUser = false supaya tidak menyentuh database saat server bermasalah.
--}}
@php
    $user = ($withUser ?? true) ? auth()->user() : null;
    $homeUrl = $user ? \App\Support\RoleHome::url($user) : url('/app/login');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('bengkel.nama') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
</head>
<body class="flex min-h-screen flex-col bg-ivory font-sans text-stone-900 antialiased">

    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-stone-200 bg-white px-6 py-4 sm:px-8">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-bronze-600">
                <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/></svg>
            </div>
            <span class="text-[15px] font-bold">{{ config('bengkel.nama') }}</span>
        </div>

        @if ($user)
            <span class="text-sm text-stone-600">
                Masuk sebagai <strong class="text-stone-900">{{ $user->name }}</strong> ({{ $user->role?->getLabel() }})
            </span>
        @endif
    </header>

    <main class="flex flex-1 items-center justify-center px-6 py-12">
        <div class="flex max-w-lg flex-col items-center gap-4 text-center">
            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-bronze-50 text-bronze-700">
                @yield('icon')
            </div>

            <span class="text-sm font-bold tracking-[0.12em] text-bronze-700">ERROR @yield('code')</span>
            <h1 class="text-2xl font-bold leading-tight sm:text-3xl">@yield('title')</h1>
            <p class="text-base leading-relaxed text-stone-600">@yield('message')</p>

            <div class="flex flex-wrap justify-center gap-3 pt-2">
                @if ($user)
                    <a href="{{ $homeUrl }}"
                       class="inline-flex min-h-[46px] items-center rounded-full bg-bronze-700 px-5 font-bold text-white hover:bg-bronze-800">
                        Kembali ke halaman saya
                    </a>

                    {{-- Logout harus lewat POST + CSRF --}}
                    <form method="POST" action="{{ route('filament.app.auth.logout') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex min-h-[46px] items-center rounded-full border border-stone-300 bg-white px-5 font-semibold text-stone-900 hover:bg-stone-50">
                            Keluar
                        </button>
                    </form>
                @else
                    <a href="{{ $homeUrl }}"
                       class="inline-flex min-h-[46px] items-center rounded-full bg-bronze-700 px-5 font-bold text-white hover:bg-bronze-800">
                        Ke halaman masuk
                    </a>
                @endif
            </div>
        </div>
    </main>
</body>
</html>
