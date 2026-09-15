@extends('layouts.admin')

@section('title', 'Pengaturan Sistem & Fitur')
@section('header_title', 'Pengaturan Sistem & Fitur')
@section('header_subtitle', 'Sesuaikan paket langganan (apa saja, berapa banyak kuota & berapa lama durasinya), payment gateway, dan fitur aplikasi.')

@section('content')
<div class="space-y-6" x-data="{ activeTab: 'plans' }">

    <!-- Tab Navigation -->
    <div class="glass-panel rounded-2xl p-2 flex flex-wrap items-center gap-2">
        
        <button @click="activeTab = 'plans'" 
                :class="activeTab === 'plans' ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-white/5'"
                class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2">
            <i data-lucide="layers" class="w-4 h-4"></i>
            <span>Paket &amp; Kuota Langganan</span>
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        </button>

        <button @click="activeTab = 'general'" 
                :class="activeTab === 'general' ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-white/5'"
                class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2">
            <i data-lucide="sliders" class="w-4 h-4"></i>
            <span>Umum &amp; Server</span>
        </button>

        <button @click="activeTab = 'features'" 
                :class="activeTab === 'features' ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-white/5'"
                class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2">
            <i data-lucide="toggle-left" class="w-4 h-4"></i>
            <span>Fitur Ekstensi</span>
        </button>

        <button @click="activeTab = 'payment'" 
                :class="activeTab === 'payment' ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-white/5'"
                class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2">
            <i data-lucide="credit-card" class="w-4 h-4"></i>
            <span>Payment Gateway</span>
        </button>

        <button @click="activeTab = 'support'" 
                :class="activeTab === 'support' ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-white/5'"
                class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2">
            <i data-lucide="message-square" class="w-4 h-4"></i>
            <span>Kontak &amp; Banner Promo</span>
        </button>
    </div>

    <!-- Main Settings Form -->
    <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="__has_booleans" value="1">

        <!-- TAB: Plans & Subscription Quotas -->
        <div x-show="activeTab === 'plans'" class="space-y-6" x-cloak>
            
            <!-- Intro Card -->
            <div class="glass-panel rounded-2xl p-5 border-brand-500/20 bg-gradient-to-r from-brand-950/40 via-dark-900 to-indigo-950/40 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-brand-500/20 text-brand-400 flex items-center justify-center">
                        <i data-lucide="sliders" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white">Konfigurasi Seluruh Paket Langganan</h2>
                        <p class="text-xs text-slate-400">Atur apa saja nama paketnya, berapa banyak kuota fotonya per hari, berapa lama durasinya, dan harganya.</p>
                    </div>
                </div>
                <a href="{{ route('admin.plans.index') }}" class="px-4 py-2 bg-dark-800 hover:bg-dark-750 text-slate-200 border border-white/10 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    <span>Buka Menu Paket</span>
                </a>
            </div>

            <!-- Plans Editor Cards Grid -->
            <div class="space-y-6">
                @foreach($plans as $plan)
                    <div class="glass-card rounded-2xl p-6 relative overflow-hidden border border-white/10 space-y-5"
                         x-data="{
                            isLifetime: {{ is_null($plan->duration_days) ? 'true' : 'false' }},
                            isUnlimited: {{ is_null($plan->daily_download_limit) ? 'true' : 'false' }}
                         }">
                        
                        <!-- Card Header -->
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/5 pb-4">
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded bg-brand-500/20 text-brand-400 border border-brand-500/30">
                                    {{ $plan->code }}
                                </span>
                                <h3 class="text-base font-extrabold text-white">{{ $plan->name }}</h3>
                            </div>

                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" 
                                           name="plans[{{ $plan->id }}][is_featured]" 
                                           value="1" 
                                           {{ $plan->is_featured ? 'checked' : '' }}
                                           class="w-4 h-4 rounded bg-dark-850 text-brand-600 focus:ring-brand-500">
                                    <span class="text-xs font-semibold text-amber-300">⭐ Paling Populer (Featured)</span>
                                </label>

                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" 
                                           name="plans[{{ $plan->id }}][is_active]" 
                                           value="1" 
                                           {{ $plan->is_active ? 'checked' : '' }}
                                           class="w-4 h-4 rounded bg-dark-850 text-brand-600 focus:ring-brand-500">
                                    <span class="text-xs font-semibold text-emerald-400">Paket Aktif</span>
                                </label>
                            </div>
                        </div>

                        <!-- 3 Column Form Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            
                            <!-- 1. Nama & Harga Paket -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Nama Paket</label>
                                    <input type="text" 
                                           name="plans[{{ $plan->id }}][name]" 
                                           value="{{ $plan->name }}" 
                                           required
                                           class="w-full bg-dark-850 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Harga Paket (IDR)</label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-semibold">Rp</span>
                                        <input type="number" 
                                               name="plans[{{ $plan->id }}][price]" 
                                               value="{{ $plan->price }}" 
                                               required
                                               min="0"
                                               class="w-full bg-dark-850 border border-white/10 rounded-xl pl-9 pr-3.5 py-2.5 text-xs text-white font-bold focus:outline-none focus:ring-2 focus:ring-brand-500">
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Berapa Lama (Durasi) & Berapa Banyak (Kuota Foto Harian) -->
                            <div class="space-y-4">
                                <!-- Berapa Lama (Durasi Hari) -->
                                <div class="p-3.5 rounded-xl bg-dark-850 border border-white/5 space-y-2">
                                    <label class="block text-xs font-bold text-brand-400 flex items-center justify-between">
                                        <span>Berapa Lama (Masa Aktif)</span>
                                        <span class="text-[11px] font-normal" x-text="isLifetime ? 'Akses Selamanya' : 'Terbatas Hari'"></span>
                                    </label>
                                    
                                    <div class="flex items-center gap-2">
                                        <input type="number" 
                                               name="plans[{{ $plan->id }}][duration_days]" 
                                               value="{{ $plan->duration_days }}" 
                                               :disabled="isLifetime"
                                               :placeholder="isLifetime ? 'Akses Selamanya (Lifetime)' : 'Misal: 7 atau 30'"
                                               min="1"
                                               class="flex-1 bg-dark-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500 disabled:opacity-40 disabled:cursor-not-allowed">
                                        <span class="text-xs text-slate-400" x-show="!isLifetime">Hari</span>
                                    </div>

                                    <label class="flex items-center gap-2 cursor-pointer pt-1">
                                        <input type="checkbox" 
                                               name="plans[{{ $plan->id }}][is_lifetime]" 
                                               value="1" 
                                               x-model="isLifetime"
                                               class="w-4 h-4 rounded bg-dark-850 text-brand-600 focus:ring-brand-500">
                                        <span class="text-[11px] font-semibold text-accent-purple">Paket Lifetime (Akses Selamanya)</span>
                                    </label>
                                </div>

                                <!-- Berapa Banyak (Batas Kuota Foto Harian) -->
                                <div class="p-3.5 rounded-xl bg-dark-850 border border-white/5 space-y-2">
                                    <label class="block text-xs font-bold text-emerald-400 flex items-center justify-between">
                                        <span>Berapa Banyak (Kuota Harian)</span>
                                        <span class="text-[11px] font-normal" x-text="isUnlimited ? 'Tanpa Batas' : 'Dibatasi'"></span>
                                    </label>
                                    
                                    <div class="flex items-center gap-2">
                                        <input type="number" 
                                               name="plans[{{ $plan->id }}][daily_download_limit]" 
                                               value="{{ $plan->daily_download_limit }}" 
                                               :disabled="isUnlimited"
                                               :placeholder="isUnlimited ? 'Unlimited (Tanpa Batas)' : 'Misal: 50'"
                                               min="1"
                                               class="flex-1 bg-dark-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500 disabled:opacity-40 disabled:cursor-not-allowed">
                                        <span class="text-xs text-slate-400" x-show="!isUnlimited">Foto/hari</span>
                                    </div>

                                    <label class="flex items-center gap-2 cursor-pointer pt-1">
                                        <input type="checkbox" 
                                               name="plans[{{ $plan->id }}][is_unlimited]" 
                                               value="1" 
                                               x-model="isUnlimited"
                                               class="w-4 h-4 rounded bg-dark-850 text-brand-600 focus:ring-brand-500">
                                        <span class="text-[11px] font-semibold text-emerald-400">Download Tanpa Batas (Unlimited)</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 3. Daftar Fitur Unggulan (Apa saja) -->
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Daftar Fitur (1 per baris)</label>
                                <textarea name="plans[{{ $plan->id }}][features]" 
                                          rows="6" 
                                          class="w-full bg-dark-850 border border-white/10 rounded-xl p-3 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                                          placeholder="Batas 50 download foto/video per hari&#10;Kualitas Full HD original&#10;Masa aktif 7 hari">{{ is_array($plan->features) ? implode("\n", $plan->features) : '' }}</textarea>
                                <p class="text-[10px] text-slate-400">Teks ini akan muncul sebagai poin keunggulan pada kartu checkout.</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Global Free Quota Card -->
            <div class="glass-card rounded-2xl p-6 border border-white/5 space-y-4">
                <div class="border-b border-white/5 pb-3">
                    <h3 class="text-sm font-bold text-white">Batas Kuota Pengguna Gratis (Free Tier)</h3>
                    <p class="text-xs text-slate-400">Batas unduhan untuk pengguna yang belum membeli paket apa pun.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label for="free_daily_limit" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Kuota Tamu (Belum Login)</label>
                        <div class="relative">
                            <input type="number" 
                                   name="free_daily_limit" 
                                   id="free_daily_limit" 
                                   value="{{ \App\Models\Setting::get('free_daily_limit', 10) }}" 
                                   min="0"
                                   required
                                   class="w-full bg-dark-850 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white font-bold focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-medium">file / hari</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Kuota gratis untuk pengguna yang belum login.</p>
                    </div>

                    <div>
                        <label for="free_registered_daily_limit" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Kuota Member (Sudah Login)</label>
                        <div class="relative">
                            <input type="number" 
                                   name="free_registered_daily_limit" 
                                   id="free_registered_daily_limit" 
                                   value="{{ \App\Models\Setting::get('free_registered_daily_limit', 15) }}" 
                                   min="0"
                                   required
                                   class="w-full bg-dark-850 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white font-bold focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-medium">file / hari</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Bonus kuota (+5) otomatis aktif begitu user login.</p>
                    </div>

                    <div class="flex items-center justify-between p-3.5 rounded-xl bg-dark-850 border border-white/5">
                        <div>
                            <span class="text-xs font-bold text-white block">Izinkan Unduhan Gratis</span>
                            <span class="text-[11px] text-slate-400">Jika mati, hanya PRO yang bisa download.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer ml-3">
                            <input type="checkbox" 
                                   name="allow_free_download" 
                                   value="1" 
                                   {{ \App\Models\Setting::get('allow_free_download', true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-dark-750 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: General & Server Settings -->
        <div x-show="activeTab === 'general'" class="space-y-6" x-cloak>
            <div class="glass-card rounded-2xl p-6 sm:p-8 space-y-6">
                <div class="border-b border-white/5 pb-4">
                    <h2 class="text-base font-bold text-white tracking-tight">Pengaturan Umum &amp; Server</h2>
                    <p class="text-xs text-slate-400">Atur identitas aplikasi dan status operasional sistem.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="app_name" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Nama Aplikasi</label>
                        <input type="text" 
                               name="app_name" 
                               id="app_name" 
                               value="{{ \App\Models\Setting::get('app_name', 'Tele Downloader PRO') }}" 
                               required
                               class="w-full bg-dark-850 border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>

                    <div class="flex items-start justify-between p-4 rounded-xl bg-dark-850 border border-white/5">
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-white block">Mode Pemeliharaan (Maintenance)</span>
                            <p class="text-[11px] text-slate-400">Nonaktifkan akses sementara untuk perbaikan server.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                            <input type="checkbox" 
                                   name="maintenance_mode" 
                                   value="1" 
                                   {{ \App\Models\Setting::get('maintenance_mode', false) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-dark-750 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="maintenance_message" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Pesan Saat Mode Pemeliharaan</label>
                    <input type="text" 
                           name="maintenance_message" 
                           id="maintenance_message" 
                           value="{{ \App\Models\Setting::get('maintenance_message', 'Server sedang dalam pemeliharaan berkala.') }}" 
                           class="w-full bg-dark-850 border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
            </div>
        </div>

        <!-- TAB: Feature Toggles -->
        <div x-show="activeTab === 'features'" class="space-y-6" x-cloak>
            <div class="glass-card rounded-2xl p-6 sm:p-8 space-y-6">
                <div class="border-b border-white/5 pb-4">
                    <h2 class="text-base font-bold text-white tracking-tight">Pengaturan Fitur Aplikasi &amp; Ekstensi</h2>
                    <p class="text-xs text-slate-400">Kontrol fitur-fitur aktif pada ekstensi Chrome Tele Downloader.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    
                    <!-- Batch Download -->
                    <div class="flex items-start justify-between p-5 rounded-2xl bg-dark-850 border border-white/5 hover:border-brand-500/30 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-brand-500/10 text-brand-400 flex items-center justify-center shrink-0">
                                <i data-lucide="layers" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="text-sm font-bold text-white block">Multi-Select / Batch Download</span>
                                <p class="text-xs text-slate-400 mt-1">Mengizinkan pengguna PRO mencentang dan mengunduh puluhan file sekaligus dengan 1-klik.</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                            <input type="checkbox" 
                                   name="enable_batch_download" 
                                   value="1" 
                                   {{ \App\Models\Setting::get('enable_batch_download', true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-dark-750 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                        </label>
                    </div>

                    <!-- High Quality -->
                    <div class="flex items-start justify-between p-5 rounded-2xl bg-dark-850 border border-white/5 hover:border-brand-500/30 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center shrink-0">
                                <i data-lucide="sparkles" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="text-sm font-bold text-white block">Kualitas Asli Full HD (Highest Quality)</span>
                                <p class="text-xs text-slate-400 mt-1">Memberikan prioritas stream resolusi tertinggi asli tanpa kompresi pada pengguna PRO.</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                            <input type="checkbox" 
                                   name="enable_high_quality" 
                                   value="1" 
                                   {{ \App\Models\Setting::get('enable_high_quality', true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-dark-750 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>

                    <!-- Auto Retry -->
                    <div class="flex items-start justify-between p-5 rounded-2xl bg-dark-850 border border-white/5 hover:border-brand-500/30 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0">
                                <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="text-sm font-bold text-white block">Auto-Retry Download Gagal</span>
                                <p class="text-xs text-slate-400 mt-1">Otomatis menyambung unduhan jika jaringan terputus di tengah jalan.</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                            <input type="checkbox" 
                                   name="enable_auto_retry" 
                                   value="1" 
                                   {{ \App\Models\Setting::get('enable_auto_retry', true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-dark-750 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <!-- Video Preview -->
                    <div class="flex items-start justify-between p-5 rounded-2xl bg-dark-850 border border-white/5 hover:border-brand-500/30 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-accent-cyan/10 text-accent-cyan flex items-center justify-center shrink-0">
                                <i data-lucide="video" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="text-sm font-bold text-white block">Pratinjau Video &amp; Thumbnail</span>
                                <p class="text-xs text-slate-400 mt-1">Menampilkan thumbnail gambar/video sebelum menekan tombol unduh.</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                            <input type="checkbox" 
                                   name="enable_video_preview" 
                                   value="1" 
                                   {{ \App\Models\Setting::get('enable_video_preview', true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-dark-750 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent-cyan"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: Payment Gateway Settings -->
        <div x-show="activeTab === 'payment'" class="space-y-6" x-cloak>
            <div class="glass-card rounded-2xl p-6 sm:p-8 space-y-6">
                <div class="border-b border-white/5 pb-4">
                    <h2 class="text-base font-bold text-white tracking-tight">Payment Gateway &amp; Integrasi Transaksi</h2>
                    <p class="text-xs text-slate-400">Pilih penyedia payment gateway otomatis (QRIS, E-Wallet, VA) atau transfer manual.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="default_payment_gateway" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Payment Gateway Aktif</label>
                        <select name="default_payment_gateway" id="default_payment_gateway" class="w-full bg-dark-850 border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="tripay" {{ \App\Models\Setting::get('default_payment_gateway') === 'tripay' ? 'selected' : '' }}>Tripay (Sangat direkomendasikan - QRIS &amp; E-Wallet instan)</option>
                            <option value="midtrans" {{ \App\Models\Setting::get('default_payment_gateway') === 'midtrans' ? 'selected' : '' }}>Midtrans (Snap / Core API)</option>
                            <option value="bri" {{ \App\Models\Setting::get('default_payment_gateway') === 'bri' ? 'selected' : '' }}>BRI Direct API</option>
                            <option value="manual" {{ \App\Models\Setting::get('default_payment_gateway') === 'manual' ? 'selected' : '' }}>Manual Transfer / Approval Admin</option>
                        </select>
                    </div>

                    <div class="flex items-start justify-between p-4 rounded-xl bg-dark-850 border border-white/5">
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-white block">Mode Sandbox / Testing</span>
                            <p class="text-[11px] text-slate-400">Gunakan lingkungan sandbox untuk simulasi pembayaran tanpa uang sungguhan.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                            <input type="checkbox" 
                                   name="payment_sandbox_mode" 
                                   value="1" 
                                   {{ \App\Models\Setting::get('payment_sandbox_mode', true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-dark-750 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-white/5 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">Informasi Transfer Rekening Manual</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="manual_bank_name" class="block text-xs font-semibold text-slate-300 mb-1">Nama Bank / Penerima</label>
                            <input type="text" name="manual_bank_name" id="manual_bank_name" value="{{ \App\Models\Setting::get('manual_bank_name', 'BCA / Mandiri / BRI') }}" class="w-full bg-dark-850 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        </div>
                        <div>
                            <label for="manual_account_number" class="block text-xs font-semibold text-slate-300 mb-1">Nomor Rekening</label>
                            <input type="text" name="manual_account_number" id="manual_account_number" value="{{ \App\Models\Setting::get('manual_account_number', '123-456-7890 (A/N Tele Downloader)') }}" class="w-full bg-dark-850 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: Support & Announcement Settings -->
        <div x-show="activeTab === 'support'" class="space-y-6" x-cloak>
            <div class="glass-card rounded-2xl p-6 sm:p-8 space-y-6">
                <div class="border-b border-white/5 pb-4">
                    <h2 class="text-base font-bold text-white tracking-tight">Kontak Bantuan &amp; Banner Promosi</h2>
                    <p class="text-xs text-slate-400">Atur kontak layanan pelanggan dan pesan pengumuman/diskon yang muncul di ekstensi.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="support_telegram" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Telegram Support Channel / Username</label>
                        <input type="text" name="support_telegram" id="support_telegram" value="{{ \App\Models\Setting::get('support_telegram', '@TeleDownloaderSupport') }}" class="w-full bg-dark-850 border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label for="support_whatsapp" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">WhatsApp Customer Support</label>
                        <input type="text" name="support_whatsapp" id="support_whatsapp" value="{{ \App\Models\Setting::get('support_whatsapp', '+6281234567890') }}" class="w-full bg-dark-850 border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <div>
                    <label for="announcement_banner" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Pesan Banner Pengumuman</label>
                    <textarea name="announcement_banner" id="announcement_banner" rows="3" class="w-full bg-dark-850 border border-white/10 rounded-xl p-4 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">{{ \App\Models\Setting::get('announcement_banner', '🚀 PRO Promo: Dapatkan akses download tanpa batas selamanya dengan paket Lifetime!') }}</textarea>
                </div>

                <div class="flex items-start justify-between p-4 rounded-xl bg-dark-850 border border-white/5">
                    <div class="space-y-1">
                        <span class="text-xs font-bold text-white block">Tampilkan Banner Pengumuman</span>
                        <p class="text-[11px] text-slate-400">Aktifkan untuk menampilkan banner info/promo di bagian atas ekstensi Chrome.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                        <input type="checkbox" 
                               name="show_announcement" 
                               value="1" 
                               {{ \App\Models\Setting::get('show_announcement', true) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-dark-750 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Global Save Button Bar -->
        <div class="glass-panel rounded-2xl p-5 flex items-center justify-between sticky bottom-4 z-20">
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
                <span>Perubahan paket, kuota foto harian, dan fitur langsung aktif ke seluruh user.</span>
            </div>
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-brand-600 via-indigo-600 to-purple-600 hover:from-brand-500 hover:to-purple-500 text-white font-bold rounded-xl shadow-xl shadow-brand-600/30 flex items-center gap-2 text-xs transition">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>Simpan Semua Pengaturan &amp; Paket</span>
            </button>
        </div>
    </form>
</div>
@endsection
