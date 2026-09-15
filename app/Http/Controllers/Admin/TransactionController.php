<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Transaction::with(['user', 'plan']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('va_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('payment_status', $status);
        }

        if ($gateway = $request->input('gateway')) {
            $query->where('payment_gateway', $gateway);
        }

        $transactions = $query->latest()->paginate(15)->withQueryString();

        return view('admin.transactions.index', compact('transactions'));
    }

    public function approve(Transaction $transaction): RedirectResponse
    {
        if ($transaction->payment_status === 'paid') {
            return back()->with('info', 'Transaksi ini sudah berstatus PAID.');
        }

        $transaction->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        // Automatically activate direct user subscription
        if ($transaction->user && $transaction->plan) {
            $transaction->user->grantSubscription($transaction->plan);
        }

        return back()->with('success', "Transaksi #{$transaction->order_id} berhasil disetujui secara manual dan langganan PRO pengguna langsung aktif.");
    }

    public function cancel(Transaction $transaction): RedirectResponse
    {
        $transaction->update([
            'payment_status' => 'failed',
        ]);

        return back()->with('success', "Transaksi #{$transaction->order_id} berhasil dibatalkan.");
    }
}
