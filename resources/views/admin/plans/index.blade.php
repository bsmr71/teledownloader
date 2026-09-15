@extends('layouts.admin')

@section('title', 'Kelola Paket Harga')
@section('header_title', 'Paket Langganan (Pricing Plans)')
@section('header_subtitle', 'Atur opsi paket harga, batas kuota harian (misal: 50 foto/hari atau Unlimited), dan durasi aktif.')

@section('content')
<div class="space-y-8" x-data="{ 
    createModalOpen: false, 
    editModalOpen: false,
    selectedPlan: null,
    featuresText: '',
    openEdit(plan) {
        this.selectedPlan = plan;
        this.featuresText = (plan.features || []).join('\n');
        this.editModalOpen = true;
    }
}">

    <!-- Top Action Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-base font-bold text-white tracking-tight">Daftar Paket Langganan</h2>
            <p class="text-xs text-slate-400">Kelola harga, batas kuota unduhan per hari, durasi, dan fitur unggulan</p>
        </div>
        <button @click="createModalOpen = true" class="px-5 py-2.5 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-brand-600/30 flex items-center gap-2 transition">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Tambah Paket Baru</span>
        </button>
    </div>

    <!-- Plan Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($plans as $plan)
            <div class="glass-card rounded-2xl p-6 relative overflow-hidden flex flex-col justify-between border {{ $plan->is_featured ? 'border-brand-500/40 shadow-xl shadow-brand-500/10' : 'border-white/5' }}">
                
                @if($plan->is_featured)
                    <div class="absolute top-0 right-0 bg-gradient-to-l from-brand-600 to-indigo-600 text-white font-bold text-[10px] uppercase tracking-wider py-1 px-4 rounded-bl-xl shadow-md">
                        ⭐ Paling Populer
                    </div>
                @endif

                <div>
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-mono text-xs font-semibold uppercase tracking-wider text-brand-400">{{ $plan->code }}</span>
                        <div class="flex items-center gap-1.5">
                            @if($plan->is_active)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">AKTIF</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">NONAKTIF</span>
                            @endif
                        </div>
                    </div>

                    <!-- Title & Price -->
                    <h3 class="text-xl font-extrabold text-white">{{ $plan->name }}</h3>
                    <div class="mt-3 flex items-baseline gap-1.5">
                        <span class="text-2xl sm:text-3xl font-black text-white">Rp {{ number_format($plan->price, 0, ',', '.') }}</span>
                        <span class="text-xs text-slate-400 font-medium">
                            / {{ $plan->duration_days ? $plan->duration_days . ' Hari' : 'Sekali Bayar (Lifetime)' }}
                        </span>
                    </div>

                    <!-- Daily Quota Badge -->
                    <div class="mt-3">
                        @if($plan->daily_download_limit)
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-500/15 text-amber-300 border border-amber-500/30 text-xs font-bold">
                                <i data-lucide="image" class="w-3.5 h-3.5 text-amber-400"></i>
                                <span>Batas: {{ $plan->daily_download_limit }} Foto / Hari</span>
                            </div>
                        @else
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 text-xs font-bold">
                                <i data-lucide="infinity" class="w-3.5 h-3.5 text-emerald-400"></i>
                                <span>Download Tanpa Batas (Unlimited)</span>
                            </div>
                        @endif
                    </div>

                    <div class="mt-3 text-xs font-semibold text-accent-purple">
                        {{ $plan->subscriptions_count }} pelanggan aktif
                    </div>

                    <!-- Features List -->
                    <div class="mt-6 pt-5 border-t border-white/5 space-y-2.5">
                        @if(is_array($plan->features))
                            @foreach($plan->features as $feature)
                                <div class="flex items-start gap-2 text-xs text-slate-300">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5"></i>
                                    <span>{{ $feature }}</span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="mt-8 pt-4 border-t border-white/5 flex items-center justify-between gap-2">
                    <button @click="openEdit({{ json_encode($plan) }})" class="flex-1 py-2 px-3 bg-dark-800 hover:bg-dark-750 text-slate-200 text-xs font-semibold rounded-xl border border-white/10 flex items-center justify-center gap-1.5 transition">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        <span>Edit Paket &amp; Kuota</span>
                    </button>

                    <form action="{{ route('admin.plans.toggle', $plan) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="p-2 rounded-xl border text-xs font-semibold transition {{ $plan->is_active ? 'bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border-amber-500/20' : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border-emerald-500/20' }}" title="{{ $plan->is_active ? 'Nonaktifkan Paket' : 'Aktifkan Paket' }}">
                            <i data-lucide="{{ $plan->is_active ? 'eye-off' : 'eye' }}" class="w-4 h-4"></i>
                        </button>
                    </form>

                    <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST" onsubmit="return confirm('Hapus paket {{ $plan->name }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs transition" title="Hapus Paket">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal: Create Plan -->
    <div x-show="createModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" 
         x-cloak>
        <div class="bg-dark-900 border border-white/10 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5" @click.outside="createModalOpen = false">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-brand-500/20 text-brand-400 flex items-center justify-center">
                        <i data-lucide="plus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Tambah Paket Langganan</h3>
                        <p class="text-xs text-slate-400">Buat opsi paket langganan baru &amp; atur kuota hariannya</p>
                    </div>
                </div>
                <button @click="createModalOpen = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('admin.plans.store') }}" method="POST" class="space-y-4">
                @csrf
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="create_name" class="block text-xs font-semibold text-slate-300 mb-1">Nama Paket</label>
                        <input type="text" name="name" id="create_name" required placeholder="Misal: Paket Mingguan (Starter)" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label for="create_code" class="block text-xs font-semibold text-slate-300 mb-1">Kode Unik (Slug)</label>
                        <input type="text" name="code" id="create_code" placeholder="Misal: weekly (opsional)" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="create_price" class="block text-xs font-semibold text-slate-300 mb-1">Harga (IDR)</label>
                        <input type="number" name="price" id="create_price" required placeholder="Contoh: 9900" min="0" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label for="create_duration" class="block text-xs font-semibold text-slate-300 mb-1">Durasi Hari (Kosong = Lifetime)</label>
                        <input type="number" name="duration_days" id="create_duration" placeholder="Misal: 7" min="1" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <!-- Daily Download Limit Input -->
                <div class="p-3.5 rounded-xl bg-dark-850/80 border border-white/10">
                    <label for="create_daily_limit" class="block text-xs font-bold text-brand-400 mb-1 flex items-center gap-1.5">
                        <i data-lucide="image" class="w-3.5 h-3.5"></i>
                        <span>Batas Download Foto/Video Harian (Per Hari)</span>
                    </label>
                    <input type="number" name="daily_download_limit" id="create_daily_limit" placeholder="Contoh: 50 (Kosongkan jika Unlimited/Tanpa Batas)" min="1" class="w-full bg-dark-900 border border-white/10 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <p class="text-[11px] text-slate-400 mt-1">Isi <strong>50</strong> jika paket ini dibatasi 50 foto/hari, atau kosongkan jika tanpa batas.</p>
                </div>

                <div>
                    <label for="create_features" class="block text-xs font-semibold text-slate-300 mb-1">Daftar Fitur (1 fitur per baris)</label>
                    <textarea name="features" id="create_features" rows="3" placeholder="Maksimal 50 unduhan per hari&#10;Kualitas Full HD original&#10;Masa aktif 7 hari" class="w-full bg-dark-850 border border-white/10 rounded-xl p-3 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
                </div>

                <div class="flex items-center gap-6 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" class="w-4 h-4 rounded bg-dark-850 text-brand-600 focus:ring-brand-500">
                        <span class="text-xs text-slate-300">Tandai Paling Populer (Featured)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded bg-dark-850 text-brand-600 focus:ring-brand-500">
                        <span class="text-xs text-slate-300">Langsung Aktifkan</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 bg-dark-800 hover:bg-dark-750 text-slate-300 text-xs font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-brand-600/30 transition">
                        Simpan Paket
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Plan -->
    <div x-show="editModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" 
         x-cloak>
        <div class="bg-dark-900 border border-white/10 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5" @click.outside="editModalOpen = false">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-brand-500/20 text-brand-400 flex items-center justify-center">
                        <i data-lucide="edit-3" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Edit Paket &amp; Kuota Harian</h3>
                        <p class="text-xs text-slate-400" x-text="selectedPlan ? selectedPlan.name : ''"></p>
                    </div>
                </div>
                <button @click="editModalOpen = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form :action="selectedPlan ? `/admin/plans/${selectedPlan.id}` : '#'" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="edit_name" class="block text-xs font-semibold text-slate-300 mb-1">Nama Paket</label>
                        <input type="text" name="name" id="edit_name" required :value="selectedPlan ? selectedPlan.name : ''" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label for="edit_price" class="block text-xs font-semibold text-slate-300 mb-1">Harga (IDR)</label>
                        <input type="number" name="price" id="edit_price" required :value="selectedPlan ? selectedPlan.price : 0" min="0" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <div>
                    <label for="edit_duration" class="block text-xs font-semibold text-slate-300 mb-1">Durasi Hari (Kosongkan = Lifetime)</label>
                    <input type="number" name="duration_days" id="edit_duration" :value="selectedPlan ? selectedPlan.duration_days : ''" min="1" placeholder="Kosongkan untuk Lifetime" class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <!-- Daily Download Limit Input in Edit Modal -->
                <div class="p-3.5 rounded-xl bg-dark-850/80 border border-white/10">
                    <label for="edit_daily_limit" class="block text-xs font-bold text-brand-400 mb-1 flex items-center gap-1.5">
                        <i data-lucide="image" class="w-3.5 h-3.5"></i>
                        <span>Batas Download Foto/Video Harian (Per Hari)</span>
                    </label>
                    <input type="number" name="daily_download_limit" id="edit_daily_limit" :value="selectedPlan ? selectedPlan.daily_download_limit : ''" placeholder="Contoh: 50 (Kosongkan jika Unlimited/Tanpa Batas)" min="1" class="w-full bg-dark-900 border border-white/10 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <p class="text-[11px] text-slate-400 mt-1">Isi <strong>50</strong> jika dibatasi 50 foto/hari, atau kosongkan jika ingin unlimited.</p>
                </div>

                <div>
                    <label for="edit_features" class="block text-xs font-semibold text-slate-300 mb-1">Daftar Fitur (1 fitur per baris)</label>
                    <textarea name="features" id="edit_features" rows="3" x-model="featuresText" class="w-full bg-dark-850 border border-white/10 rounded-xl p-3 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
                </div>

                <div class="flex items-center gap-6 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" :checked="selectedPlan && selectedPlan.is_featured" class="w-4 h-4 rounded bg-dark-850 text-brand-600 focus:ring-brand-500">
                        <span class="text-xs text-slate-300">Tandai Paling Populer (Featured)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" :checked="selectedPlan && selectedPlan.is_active" class="w-4 h-4 rounded bg-dark-850 text-brand-600 focus:ring-brand-500">
                        <span class="text-xs text-slate-300">Paket Aktif</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-dark-800 hover:bg-dark-750 text-slate-300 text-xs font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-brand-600/30 transition">
                        Perbarui Paket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
