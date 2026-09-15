@extends('layouts.admin')

@section('title', 'Kelola Pengguna')
@section('header_title', 'Daftar Pengguna')
@section('header_subtitle', 'Kelola seluruh akun pengguna, berikan akses langganan PRO langsung, dan atur role administrator.')

@section('content')
<div class="space-y-6" x-data="{ 
    grantModalOpen: false, 
    roleModalOpen: false,
    selectedUser: null,
    selectedUserName: '',
    selectedUserRole: 'user',
    openGrant(user) {
        this.selectedUser = user;
        this.selectedUserName = user.name;
        this.grantModalOpen = true;
    },
    openRole(user) {
        this.selectedUser = user;
        this.selectedUserName = user.name;
        this.selectedUserRole = user.role;
        this.roleModalOpen = true;
    }
}">

    <!-- Filter & Search Bar -->
    <div class="glass-panel rounded-2xl p-5">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Search -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Cari nama, email, device ID..." 
                       class="w-full bg-dark-850 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>

            <!-- Role Filter -->
            <div>
                <select name="role" onchange="this.form.submit()" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Semua Role</option>
                    <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User Biasa</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                </select>
            </div>

            <!-- Status Pro Filter -->
            <div>
                <select name="status" onchange="this.form.submit()" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Semua Status Langganan</option>
                    <option value="pro" {{ request('status') === 'pro' ? 'selected' : '' }}>PRO Aktif ⭐</option>
                    <option value="free" {{ request('status') === 'free' ? 'selected' : '' }}>Free User</option>
                </select>
            </div>

            <!-- Submit & Reset -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-brand-600 hover:bg-brand-500 text-white font-semibold rounded-xl text-xs flex items-center justify-center gap-2 transition">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Terapkan</span>
                </button>
                @if(request()->hasAny(['search', 'role', 'status']))
                    <a href="{{ route('admin.users.index') }}" class="p-2.5 bg-dark-800 hover:bg-dark-750 text-slate-300 rounded-xl text-xs transition" title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Users Table Card -->
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="p-5 border-b border-white/5 flex items-center justify-between">
            <h2 class="text-sm font-bold text-white tracking-tight">Daftar Pengguna Terdaftar ({{ $users->total() }})</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-white/5 bg-dark-850/50 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3.5 px-5">Pengguna</th>
                        <th class="py-3.5 px-4">Role</th>
                        <th class="py-3.5 px-4">Status Langganan</th>
                        <th class="py-3.5 px-4">Total Unduhan</th>
                        <th class="py-3.5 px-4">Device ID</th>
                        <th class="py-3.5 px-4">Terdaftar</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($users as $user)
                        <tr class="group hover:bg-white/[0.02] transition">
                            <!-- User Info -->
                            <td class="py-4 px-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-600 flex items-center justify-center font-bold text-white text-xs shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.users.show', $user) }}" class="font-bold text-white hover:text-brand-400 transition">
                                            {{ $user->name }}
                                        </a>
                                        <div class="text-[11px] text-slate-400">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Role -->
                            <td class="py-4 px-4">
                                @if($user->role === 'admin')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-purple-500/20 text-purple-300 border border-purple-500/30 flex items-center gap-1 w-max">
                                        <i data-lucide="shield-check" class="w-3 h-3"></i>
                                        ADMIN
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-dark-750 text-slate-300 border border-white/10 w-max block">
                                        USER
                                    </span>
                                @endif
                            </td>

                            <!-- Subscription Status -->
                            <td class="py-4 px-4">
                                @if($user->isPro())
                                    <div class="space-y-1">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 inline-flex items-center gap-1">
                                            <i data-lucide="star" class="w-2.5 h-2.5 fill-emerald-400"></i>
                                            PRO: {{ $user->activeSubscription?->plan?->name ?? 'Active' }}
                                        </span>
                                        <div class="text-[10px] text-slate-400">
                                            @if(is_null($user->activeSubscription?->expires_at))
                                                <span class="text-accent-purple font-medium">Lifetime</span>
                                            @else
                                                Hingga {{ $user->activeSubscription?->expires_at->format('d M Y') }}
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-dark-800 text-slate-400 border border-white/5">
                                        Free Tier
                                    </span>
                                @endif
                            </td>

                            <!-- Total Downloads -->
                            <td class="py-4 px-4 font-semibold text-slate-300">
                                {{ number_format($user->total_downloads ?? 0) }} unduhan
                            </td>

                            <!-- Device ID -->
                            <td class="py-4 px-4">
                                @if($user->device_id)
                                    <span class="font-mono text-[11px] text-slate-400 bg-dark-850 px-2 py-1 rounded border border-white/5" title="{{ $user->device_id }}">
                                        {{ Str::limit($user->device_id, 12) }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[11px]">-</span>
                                @endif
                            </td>

                            <!-- Joined Date -->
                            <td class="py-4 px-4 text-slate-400 text-[11px]">
                                {{ $user->created_at->format('d M Y') }}
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    
                                    <!-- Grant Pro Button -->
                                    <button @click="openGrant({{ json_encode($user) }})" 
                                            title="Berikan / Upgrade Langganan PRO"
                                            class="p-1.5 rounded-lg bg-brand-500/10 hover:bg-brand-500/20 text-brand-400 border border-brand-500/20 transition">
                                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                                    </button>

                                    <!-- Edit Role Button -->
                                    <button @click="openRole({{ json_encode($user) }})" 
                                            title="Ubah Role (User / Admin)"
                                            class="p-1.5 rounded-lg bg-dark-800 hover:bg-dark-750 text-slate-300 border border-white/10 transition">
                                        <i data-lucide="shield" class="w-4 h-4"></i>
                                    </button>

                                    <!-- User Detail Link -->
                                    <a href="{{ route('admin.users.show', $user) }}" 
                                       title="Lihat Detail Profil"
                                       class="p-1.5 rounded-lg bg-dark-800 hover:bg-dark-750 text-slate-300 border border-white/10 transition">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>

                                    <!-- Delete Button -->
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna {{ $user->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus Pengguna" class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 transition">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                Tidak ada data pengguna yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-white/5 bg-dark-850/40">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: Grant Pro Subscription Directly -->
    <div x-show="grantModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" 
         x-cloak>
        <div class="bg-dark-900 border border-white/10 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5" @click.outside="grantModalOpen = false">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-brand-500/20 text-brand-400 flex items-center justify-center">
                        <i data-lucide="sparkles" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Aktifkan Langganan PRO</h3>
                        <p class="text-xs text-slate-400">Pilih paket untuk di-assign langsung</p>
                    </div>
                </div>
                <button @click="grantModalOpen = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form :action="selectedUser ? `/admin/users/${selectedUser.id}/grant` : '#'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Pengguna Target</label>
                    <input type="text" :value="selectedUserName" disabled class="w-full bg-dark-850 border border-white/5 rounded-xl px-3.5 py-2.5 text-xs text-slate-400">
                </div>

                <div>
                    <label for="plan_id" class="block text-xs font-semibold text-slate-300 mb-1">Pilih Paket Langganan</label>
                    <select name="plan_id" id="plan_id" required class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">
                                {{ $plan->name }} - Rp {{ number_format($plan->price, 0, ',', '.') }} ({{ $plan->duration_days ? $plan->duration_days . ' Hari' : 'Lifetime' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="duration_days" class="block text-xs font-semibold text-slate-300 mb-1">Kustom Durasi Tambahan (Opsional)</label>
                    <input type="number" name="duration_days" id="duration_days" placeholder="Kosongkan untuk mengikuti durasi default paket" min="1" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <p class="text-[11px] text-slate-400 mt-1">Kosongkan jika ingin menggunakan durasi asli dari paket yang dipilih.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="grantModalOpen = false" class="px-4 py-2 bg-dark-800 hover:bg-dark-750 text-slate-300 text-xs font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-brand-600/30 transition">
                        Aktifkan Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Change User Role -->
    <div x-show="roleModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" 
         x-cloak>
        <div class="bg-dark-900 border border-white/10 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5" @click.outside="roleModalOpen = false">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center">
                        <i data-lucide="shield" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Ubah Role Pengguna</h3>
                        <p class="text-xs text-slate-400">Atur hak akses pengguna ke sistem</p>
                    </div>
                </div>
                <button @click="roleModalOpen = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form :action="selectedUser ? `/admin/users/${selectedUser.id}/role` : '#'" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')
                
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Pengguna Target</label>
                    <input type="text" :value="selectedUserName" disabled class="w-full bg-dark-850 border border-white/5 rounded-xl px-3.5 py-2.5 text-xs text-slate-400">
                </div>

                <div>
                    <label for="role" class="block text-xs font-semibold text-slate-300 mb-1">Pilih Role</label>
                    <select name="role" id="role" x-model="selectedUserRole" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="user">User Biasa</option>
                        <option value="admin">Administrator (Akses Penuh)</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="roleModalOpen = false" class="px-4 py-2 bg-dark-800 hover:bg-dark-750 text-slate-300 text-xs font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-purple-600/30 transition">
                        Simpan Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
