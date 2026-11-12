@extends('layouts.error')

@section('code', '404')
@section('title', 'Halaman tidak ditemukan')
@section('message', 'Alamat yang kamu buka tidak ada atau sudah dipindah.')

@section('icon')
    <svg aria-hidden="true" class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
@endsection
