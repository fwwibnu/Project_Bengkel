{{-- Nama dan peran pengguna di topbar panel (di HP disembunyikan supaya tidak sempit) --}}
@php($user = auth()->user())

@if ($user)
    <div class="hidden sm:flex flex-col items-end gap-0.5 me-2 leading-tight">
        <span class="text-sm font-semibold text-gray-950">
            {{ $user->name }}
        </span>

        <x-filament::badge size="sm" :color="$user->role->getColor()">
            {{ $user->role->getLabel() }}
        </x-filament::badge>
    </div>
@endif
