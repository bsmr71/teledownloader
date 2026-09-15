@extends('layouts.admin')

@section('title', 'Kelola Transaksi')
@section('header_title', 'Daftar Transaksi Pembayaran')
@section('header_subtitle', 'Pantau seluruh invoice pembayaran QRIS / E-Wallet dan setujui transaksi secara manual.')

@section('content')
<div class="space-y-6" x-data="{ 
    payloadModalOpen: false,
    selectedPayload: null,
    selectedOrderId: '',
    openPayload(tx) {
        this.selectedOrderId = tx.order_id;
        this.selectedPayload = JSON.stringify(tx.payload || {}, null, 2);
        this.payloadModalOpen = true;
    }
}">

    <!-- Filter & Search Bar -->
    <div class="glass-panel rounded-2xl p-5">
        <form method="GET" action="{{ route('admin.transactions.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Search -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Cari Order ID, user, VA..." 
                       class="w-full bg-dark-850 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" onchange="this.form.submit()" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Semua Status</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>PAID (Berhasil)</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>PENDING (Menunggu Pembayaran)</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>FAILED (Gagal)</option>
                </select>
            </div>

            <!-- Gateway Filter -->
            <div>
                <select name="gateway" onchange="this.form.submit()" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Semua Payment Gateway</option>
                    <option value="tripay" {{ request('gateway') === 'tripay' ? 'selected' : '' }}>Tripay</option>
                    <option value="midtrans" {{ request('gateway') === 'midtrans' ? 'selected' : '' }}>Midtrans</option>
                    <option value="bri" {{ request('gateway') === 'bri' ? 'selected' : '' }}>BRI Direct API</option>
                    <option value="manual" {{ request('gateway') === 'manual' ? 'selected' : '' }}>Manual Transfer</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-brand-600 hover:bg-brand-500 text-white font-semibold rounded-xl text-xs flex items-center justify-center gap-2 transition">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Terapkan</span>
                </button>
                @if(request()->hasAny(['search', 'status', 'gateway']))
                    <a href="{{ route('admin.transactions.index') }}" class="p-2.5 bg-dark-800 hover:bg-dark-750 text-slate-300 rounded-xl text-xs transition" title="Reset">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Transactions Table Card -->
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="p-5 border-b border-white/5 flex items-center justify-between">
            <h2 class="text-sm font-bold text-white tracking-tight">Daftar Transaksi ({{ $transactions->total() }})</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-white/5 bg-dark-850/50 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3.5 px-5">Order ID</th>
                        <th class="py-3.5 px-4">Pengguna</th>
                        <th class="py-3.5 px-4">Paket</th>
                        <th class="py-3.5 px-4">Nominal</th>
                        <th class="py-3.5 px-4">Gateway</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Waktu</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($transactions as $tx)
                        <tr class="group hover:bg-white/[0.02] transition">
                            
                            <!-- Order ID -->
                            <td class="py-4 px-5">
                                <span class="font-mono font-bold text-white">{{ $tx->order_id }}</span>
                                @if($tx->va_number)
                                    <div class="text-[10px] text-slate-400 font-mono">VA: {{ $tx->va_number }}</div>
                                @endif
                            </td>

                            <!-- User -->
                            <td class="py-4 px-4">
                                @if($tx->user)
                                    <div class="font-semibold text-white">{{ $tx->user->name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $tx->user->email }}</div>
                                @else
                                    <span class="text-slate-400 italic">Guest / Anonim</span>
                                @endif
                            </td>

                            <!-- Plan -->
                            <td class="py-4 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-dark-750 text-slate-300 border border-white/10">
                                    {{ $tx->plan ? $tx->plan->name : '-' }}
                                </span>
                            </td>

                            <!-- Gross Amount -->
                            <td class="py-4 px-4 font-bold text-white">
                                Rp {{ number_format($tx->gross_amount, 0, ',', '.') }}
                            </td>

                            <!-- Gateway & Payment Type -->
                            <td class="py-4 px-4">
                                <div class="font-semibold uppercase text-brand-400 text-[11px]">{{ $tx->payment_gateway }}</div>
                                <div class="text-[10px] text-slate-400 uppercase">{{ $tx->payment_type ?? 'Online' }}</div>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-4 px-4">
                                @if($tx->payment_status === 'paid')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 inline-flex items-center gap-1">
                                        <i data-lucide="check" class="w-3 h-3"></i>
                                        PAID
                                    </span>
                                @elseif($tx->payment_status === 'pending')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping"></span>
                                        PENDING
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                        {{ strtoupper($tx->payment_status) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Date / Paid At -->
                            <td class="py-4 px-4 text-slate-400 text-[11px]">
                                <div>{{ $tx->created_at->format('d M Y H:i') }}</div>
                                @if($tx->paid_at)
                                    <div class="text-[10px] text-emerald-400 font-medium">Dibayar: {{ $tx->paid_at->format('H:i') }}</div>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    
                                    <!-- Manual Approve Button -->
                                    @if($tx->payment_status === 'pending')
                                        <form action="{{ route('admin.transactions.approve', $tx) }}" method="POST" class="inline" onsubmit="return confirm('Approve transaksi #{{ $tx->order_id }} secara manual? Langganan PRO user akan langsung aktif.')">
                                            @csrf
                                            <button type="submit" 
                                                    title="Setujui Pembayaran & Aktifkan Langganan PRO"
                                                    class="px-2.5 py-1 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/30 font-semibold text-[11px] flex items-center gap-1 transition">
                                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                                <span>Approve</span>
                                            </button>
                                        </form>

                                        <!-- Cancel Transaction -->
                                        <form action="{{ route('admin.transactions.cancel', $tx) }}" method="POST" class="inline" onsubmit="return confirm('Batalkan transaksi ini?')">
                                            @csrf
                                            <button type="submit" 
                                                    title="Batalkan Transaksi"
                                                    class="p-1.5 rounded-lg bg-dark-800 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 border border-white/5 transition">
                                                <i data-lucide="x" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- View Payload JSON -->
                                    @if($tx->payload)
                                        <button @click="openPayload({{ json_encode($tx) }})" 
                                                title="Lihat Raw Gateway Payload"
                                                class="p-1.5 rounded-lg bg-dark-800 hover:bg-dark-750 text-slate-300 border border-white/10 transition">
                                            <i data-lucide="code" class="w-4 h-4"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                Tidak ada transaksi yang sesuai dengan kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="p-4 border-t border-white/5 bg-dark-850/40">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: View Payload JSON -->
    <div x-show="payloadModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" 
         x-cloak>
        <div class="bg-dark-900 border border-white/10 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.outside="payloadModalOpen = false">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-white">Gateway Response Data</h3>
                    <p class="text-xs text-slate-400 font-mono" x-text="`Order ID: #${selectedOrderId}`"></p>
                </div>
                <button @click="payloadModalOpen = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="bg-dark-950 p-4 rounded-xl border border-white/5 overflow-x-auto max-h-80">
                <pre class="text-xs font-mono text-emerald-400 leading-relaxed" x-text="selectedPayload"></pre>
            </div>

            <div class="flex justify-end pt-2">
                <button type="button" @click="payloadModalOpen = false" class="px-4 py-2 bg-dark-800 hover:bg-dark-750 text-slate-300 text-xs font-semibold rounded-xl transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
