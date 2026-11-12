@extends('layouts.error')

@section('code', '403')
@section('title', 'Kamu tidak punya akses ke halaman ini')
@section('message', 'Halaman ini hanya untuk peran tertentu. Kalau menurutmu ini keliru, hubungi Admin bengkel.')

@section('icon')
    <svg aria-hidden="true" class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
@endsection
