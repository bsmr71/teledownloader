@extends('layouts.admin')

@section('title', 'Kelola Langganan User')
@section('header_title', 'Langganan Pengguna (Direct Subscription)')
@section('header_subtitle', 'Kelola akun pengguna PRO, berikan akses paket secara langsung, perpanjang masa aktif, atau batalkan langganan.')

@section('content')
<div class="space-y-6" x-data="{ 
    createModalOpen: false, 
    extendModalOpen: false,
    selectedSub: null,
    selectedUserName: '',
    selectedPlanName: '',
    actionType: 'add_30',
    copiedKey: null,
    copy(text) {
        navigator.clipboard.writeText(text);
        this.copiedKey = text;
        setTimeout(() => this.copiedKey = null, 2500);
    },
    openExtend(sub) {
        this.selectedSub = sub;
        this.selectedUserName = sub.user ? sub.user.name : 'Pengguna';
        this.selectedPlanName = sub.plan ? sub.plan.name : 'PRO';
        this.extendModalOpen = true;
    }
}">

    <!-- Filter & Top Action Bar -->
    <div class="glass-panel rounded-2xl p-5 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
        
        <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
            
            <!-- Search -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Cari user, email, lisensi..." 
                       class="w-full bg-dark-850 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>

            <!-- Plan Filter -->
            <div>
                <select name="plan_id" onchange="this.form.submit()" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Semua Paket</option>
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}" {{ request('plan_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" onchange="this.form.submit()" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired (Kedaluwarsa)</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
        </form>

        <!-- New Direct Subscription Button -->
        <button @click="createModalOpen = true" class="px-5 py-2.5 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-brand-600/30 flex items-center justify-center gap-2 transition shrink-0">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>+ Berikan Langganan ke User</span>
        </button>
    </div>

    <!-- Subscriptions Table Card -->
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="p-5 border-b border-white/5 flex items-center justify-between">
            <h2 class="text-sm font-bold text-white tracking-tight">Daftar Langganan ({{ $subscriptions->total() }})</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-white/5 bg-dark-850/50 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3.5 px-5">Pengguna</th>
                        <th class="py-3.5 px-4">Paket</th>
                        <th class="py-3.5 px-4">Tanggal Mulai</th>
                        <th class="py-3.5 px-4">Berlaku Hingga</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">License Key (Ref)</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($subscriptions as $sub)
                        <tr class="group hover:bg-white/[0.02] transition">
                            
                            <!-- User -->
                            <td class="py-4 px-5">
                                <div class="font-bold text-white">
                                    {{ $sub->user ? $sub->user->name : 'Unassigned User' }}
                                </div>
                                <div class="text-[11px] text-slate-400">{{ $sub->user ? $sub->user->email : '-' }}</div>
                            </td>

                            <!-- Plan -->
                            <td class="py-4 px-4">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-brand-500/20 text-brand-400 border border-brand-500/30">
                                    {{ $sub->plan ? $sub->plan->name : 'PRO' }}
                                </span>
                            </td>

                            <!-- Starts At -->
                            <td class="py-4 px-4 text-slate-400">
                                {{ $sub->starts_at ? $sub->starts_at->format('d M Y') : '-' }}
                            </td>

                            <!-- Expires At -->
                            <td class="py-4 px-4">
                                @if(is_null($sub->expires_at))
                                    <span class="font-bold text-accent-purple">Lifetime (Selamanya)</span>
                                @else
                                    <div class="text-slate-200 font-medium">{{ $sub->expires_at->format('d M Y H:i') }}</div>
                                    <div class="text-[10px] {{ $sub->expires_at->isPast() ? 'text-rose-400 font-semibold' : 'text-slate-400' }}">
                                        {{ $sub->expires_at->diffForHumans() }}
                                    </div>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-4">
                                @if($sub->status === 'active' && ($sub->expires_at === null || $sub->expires_at->isFuture()))
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        AKTIF
                                    </span>
                                @elseif($sub->status === 'cancelled')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-dark-750 text-slate-400 border border-white/10">
                                        DIBATALKAN
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                        EXPIRED
                                    </span>
                                @endif
                            </td>

                            <!-- License Key -->
                            <td class="py-4 px-4">
                                <button @click="copy('{{ $sub->license_key }}')" 
                                        class="group/btn flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-dark-850 hover:bg-dark-800 border border-white/10 text-[11px] font-mono text-slate-300 hover:text-white transition"
                                        title="Klik untuk menyalin">
                                    <span x-text="copiedKey === '{{ $sub->license_key }}' ? 'Tersalin!' : '{{ $sub->license_key }}'"></span>
                                    <i data-lucide="copy" class="w-3 h-3 text-slate-400 group-hover/btn:text-brand-400"></i>
                                </button>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    
                                    <!-- Extend Button -->
                                    <button @click="openExtend({{ json_encode($sub) }})" 
                                            title="Perpanjang Masa Aktif"
                                            class="px-2.5 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 font-semibold text-[11px] flex items-center gap-1 transition">
                                        <i data-lucide="clock" class="w-3 h-3"></i>
                                        <span>Perpanjang</span>
                                    </button>

                                    <!-- Cancel Button -->
                                    @if($sub->status === 'active')
                                        <form action="{{ route('admin.subscriptions.cancel', $sub) }}" method="POST" class="inline" onsubmit="return confirm('Nonaktifkan langganan ini?')">
                                            @csrf
                                            <button type="submit" title="Nonaktifkan Langganan" class="p-1.5 rounded-lg bg-dark-800 hover:bg-dark-750 text-slate-400 hover:text-amber-400 border border-white/5 transition">
                                                <i data-lucide="ban" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Delete Button -->
                                    <form action="{{ route('admin.subscriptions.destroy', $sub) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data langganan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus Data" class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 transition">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                Belum ada data langganan yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subscriptions->hasPages())
            <div class="p-4 border-t border-white/5 bg-dark-850/40">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: Create Direct User Subscription -->
    <div x-show="createModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" 
         x-cloak>
        <div class="bg-dark-900 border border-white/10 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5" @click.outside="createModalOpen = false">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-brand-500/20 text-brand-400 flex items-center justify-center">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Berikan Langganan ke User</h3>
                        <p class="text-xs text-slate-400">Aktifkan paket PRO langsung tanpa kode lisensi</p>
                    </div>
                </div>
                <button @click="createModalOpen = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('admin.subscriptions.store') }}" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label for="create_user_id" class="block text-xs font-semibold text-slate-300 mb-1">Pilih Pengguna</label>
                    <select name="user_id" id="create_user_id" required class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">-- Pilih User Akun --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="create_plan_id" class="block text-xs font-semibold text-slate-300 mb-1">Pilih Paket Langganan</label>
                    <select name="plan_id" id="create_plan_id" required class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">
                                {{ $plan->name }} - Rp {{ number_format($plan->price, 0, ',', '.') }} ({{ $plan->duration_days ? $plan->duration_days . ' Hari' : 'Lifetime' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="create_duration_days" class="block text-xs font-semibold text-slate-300 mb-1">Kustom Durasi Hari (Opsional)</label>
                    <input type="number" name="duration_days" id="create_duration_days" placeholder="Default mengikuti durasi paket" min="1" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 bg-dark-800 hover:bg-dark-750 text-slate-300 text-xs font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-brand-600/30 transition">
                        Aktifkan Langganan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Extend Subscription -->
    <div x-show="extendModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" 
         x-cloak>
        <div class="bg-dark-900 border border-white/10 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5" @click.outside="extendModalOpen = false">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Perpanjang Masa Aktif</h3>
                        <p class="text-xs text-slate-400" x-text="`${selectedUserName} (${selectedPlanName})`"></p>
                    </div>
                </div>
                <button @click="extendModalOpen = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form :action="selectedSub ? `/admin/subscriptions/${selectedSub.id}/extend` : '#'" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2">Pilihan Tambahan Durasi</label>
                    <div class="grid grid-cols-2 gap-2.5">
                        <label class="p-3 rounded-xl border border-white/10 bg-dark-850 cursor-pointer flex items-center gap-2 hover:border-brand-500/40 transition">
                            <input type="radio" name="action_type" value="add_7" x-model="actionType" class="text-brand-600 focus:ring-brand-500">
                            <span class="text-xs font-medium text-white">+ 7 Hari</span>
                        </label>
                        <label class="p-3 rounded-xl border border-white/10 bg-dark-850 cursor-pointer flex items-center gap-2 hover:border-brand-500/40 transition">
                            <input type="radio" name="action_type" value="add_30" x-model="actionType" class="text-brand-600 focus:ring-brand-500">
                            <span class="text-xs font-medium text-white">+ 30 Hari</span>
                        </label>
                        <label class="p-3 rounded-xl border border-white/10 bg-dark-850 cursor-pointer flex items-center gap-2 hover:border-brand-500/40 transition">
                            <input type="radio" name="action_type" value="make_lifetime" x-model="actionType" class="text-brand-600 focus:ring-brand-500">
                            <span class="text-xs font-bold text-accent-purple">Jadikan Lifetime</span>
                        </label>
                        <label class="p-3 rounded-xl border border-white/10 bg-dark-850 cursor-pointer flex items-center gap-2 hover:border-brand-500/40 transition">
                            <input type="radio" name="action_type" value="add_custom" x-model="actionType" class="text-brand-600 focus:ring-brand-500">
                            <span class="text-xs font-medium text-white">Kustom Hari</span>
                        </label>
                    </div>
                </div>

                <div x-show="actionType === 'add_custom'" class="pt-1">
                    <label for="custom_days" class="block text-xs font-semibold text-slate-300 mb-1">Jumlah Hari Tambahan</label>
                    <input type="number" name="custom_days" id="custom_days" min="1" placeholder="Misal: 60" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="extendModalOpen = false" class="px-4 py-2 bg-dark-800 hover:bg-dark-750 text-slate-300 text-xs font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition">
                        Terapkan Perpanjangan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
