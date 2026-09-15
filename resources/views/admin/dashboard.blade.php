@extends('layouts.admin')

@section('title', 'Dashboard Ringkasan')
@section('header_title', 'Dashboard Overview')
@section('header_subtitle', 'Pantau metrik pendapatan, langganan aktif, dan aktivitas unduhan secara real-time.')

@section('content')
<div class="space-y-8">

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Total Revenue -->
        <div class="glass-card rounded-2xl p-6 relative overflow-hidden group hover:border-brand-500/30 transition-all duration-300">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-brand-500/10 rounded-full blur-2xl group-hover:bg-brand-500/20 transition-all"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Omset</span>
                <div class="w-10 h-10 rounded-xl bg-brand-500/10 border border-brand-500/20 flex items-center justify-center text-brand-400">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </div>
                <div class="flex items-center gap-2 mt-2 text-xs text-slate-400">
                    <span class="font-semibold text-emerald-400 flex items-center gap-0.5">
                        <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        Rp {{ number_format($todayRevenue, 0, ',', '.') }}
                    </span>
                    <span>hari ini</span>
                </div>
            </div>
        </div>

        <!-- Active PRO Subscribers -->
        <div class="glass-card rounded-2xl p-6 relative overflow-hidden group hover:border-accent-purple/30 transition-all duration-300">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-accent-purple/10 rounded-full blur-2xl group-hover:bg-accent-purple/20 transition-all"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Langganan PRO Aktif</span>
                <div class="w-10 h-10 rounded-xl bg-accent-purple/10 border border-accent-purple/20 flex items-center justify-center text-accent-purple">
                    <i data-lucide="badge-check" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                    {{ number_format($activeSubscriptionsCount) }} <span class="text-sm font-normal text-slate-400">Akun</span>
                </div>
                <div class="flex items-center gap-2 mt-2 text-xs text-slate-400">
                    <span class="text-accent-purple font-medium">{{ $totalProUsers }} user aktif</span>
                    <span>dari {{ $totalUsers }} total</span>
                </div>
            </div>
        </div>

        <!-- Transactions Count -->
        <div class="glass-card rounded-2xl p-6 relative overflow-hidden group hover:border-emerald-500/30 transition-all duration-300">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Transaksi Sukses</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                    {{ number_format($paidTransactionsCount) }}
                </div>
                <div class="flex items-center gap-2 mt-2 text-xs">
                    @if($pendingTransactionsCount > 0)
                        <span class="text-amber-400 font-semibold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping"></span>
                            {{ $pendingTransactionsCount }} pending
                        </span>
                    @else
                        <span class="text-slate-400">Semua diproses</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Downloads Usage -->
        <div class="glass-card rounded-2xl p-6 relative overflow-hidden group hover:border-accent-cyan/30 transition-all duration-300">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-accent-cyan/10 rounded-full blur-2xl group-hover:bg-accent-cyan/20 transition-all"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Download Hari Ini</span>
                <div class="w-10 h-10 rounded-xl bg-accent-cyan/10 border border-accent-cyan/20 flex items-center justify-center text-accent-cyan">
                    <i data-lucide="download-cloud" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                    {{ number_format($todayDownloads) }}
                </div>
                <div class="flex items-center gap-2 mt-2 text-xs text-slate-400">
                    <span>{{ number_format($totalDownloads) }} total keseluruhan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Banner -->
    <div class="glass-panel rounded-2xl p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4 border-brand-500/20 bg-gradient-to-r from-brand-950/40 via-dark-900 to-indigo-950/40">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-brand-500/20 text-brand-400 flex items-center justify-center">
                <i data-lucide="zap" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-white">Aksi Cepat Administrator</h2>
                <p class="text-xs text-slate-400">Berikan akses langganan ke pengguna atau kelola pengaturan fitur sistem.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.subscriptions.index') }}" class="px-4 py-2 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-brand-600/30 flex items-center gap-2 transition">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Aktifkan Pro ke User</span>
            </a>
            <a href="{{ route('admin.settings.index') }}" class="px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 text-white text-xs font-semibold rounded-xl flex items-center gap-2 transition">
                <i data-lucide="sliders" class="w-4 h-4 text-slate-400"></i>
                <span>Atur Fitur &amp; Kuota</span>
            </a>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Revenue Trend Chart -->
        <div class="lg:col-span-2 glass-card rounded-2xl p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-base font-bold text-white tracking-tight">Tren Pendapatan (7 Hari Terakhir)</h2>
                    <p class="text-xs text-slate-400">Statistik omset harian dari transaksi yang telah dibayar</p>
                </div>
                <div class="px-3 py-1 rounded-lg bg-brand-500/10 border border-brand-500/20 text-brand-400 text-xs font-bold">
                    IDR (Rupiah)
                </div>
            </div>
            <div class="h-64 sm:h-72 w-full">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Plan Distribution -->
        <div class="glass-card rounded-2xl p-6 flex flex-col justify-between">
            <div class="mb-4">
                <h2 class="text-base font-bold text-white tracking-tight">Distribusi Paket Aktif</h2>
                <p class="text-xs text-slate-400">Komposisi paket langganan user saat ini</p>
            </div>
            <div class="h-56 flex items-center justify-center relative">
                <canvas id="planChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($plans as $p)
                    <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-dark-850 border border-white/5">
                        <span class="font-medium text-slate-300">{{ $p->name }}</span>
                        <span class="font-bold text-brand-400">{{ $p->subscriptions_count }} aktif</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Tables Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Recent Transactions -->
        <div class="glass-card rounded-2xl p-6 flex flex-col">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-base font-bold text-white tracking-tight">Transaksi Terbaru</h2>
                    <p class="text-xs text-slate-400">Aktivitas pembayaran terakhir</p>
                </div>
                <a href="{{ route('admin.transactions.index') }}" class="text-xs font-semibold text-brand-400 hover:text-brand-300 flex items-center gap-1">
                    <span>Lihat Semua</span>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-white/5 text-slate-400 font-semibold">
                            <th class="pb-3">Order ID / User</th>
                            <th class="pb-3">Paket</th>
                            <th class="pb-3">Nominal</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($recentTransactions as $tx)
                            <tr class="group hover:bg-white/[0.02] transition">
                                <td class="py-3.5">
                                    <div class="font-mono font-semibold text-white">{{ $tx->order_id }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $tx->user ? $tx->user->email : 'Guest' }}</div>
                                </td>
                                <td class="py-3.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-dark-750 text-slate-300 border border-white/10">
                                        {{ $tx->plan ? $tx->plan->name : '-' }}
                                    </span>
                                </td>
                                <td class="py-3.5 font-semibold text-white">
                                    Rp {{ number_format($tx->gross_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5">
                                    @if($tx->payment_status === 'paid')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">PAID</span>
                                    @elseif($tx->payment_status === 'pending')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">PENDING</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">{{ strtoupper($tx->payment_status) }}</span>
                                    @endif
                                </td>
                                <td class="py-3.5 text-right">
                                    @if($tx->payment_status === 'pending')
                                        <form action="{{ route('admin.transactions.approve', $tx) }}" method="POST" class="inline" onsubmit="return confirm('Setujui transaksi ini dan otomatis aktifkan langganan PRO user?')">
                                            @csrf
                                            <button type="submit" class="px-2 py-1 rounded bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/30 font-semibold text-[11px] transition">
                                                Approve
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-slate-400 text-[11px]">{{ $tx->paid_at ? $tx->paid_at->format('d M H:i') : $tx->created_at->format('d M H:i') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400">Belum ada transaksi recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Subscriptions -->
        <div class="glass-card rounded-2xl p-6 flex flex-col">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-base font-bold text-white tracking-tight">Langganan User Aktif</h2>
                    <p class="text-xs text-slate-400">Akun-akun dengan status PRO aktif</p>
                </div>
                <a href="{{ route('admin.subscriptions.index') }}" class="text-xs font-semibold text-brand-400 hover:text-brand-300 flex items-center gap-1">
                    <span>Lihat Semua</span>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-white/5 text-slate-400 font-semibold">
                            <th class="pb-3">Pengguna</th>
                            <th class="pb-3">Paket</th>
                            <th class="pb-3">Berlaku Hingga</th>
                            <th class="pb-3 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($recentSubscriptions as $sub)
                            <tr class="group hover:bg-white/[0.02] transition">
                                <td class="py-3.5">
                                    <div class="font-semibold text-white">{{ $sub->user ? $sub->user->name : 'User' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $sub->user ? $sub->user->email : '-' }}</div>
                                </td>
                                <td class="py-3.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-brand-500/20 text-brand-400 border border-brand-500/30">
                                        {{ $sub->plan ? $sub->plan->name : 'PRO' }}
                                    </span>
                                </td>
                                <td class="py-3.5">
                                    @if(is_null($sub->expires_at))
                                        <span class="font-bold text-accent-purple">Selamanya (Lifetime)</span>
                                    @else
                                        <span class="text-slate-300">{{ $sub->expires_at->format('d M Y') }}</span>
                                        <span class="text-[10px] text-slate-400 block">({{ $sub->expires_at->diffForHumans() }})</span>
                                    @endif
                                </td>
                                <td class="py-3.5 text-right">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                        AKTIF
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400">Belum ada langganan aktif.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Revenue Area Chart
    const revCtx = document.getElementById('revenueChart').getContext('2d');
    const revGradient = revCtx.createLinearGradient(0, 0, 0, 300);
    revGradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
    revGradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

    new Chart(revCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($revenueChartLabels) !!},
            datasets: [{
                label: 'Omset (Rp)',
                data: {!! json_encode($revenueChartData) !!},
                borderColor: '#3b82f6',
                borderWidth: 3,
                backgroundColor: revGradient,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#ffffff',
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111726',
                    titleColor: '#ffffff',
                    bodyColor: '#60a5fa',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            return ' Rp ' + context.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#64748b', font: { family: 'Inter', size: 11 } }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: {
                        color: '#64748b',
                        font: { family: 'Inter', size: 11 },
                        callback: function(value) {
                            if (value >= 1000000) return 'Rp ' + (value/1000000).toFixed(1) + 'M';
                            if (value >= 1000) return 'Rp ' + (value/1000).toFixed(0) + 'k';
                            return 'Rp ' + value;
                        }
                    }
                }
            }
        }
    });

    // Plan Doughnut Chart
    const planCtx = document.getElementById('planChart').getContext('2d');
    new Chart(planCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($plans->pluck('name')) !!},
            datasets: [{
                data: {!! json_encode($plans->pluck('subscriptions_count')) !!},
                backgroundColor: ['#3b82f6', '#a855f7', '#10b981', '#f59e0b'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>
@endpush
