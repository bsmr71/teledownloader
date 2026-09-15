<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Pengguna - Tele Downloader PRO</title>
    
    @if(Auth::check())
    <meta name="tld-auth-user" content="{{ json_encode([
        'token' => $user->createToken('web-sync')->plainTextToken,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_pro' => $isPro,
            'plan' => $plan?->code ?? 'free',
            'plan_name' => $plan?->name ?? ($isPro ? 'PRO' : 'Free Tier')
        ]
    ]) }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        dark: {
                            950: '#070a12',
                            900: '#0b0f19',
                            850: '#111726',
                            800: '#161f36',
                            750: '#1c2846',
                        },
                        brand: {
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        accent: {
                            cyan: '#06b6d4',
                            purple: '#a855f7',
                            gold: '#f59e0b',
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        .glass-panel {
            background: rgba(11, 15, 25, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-card {
            background: rgba(17, 23, 38, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="bg-dark-950 text-slate-200 font-sans antialiased min-h-screen relative overflow-x-hidden">

    <!-- Background Orbs -->
    <div class="fixed top-0 left-1/4 -translate-x-1/2 w-96 h-96 bg-brand-600/15 blur-[120px] rounded-full pointer-events-none -z-10"></div>
    <div class="fixed top-1/3 right-10 w-96 h-96 bg-purple-600/10 blur-[130px] rounded-full pointer-events-none -z-10"></div>

    <!-- ── Navbar ── -->
    <header class="glass-panel sticky top-0 z-30 border-b border-white/10 px-6 py-4">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 via-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-brand-500/20 text-white ring-1 ring-white/20">
                    <i data-lucide="download" class="w-5 h-5 stroke-[2.5]"></i>
                </div>
                <div>
                    <h1 class="text-base font-extrabold text-white tracking-tight leading-tight">Tele Downloader PRO</h1>
                    <p class="text-[11px] text-slate-400">Portal Akun &amp; Langganan Pengguna</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2.5 px-3.5 py-1.5 rounded-xl bg-dark-850 border border-white/10 text-xs">
                    <div class="w-6 h-6 rounded-full bg-brand-500/20 text-brand-400 font-bold flex items-center justify-center text-[10px]">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <span class="font-semibold text-white">{{ $user->name }}</span>
                    @if($isPro)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">PRO</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/20 text-slate-300 border border-white/10">FREE</span>
                    @endif
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-xl transition" title="Keluar">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto p-6 space-y-8">
        
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-950/70 border border-emerald-500/40 text-emerald-200 text-xs font-semibold flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- ── Status Hero Card ── -->
        <div class="glass-card rounded-3xl p-6 sm:p-8 relative overflow-hidden border border-white/10 bg-gradient-to-r from-dark-900 via-dark-850 to-indigo-950/40">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        @if($isPro)
                            <span class="px-3 py-1 rounded-full text-xs font-black bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-md shadow-amber-500/20 flex items-center gap-1.5">
                                <i data-lucide="crown" class="w-3.5 h-3.5"></i>
                                <span>STATUS PRO AKTIF</span>
                            </span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-dark-750 text-slate-300 border border-white/10">
                                TIER PENGGUNA GRATIS
                            </span>
                        @endif
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-black text-white">
                        {{ $isPro ? ($plan ? $plan->name : 'Paket PRO') : 'Akun Free' }}
                    </h2>
                    <p class="text-xs text-slate-400 max-w-xl">
                        @if($isPro)
                            Akun Anda terhubung langsung dengan ekstensi Chrome. Nikmati unduhan cepat kualitas Full HD tanpa perlu kunci lisensi manual.
                        @else
                            Tingkatkan akun Anda untuk mendapatkan kecepatan unduhan tanpa batas, batch download multi-select, dan kualitas Full HD original.
                        @endif
                    </p>
                </div>

                <!-- Quota / Expiry Box -->
                <div class="grid grid-cols-2 gap-3 w-full md:w-auto shrink-0">
                    <div class="p-4 rounded-2xl bg-dark-900/90 border border-white/10 text-center min-w-[140px]">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Kuota Hari Ini</span>
                        <div class="text-xl font-black text-white mt-1">
                            @if($isUnlimited)
                                <span class="text-emerald-400">Unlimited</span>
                            @else
                                <span class="text-brand-400">{{ $remaining }}</span><span class="text-xs text-slate-400">/{{ $dailyLimit }}</span>
                            @endif
                        </div>
                        <span class="text-[10px] text-slate-400 block mt-0.5">Reset tiap 00:00</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-dark-900/90 border border-white/10 text-center min-w-[140px]">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Masa Aktif</span>
                        <div class="text-base font-extrabold text-white mt-1.5">
                            @if($isPro)
                                @if(is_null($activeSub->expires_at))
                                    <span class="text-accent-purple font-black">Lifetime</span>
                                @else
                                    <span>{{ $activeSub->expires_at->format('d M Y') }}</span>
                                @endif
                            @else
                                <span class="text-slate-400">Selamanya</span>
                            @endif
                        </div>
                        <span class="text-[10px] text-slate-400 block mt-0.5">
                            {{ $isPro && $activeSub->expires_at ? $activeSub->expires_at->diffForHumans() : 'Akses Dasar' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Upgrade / Pricing Plans Section ── -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white tracking-tight">Pilihan Paket Langganan</h3>
                    <p class="text-xs text-slate-400">Beli atau perpanjang paket langganan Anda secara instan</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($plans as $p)
                    <div class="glass-card rounded-2xl p-6 relative overflow-hidden flex flex-col justify-between border {{ $p->is_featured ? 'border-brand-500/40 shadow-xl shadow-brand-500/10' : 'border-white/5' }}">
                        @if($p->is_featured)
                            <div class="absolute top-0 right-0 bg-gradient-to-l from-brand-600 to-indigo-600 text-white font-bold text-[10px] uppercase tracking-wider py-1 px-4 rounded-bl-xl shadow-md">
                                ⭐ Paling Populer
                            </div>
                        @endif

                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-lg font-extrabold text-white">{{ $p->name }}</h4>
                            </div>

                            <div class="flex items-baseline gap-1.5">
                                <span class="text-2xl font-black text-white">Rp {{ number_format($p->price, 0, ',', '.') }}</span>
                                <span class="text-xs text-slate-400 font-medium">
                                    / {{ $p->duration_days ? $p->duration_days . ' Hari' : 'Sekali Bayar' }}
                                </span>
                            </div>

                            <!-- Quota badge -->
                            <div class="mt-3">
                                @if($p->daily_download_limit)
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-500/15 text-amber-300 border border-amber-500/30 text-[11px] font-bold">
                                        <i data-lucide="image" class="w-3.5 h-3.5 text-amber-400"></i>
                                        <span>Batas: {{ $p->daily_download_limit }} Foto / Hari</span>
                                    </div>
                                @else
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 text-[11px] font-bold">
                                        <i data-lucide="infinity" class="w-3.5 h-3.5 text-emerald-400"></i>
                                        <span>Download Tanpa Batas</span>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-5 pt-4 border-t border-white/5 space-y-2">
                                @if(is_array($p->features))
                                    @foreach($p->features as $f)
                                        <div class="flex items-start gap-2 text-xs text-slate-300">
                                            <i data-lucide="check" class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5"></i>
                                            <span>{{ $f }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-white/5">
                            <a href="https://t.me/{{ str_replace('@', '', \App\Models\Setting::get('support_telegram', 'TeleDownloaderSupport')) }}" target="_blank" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition {{ $p->is_featured ? 'bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 text-white shadow-lg shadow-brand-600/30' : 'bg-dark-800 hover:bg-dark-750 text-slate-200 border border-white/10' }}">
                                <span>Beli / Upgrade Paket</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- ── Transaction Invoices History ── -->
        <div class="glass-card rounded-2xl overflow-hidden border border-white/10">
            <div class="p-5 border-b border-white/5 flex items-center justify-between">
                <h3 class="text-sm font-bold text-white tracking-tight">Riwayat Pembayaran &amp; Transaksi</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-white/5 bg-dark-850/50 text-slate-400 font-semibold uppercase tracking-wider">
                            <th class="py-3.5 px-5">Order ID</th>
                            <th class="py-3.5 px-4">Paket</th>
                            <th class="py-3.5 px-4">Nominal</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-5 text-right">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($transactions as $tx)
                            <tr class="hover:bg-white/[0.02] transition">
                                <td class="py-4 px-5 font-mono text-slate-300 font-semibold">
                                    {{ $tx->order_id }}
                                </td>
                                <td class="py-4 px-4 font-bold text-white">
                                    {{ $tx->plan ? $tx->plan->name : 'PRO' }}
                                </td>
                                <td class="py-4 px-4 text-emerald-400 font-bold">
                                    Rp {{ number_format($tx->gross_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-4 px-4">
                                    @if($tx->payment_status === 'paid')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                            LUNAS
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                            PENDING
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-5 text-right text-slate-400">
                                    {{ $tx->created_at->format('d M Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400">
                                    Belum ada transaksi pembayaran yang tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <footer class="max-w-6xl mx-auto p-6 text-center text-xs text-slate-500 border-t border-white/5 mt-12">
        &copy; {{ date('Y') }} {{ \App\Models\Setting::get('app_name', 'Tele Downloader PRO') }}. All rights reserved.
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
