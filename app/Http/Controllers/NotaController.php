<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/** Nota pembayaran siap cetak (Blade + Tailwind). Kasir, Admin, Pemilik (lihat saja). */
class NotaController extends Controller
{
    public function show(Payment $payment): View
    {
        Gate::authorize('view', $payment);

        $payment->load(['workOrder.items.sparepart', 'workOrder.vehicle', 'workOrder.customer', 'workOrder.mechanic', 'cashier']);

        return view('print.nota', [
            'payment' => $payment,
            'workOrder' => $payment->workOrder,
            'bengkel' => config('bengkel'),
            'kembaliUrl' => url()->previous() !== url()->current() ? url()->previous() : url('/'),
        ]);
    }
}
