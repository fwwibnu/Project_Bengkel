@extends('layouts.error')

@section('code', '419')
@section('title', 'Sesi kamu sudah habis')
@section('message', 'Halaman terlalu lama dibiarkan terbuka. Muat ulang halaman, lalu coba lagi.')

@section('icon')
    <svg aria-hidden="true" class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
@endsection
