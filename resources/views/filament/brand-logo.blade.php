{{-- Logo panel: ikon perunggu + nama bengkel (sidebar, topbar HP, halaman login) --}}
<div class="bk-brand flex items-center gap-3">
    <span class="bk-brand-mark flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px]">
        <svg aria-hidden="true" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg>
    </span>
    <span class="flex flex-col leading-tight">
        <span class="bk-brand-name text-[15px] font-bold">{{ config('bengkel.nama') }}</span>
        <span class="bk-brand-sub text-xs font-medium">Sistem Bengkel</span>
    </span>
</div>
