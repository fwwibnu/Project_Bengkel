@extends('layouts.error', ['withUser' => false])

@section('code', '500')
@section('title', 'Terjadi kesalahan di sistem')
@section('message', 'Maaf, ada masalah saat memproses permintaanmu. Coba lagi beberapa saat lagi. Kalau terus terjadi, hubungi Admin bengkel.')

@section('icon')
    <svg aria-hidden="true" class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l9.5 17h-19z"/><path d="M12 10v4M12 17h.01"/></svg>
@endsection
