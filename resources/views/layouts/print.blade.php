{{-- Kerangka halaman siap cetak (nota & laporan). Tombol di .no-print tidak ikut tercetak. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('bengkel.nama') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    @stack('print-style')
</head>
<body class="min-h-screen bg-ivory font-sans text-stone-900 antialiased print:bg-white">
    <div class="no-print sticky top-0 z-10 border-b border-stone-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="@yield('back-url', url('/'))"
               class="inline-flex min-h-[44px] items-center gap-2 rounded-full px-3 text-sm font-semibold text-stone-700 hover:bg-stone-100">
                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                @yield('back-label', 'Kembali')
            </a>
            <button type="button" onclick="window.print()"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-full bg-bronze-700 px-5 text-sm font-bold text-white hover:bg-bronze-800">
                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="7"/></svg>
                Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <main class="px-4 py-6 print:p-0">
        @yield('content')
    </main>
</body>
</html>
