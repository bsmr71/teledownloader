@extends('layouts.admin')

@section('title', 'Detail Pengguna - ' . $user->name)
@section('header_title', 'Profil Pengguna: ' . $user->name)
@section('header_subtitle', 'Informasi akun, status langganan aktif, histori transaksi, dan log penggunaan unduhan.')

@section('content')
<div class="space-y-6">

    <!-- Top User Overview Card -->
    <div class="glass-card rounded-2xl p-6 relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-brand-600 via-indigo-600 to-purple-600 flex items-center justify-center font-extrabold text-white text-2xl shadow-xl shadow-brand-500/20">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-white tracking-tight">{{ $user->name }}</h2>
                    @if($user->role === 'admin')
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-500/20 text-purple-300 border border-purple-500/30">ADMIN</span>
                    @endif
                </div>
                <p class="text-xs text-slate-400 mt-0.5">{{ $user->email }}</p>
                <div class="flex items-center gap-3 mt-2 text-[11px] text-slate-400">
                    <span>Bergabung: {{ $user->created_at->format('d M Y') }}</span>
                    <span>•</span>
                    <span>Device: {{ $user->device_id ?? 'Belum terikat' }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            @if($user->isPro())
                <div class="p-3 rounded-xl bg-emerald-950/60 border border-emerald-500/30 text-emerald-300 text-xs">
                    <div class="font-bold flex items-center gap-1">
                        <i data-lucide="star" class="w-3.5 h-3.5 fill-emerald-400"></i>
                        PRO: {{ $user->activeSubscription?->plan?->name }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                        @if(is_null($user->activeSubscription?->expires_at))
                            Lifetime (Akses Selamanya)
                        @else
                            Berakhir: {{ $user->activeSubscription?->expires_at->format('d M Y H:i') }}
                        @endif
                    </div>
                </div>
            @else
                <div class="p-3 rounded-xl bg-dark-850 border border-white/5 text-slate-400 text-xs">
                    <span class="font-medium text-slate-300">Free Tier</span> (Batas 10 download/hari)
                </div>
            @endif
            
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-dark-800 hover:bg-dark-750 text-slate-300 text-xs font-semibold rounded-xl transition">
                Kembali
            </a>
        </div>
    </div>

    <!-- 2 Column Details: Subscriptions & Transactions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Subscription History -->
        <div class="glass-card rounded-2xl p-6 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-white tracking-tight">Histori Langganan</h3>
                <span class="text-xs text-slate-400">{{ $user->subscriptions->count() }} record</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-white/5 text-slate-400 font-semibold">
                            <th class="pb-2.5">Paket</th>
                            <th class="pb-2.5">Mulai</th>
                            <th class="pb-2.5">Berakhir</th>
                            <th class="pb-2.5 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($user->subscriptions as $sub)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-semibold text-white">
                                    {{ $sub->plan ? $sub->plan->name : 'PRO' }}
                                </td>
                                <td class="py-3 text-slate-400">
                                    {{ $sub->starts_at ? $sub->starts_at->format('d M Y') : '-' }}
                                </td>
                                <td class="py-3">
                                    @if(is_null($sub->expires_at))
                                        <span class="text-accent-purple font-medium">Lifetime</span>
                                    @else
                                        <span class="text-slate-300">{{ $sub->expires_at->format('d M Y') }}</span>
                                    @endif
                                </td>
                                <td class="py-3 text-right">
                                    @if($sub->status === 'active')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">AKTIF</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-dark-800 text-slate-400">{{ strtoupper($sub->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400">Belum ada riwayat langganan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="glass-card rounded-2xl p-6 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-white tracking-tight">Histori Pembayaran</h3>
                <span class="text-xs text-slate-400">{{ $user->transactions->count() }} transaksi</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-white/5 text-slate-400 font-semibold">
                            <th class="pb-2.5">Order ID</th>
                            <th class="pb-2.5">Nominal</th>
                            <th class="pb-2.5">Gateway</th>
                            <th class="pb-2.5 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($user->transactions as $tx)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono font-medium text-white">
                                    {{ $tx->order_id }}
                                </td>
                                <td class="py-3 font-semibold text-slate-200">
                                    Rp {{ number_format($tx->gross_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 text-slate-400 uppercase">
                                    {{ $tx->payment_gateway }}
                                </td>
                                <td class="py-3 text-right">
                                    @if($tx->payment_status === 'paid')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">PAID</span>
                                    @elseif($tx->payment_status === 'pending')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">PENDING</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">{{ strtoupper($tx->payment_status) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400">Belum ada riwayat transaksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
