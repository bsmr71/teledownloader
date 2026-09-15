@extends('layouts.admin')

@section('title', 'Log Unduhan & Kuota')
@section('header_title', 'Log Penggunaan Unduhan Harian')
@section('header_subtitle', 'Pantau kuota unduhan yang digunakan oleh akun pengguna dan perangkat guest.')

@section('content')
<div class="space-y-6">

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="glass-card rounded-2xl p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Unduhan Hari Ini</span>
            <div class="text-2xl sm:text-3xl font-extrabold text-white mt-2">{{ number_format($todayTotal) }} <span class="text-xs font-normal text-slate-400">file</span></div>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Unduhan Keseluruhan</span>
            <div class="text-2xl sm:text-3xl font-extrabold text-brand-400 mt-2">{{ number_format($allTimeTotal) }} <span class="text-xs font-normal text-slate-400">file</span></div>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Perangkat Aktif Hari Ini</span>
            <div class="text-2xl sm:text-3xl font-extrabold text-accent-cyan mt-2">{{ number_format($uniqueDevicesToday) }} <span class="text-xs font-normal text-slate-400">device unik</span></div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="glass-panel rounded-2xl p-5">
        <form method="GET" action="{{ route('admin.downloads.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            
            <!-- Search -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Cari IP, Device ID, nama user..." 
                       class="w-full bg-dark-850 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>

            <!-- Date Filter -->
            <div>
                <input type="date" 
                       name="date" 
                       value="{{ request('date') }}" 
                       class="w-full bg-dark-850 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-brand-600 hover:bg-brand-500 text-white font-semibold rounded-xl text-xs flex items-center justify-center gap-2 transition">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Terapkan Filter</span>
                </button>
                @if(request()->hasAny(['search', 'date']))
                    <a href="{{ route('admin.downloads.index') }}" class="p-2.5 bg-dark-800 hover:bg-dark-750 text-slate-300 rounded-xl text-xs transition" title="Reset">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Download Logs Table Card -->
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="p-5 border-b border-white/5 flex items-center justify-between">
            <h2 class="text-sm font-bold text-white tracking-tight">Data Log Kuota Unduhan ({{ $logs->total() }})</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-white/5 bg-dark-850/50 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3.5 px-5">Tanggal</th>
                        <th class="py-3.5 px-4">Pengguna</th>
                        <th class="py-3.5 px-4">Device ID</th>
                        <th class="py-3.5 px-4">Alamat IP</th>
                        <th class="py-3.5 px-4">Unduhan Terpakai</th>
                        <th class="py-3.5 px-4">Update Terakhir</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($logs as $log)
                        <tr class="group hover:bg-white/[0.02] transition">
                            
                            <!-- Date -->
                            <td class="py-4 px-5 font-semibold text-white">
                                {{ $log->download_date ? $log->download_date->format('d M Y') : '-' }}
                            </td>

                            <!-- User -->
                            <td class="py-4 px-4">
                                @if($log->user)
                                    <div class="font-semibold text-white">{{ $log->user->name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $log->user->email }}</div>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-dark-800 text-slate-400 border border-white/5">Guest (Tanpa Akun)</span>
                                @endif
                            </td>

                            <!-- Device ID -->
                            <td class="py-4 px-4 font-mono text-[11px] text-slate-300">
                                @if($log->device_id)
                                    <span class="bg-dark-850 px-2 py-1 rounded border border-white/5" title="{{ $log->device_id }}">
                                        {{ Str::limit($log->device_id, 16) }}
                                    </span>
                                @else
                                    <span class="text-slate-500">-</span>
                                @endif
                            </td>

                            <!-- IP Address -->
                            <td class="py-4 px-4 font-mono text-slate-300">
                                {{ $log->ip_address ?? '-' }}
                            </td>

                            <!-- Count -->
                            <td class="py-4 px-4">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold {{ $log->downloads_count >= 10 ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-dark-800 text-brand-400 border border-white/5' }}">
                                    {{ $log->downloads_count }} / {{ \App\Models\Setting::get('free_daily_limit', 10) }}
                                </span>
                            </td>

                            <!-- Last Updated -->
                            <td class="py-4 px-4 text-slate-400 text-[11px]">
                                {{ $log->updated_at ? $log->updated_at->format('H:i:s') : '-' }}
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-5 text-right">
                                @if($log->downloads_count > 0)
                                    <form action="{{ route('admin.downloads.reset', $log) }}" method="POST" class="inline" onsubmit="return confirm('Reset kuota download hari ini untuk pengguna/device ini ke 0?')">
                                        @csrf
                                        <button type="submit" 
                                                title="Reset kuota ke 0"
                                                class="px-2.5 py-1 rounded-lg bg-brand-500/10 hover:bg-brand-500/20 text-brand-400 border border-brand-500/20 font-semibold text-[11px] flex items-center gap-1 transition ml-auto">
                                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                            <span>Reset Kuota</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-slate-400">Sudah 0</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                Belum ada catatan log unduhan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-white/5 bg-dark-850/40">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
