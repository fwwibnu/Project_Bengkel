{{--
    Angka yang "berjalan" naik dari 0 saat halaman dibuka (Alpine, sudah ada di Filament).
    Pemakaian: <x-bk-count :value="1624000" prefix="Rp " />
    Tanpa JavaScript / dengan "kurangi gerakan", angka akhir langsung tampil.
--}}
@props(['value' => 0, 'prefix' => '', 'duration' => 1400])

<span
    x-data="{ v: {{ (int) $value }}, t: {{ (int) $value }} }"
    x-init="let s = null; const d = {{ (int) $duration }}; const step = (n) => { if (s === null) s = n; const p = Math.min(1, (n - s) / d); v = Math.round(t * (1 - Math.pow(1 - p, 4))); if (p < 1) requestAnimationFrame(step); }; if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches) { v = 0; requestAnimationFrame(step); }"
    x-text="'{{ $prefix }}' + v.toLocaleString('id-ID')"
    {{ $attributes }}
>{{ $prefix }}{{ number_format((int) $value, 0, ',', '.') }}</span>
