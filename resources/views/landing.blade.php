<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} — Unduh Media Telegram Web Kualitas Full HD Tanpa Batas</title>
    <meta name="description" content="Ekstensi Chrome terbaik untuk mengunduh foto, video, album, dan media Telegram Web secara cepat, tanpa batas, dan berkualitas original.">

    @if(Auth::check())
    <meta name="tld-auth-user" content="{{ json_encode([
        'token' => Auth::user()->createToken('landing-web-sync')->plainTextToken,
        'user' => [
            'id' => Auth::user()->id,
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
            'is_pro' => Auth::user()->isPro(),
            'plan' => Auth::user()->activeSubscription?->plan?->code ?? (Auth::user()->role === 'admin' ? 'lifetime' : 'free'),
            'plan_name' => Auth::user()->activeSubscription?->plan?->name ?? (Auth::user()->role === 'admin' ? 'PRO Administrator' : 'Free Member')
        ]
    ]) }}">
    @else
    <meta name="tld-auth-guest" content="1">
    @endif

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        dark: {
                            950: '#070a12',
                            900: '#0b0f19',
                            850: '#111726',
                            800: '#161f36',
                            750: '#1c2846',
                        },
                        brand: {
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        accent: {
                            cyan: '#06b6d4',
                            purple: '#a855f7',
                            gold: '#f59e0b',
                        }
                    },
                    animation: {
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' },
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        // Init theme immediately to avoid flash
        if (localStorage.theme === 'light' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: light)').matches)) {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>

    <style>
        /* Smooth transitions for theme toggle */
        html.dark body {
            background-color: #070a12;
            color: #f1f5f9;
        }
        body {
            background-color: #f8fafc;
            color: #0f172a;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Dark / Light Glassmorphism utilities */
        .dark .glass-nav {
            background: rgba(7, 10, 18, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
        }
        .glass-nav {
            background: rgba(255, 255, 255, 0.85);
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            backdrop-filter: blur(20px);
        }

        .dark .glass-card {
            background: rgba(17, 23, 38, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            backdrop-filter: blur(16px);
        }

        .dark .glass-hero-badge {
            background: rgba(37, 99, 235, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #93c5fd;
        }
        .glass-hero-badge {
            background: rgba(239, 246, 255, 0.9);
            border: 1px solid rgba(191, 219, 254, 0.9);
            color: #1d4ed8;
        }
    </style>
</head>
<body class="antialiased overflow-x-hidden selection:bg-brand-500 selection:text-white" 
      x-data="{
          darkMode: document.documentElement.classList.contains('dark'),
          toggleTheme() {
              this.darkMode = !this.darkMode;
              if (this.darkMode) {
                  document.documentElement.classList.add('dark');
                  localStorage.theme = 'dark';
              } else {
                  document.documentElement.classList.remove('dark');
                  localStorage.theme = 'light';
              }
          },
          mobileMenuOpen: false
      }">

    <!-- Ambient Glowing Orbs Background -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[800px] h-[450px] bg-brand-600/20 dark:bg-brand-600/15 blur-[140px] rounded-full pointer-events-none -z-10 animate-pulse-slow"></div>
    <div class="fixed top-1/3 right-0 w-[500px] h-[500px] bg-purple-600/15 dark:bg-purple-600/10 blur-[150px] rounded-full pointer-events-none -z-10"></div>
    <div class="fixed bottom-10 left-0 w-[600px] h-[400px] bg-cyan-500/15 dark:bg-cyan-500/10 blur-[150px] rounded-full pointer-events-none -z-10"></div>

    <!-- ── Optional Announcement Bar ── -->
    @if($announcement)
        <div class="bg-gradient-to-r from-brand-600 via-indigo-600 to-purple-600 text-white text-xs font-semibold py-2 px-4 text-center flex items-center justify-center gap-2 shadow-md">
            <span>🚀</span>
            <span>{{ $announcement }}</span>
            <a href="#pricing" class="underline font-bold hover:text-amber-200 transition ml-2">Lihat Promo ↗</a>
        </div>
    @endif

    <!-- ── Navbar ── -->
    <header class="glass-nav sticky top-0 z-50 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            
            <!-- Logo -->
            <a href="/" class="flex items-center gap-3 group">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-brand-600 via-indigo-600 to-purple-600 flex items-center justify-center shadow-lg shadow-brand-500/25 text-white ring-2 ring-brand-500/20 group-hover:scale-105 transition transform">
                    <i data-lucide="download" class="w-6 h-6 stroke-[2.5]"></i>
                </div>
                <div>
                    <span class="text-lg font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-1.5">
                        {{ $appName }}
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase bg-gradient-to-r from-amber-500 to-amber-600 text-white">PRO</span>
                    </span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 block -mt-0.5 font-medium">Telegram Web Downloader</span>
                </div>
            </a>

            <!-- Desktop Navigation Links -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600 dark:text-slate-300">
                <a href="#fitur" class="hover:text-brand-600 dark:hover:text-brand-400 transition">Fitur Utama</a>
                <a href="#cara-kerja" class="hover:text-brand-600 dark:hover:text-brand-400 transition">Cara Kerja</a>
                <a href="#pricing" class="hover:text-brand-600 dark:hover:text-brand-400 transition">Paket Harga</a>
                <a href="#faq" class="hover:text-brand-600 dark:hover:text-brand-400 transition">FAQ</a>
            </nav>

            <!-- Actions: Theme Toggle & Auth Buttons -->
            <div class="hidden md:flex items-center gap-3.5">
                
                <!-- Dark / Light Mode Toggle Button -->
                <button @click="toggleTheme()" 
                        class="p-2.5 rounded-xl border border-slate-200 dark:border-white/10 bg-slate-100 dark:bg-dark-850 text-slate-700 dark:text-slate-300 hover:text-brand-600 dark:hover:text-brand-400 transition"
                        :title="darkMode ? 'Beralih ke Light Mode' : 'Beralih ke Dark Mode'">
                    <i x-show="darkMode" data-lucide="sun" class="w-4 h-4 text-amber-400"></i>
                    <i x-show="!darkMode" data-lucide="moon" class="w-4 h-4 text-indigo-600"></i>
                </button>

                @auth
                    @if(Auth::user()->role === 'admin')
                        <a href="{{ route('admin.dashboard') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-purple-600 hover:bg-purple-500 text-white shadow-lg shadow-purple-600/30 transition flex items-center gap-1.5">
                            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                            <span>Admin Dashboard</span>
                        </a>
                    @else
                        <a href="{{ route('member.dashboard') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-brand-600 hover:bg-brand-500 text-white shadow-lg shadow-brand-600/30 transition flex items-center gap-1.5">
                            <i data-lucide="user" class="w-4 h-4"></i>
                            <span>Portal Akun ({{ Auth::user()->name }})</span>
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-dark-800 border border-slate-200 dark:border-white/10 transition">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white shadow-lg shadow-brand-600/30 hover:scale-[1.02] active:scale-[0.98] transition transform flex items-center gap-1.5">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>Daftar Gratis</span>
                    </a>
                @endauth
            </div>

            <!-- Mobile Menu Trigger -->
            <div class="flex items-center gap-2 md:hidden">
                <button @click="toggleTheme()" class="p-2 rounded-xl border border-slate-200 dark:border-white/10 bg-slate-100 dark:bg-dark-850 text-slate-700 dark:text-slate-300">
                    <i x-show="darkMode" data-lucide="sun" class="w-4 h-4 text-amber-400"></i>
                    <i x-show="!darkMode" data-lucide="moon" class="w-4 h-4 text-indigo-600"></i>
                </button>
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 rounded-xl text-slate-700 dark:text-slate-200">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Dropdown -->
        <div x-show="mobileMenuOpen" x-cloak class="md:hidden glass-card border-b border-slate-200 dark:border-white/10 px-6 py-5 space-y-4">
            <nav class="flex flex-col gap-3 text-sm font-semibold text-slate-700 dark:text-slate-300">
                <a href="#fitur" @click="mobileMenuOpen = false" class="py-1">Fitur Utama</a>
                <a href="#cara-kerja" @click="mobileMenuOpen = false" class="py-1">Cara Kerja</a>
                <a href="#pricing" @click="mobileMenuOpen = false" class="py-1">Paket Harga</a>
                <a href="#faq" @click="mobileMenuOpen = false" class="py-1">FAQ</a>
            </nav>
            <div class="pt-4 border-t border-slate-200 dark:border-white/10 flex flex-col gap-2.5">
                @auth
                    <a href="{{ Auth::user()->role === 'admin' ? route('admin.dashboard') : route('member.dashboard') }}" class="w-full py-2.5 text-center text-xs font-bold rounded-xl bg-brand-600 text-white">
                        Buka Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="w-full py-2.5 text-center text-xs font-bold rounded-xl border border-slate-200 dark:border-white/10 text-slate-800 dark:text-white">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="w-full py-2.5 text-center text-xs font-bold rounded-xl bg-brand-600 text-white">
                        Daftar Akun Baru
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- ── Hero Section ── -->
    <section class="relative pt-12 pb-20 lg:pt-20 lg:pb-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-4xl mx-auto space-y-6">
                
                <!-- Pill Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold glass-hero-badge shadow-sm">
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
                    </span>
                    <span>Ekstensi Chrome #1 Pengunduh Telegram Web • Versi 1.2.0</span>
                </div>

                <!-- Main Heading -->
                <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black text-slate-900 dark:text-white tracking-tight leading-[1.1]">
                    Download Media Telegram Web Sekali Klik —
                    <span class="bg-gradient-to-r from-brand-600 via-indigo-500 to-purple-600 bg-clip-text text-transparent">
                        Kualitas Asli & Bebas Batas
                    </span>
                </h1>

                <!-- Subtitle -->
                <p class="text-base sm:text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto font-normal leading-relaxed">
                    Simpan foto, video, album, dan dokumen dari grup, channel, atau story Telegram Web secara instan. Tanpa perlu bot, tanpa kompresi, dan terintegrasi otomatis ke akun Anda.
                </p>

                <!-- Dual CTA Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                    <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-brand-600 via-indigo-600 to-purple-600 hover:from-brand-500 hover:to-indigo-500 text-white font-extrabold text-sm shadow-xl shadow-brand-600/30 hover:scale-105 active:scale-95 transition transform flex items-center justify-center gap-2">
                        <i data-lucide="download-cloud" class="w-5 h-5"></i>
                        <span>Mulai Unduh Gratis Sekarang</span>
                    </a>
                    <a href="#pricing" class="w-full sm:w-auto px-8 py-4 rounded-2xl border border-slate-300 dark:border-white/10 bg-white/60 dark:bg-dark-850/80 hover:bg-white dark:hover:bg-dark-800 text-slate-800 dark:text-white font-bold text-sm shadow-sm transition flex items-center justify-center gap-2">
                        <i data-lucide="crown" class="w-5 h-5 text-amber-500"></i>
                        <span>Lihat Paket Langganan</span>
                    </a>
                </div>

                <!-- Trust Metrics -->
                <div class="pt-8 flex flex-wrap items-center justify-center gap-6 text-xs font-semibold text-slate-500 dark:text-slate-400">
                    <div class="flex items-center gap-2">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500"></i>
                        <span>50.000+ File Terunduh</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-4 h-4 text-brand-500"></i>
                        <span>100% Aman &amp; Privat</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="zap" class="w-4 h-4 text-amber-500"></i>
                        <span>Batch 1-Klik Super Cepat</span>
                    </div>
                </div>

            </div>

            <!-- ── Interactive App Mockup Preview ── -->
            <div class="mt-16 relative max-w-5xl mx-auto animate-float">
                <div class="glass-card rounded-3xl p-3 sm:p-5 shadow-2xl border border-slate-200 dark:border-white/10">
                    
                    <!-- Window Header -->
                    <div class="flex items-center justify-between px-3 py-2 border-b border-slate-200 dark:border-white/10 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-dark-900 px-4 py-1 rounded-lg">
                            web.telegram.org • Tele Downloader PRO Active
                        </div>
                        <div class="w-12"></div>
                    </div>

                    <!-- Inner Mockup Display -->
                    <div class="bg-slate-100 dark:bg-dark-900 rounded-2xl p-6 sm:p-8 grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                        
                        <!-- Left: Feature Teaser -->
                        <div class="space-y-4 md:col-span-1">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold text-xs">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Pendeteksian Otomatis</span>
                            </div>
                            <h3 class="text-xl font-extrabold text-slate-900 dark:text-white">Otomatis Mendeteksi Semua Media di Chat</h3>
                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                Cukup buka chat Telegram Web Anda, ekstensi akan secara pintar menangkap thumbnail, video stream, dan metadata foto resolusi tinggi.
                            </p>
                            <div class="flex items-center gap-3 text-xs font-bold text-brand-600 dark:text-brand-400">
                                <span>⚡ Kecepatan Penuh 100Mbps</span>
                            </div>
                        </div>

                        <!-- Right: Media Cards Simulator -->
                        <div class="md:col-span-2 space-y-3">
                            
                            <!-- Simulated Item 1 -->
                            <div class="p-3.5 rounded-2xl bg-white dark:bg-dark-850 border border-slate-200 dark:border-white/10 shadow-sm flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-600 flex items-center justify-center text-white font-bold text-xs shadow-md">
                                        FOTO
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-900 dark:text-white">Wallpaper_4K_Landscape_Ultra.jpg</div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400">3840x2160 • 4.8 MB • Full Original Quality</div>
                                    </div>
                                </div>
                                <span class="px-3 py-1.5 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-bold text-xs flex items-center gap-1">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i> Terunduh
                                </span>
                            </div>

                            <!-- Simulated Item 2 -->
                            <div class="p-3.5 rounded-2xl bg-white dark:bg-dark-850 border border-slate-200 dark:border-white/10 shadow-sm flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center text-white font-bold text-xs shadow-md">
                                        VIDEO
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-900 dark:text-white">Drone_Cinema_Tour_Bali_1080p.mp4</div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400">1920x1080 • 85.2 MB • 60 FPS Stream</div>
                                    </div>
                                </div>
                                <button class="px-3.5 py-1.5 rounded-xl bg-brand-600 text-white font-bold text-xs shadow-md shadow-brand-600/30 flex items-center gap-1">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i> Unduh
                                </button>
                            </div>

                            <!-- Batch Toolbar Simulator -->
                            <div class="p-3 rounded-2xl bg-brand-50 dark:bg-dark-800 border border-brand-200 dark:border-brand-500/20 flex items-center justify-between text-xs">
                                <span class="font-bold text-brand-700 dark:text-brand-300">✨ 14 Media Lainnya Siap Diunduh Sekaligus</span>
                                <span class="font-extrabold text-brand-600 dark:text-brand-400">Unduh Semua (16) ↗</span>
                            </div>

                        </div>

                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ── Feature Grid Section ── -->
    <section id="fitur" class="py-20 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                <h2 class="text-xs font-extrabold uppercase tracking-widest text-brand-600 dark:text-brand-400">Fitur Unggulan</h2>
                <p class="text-3xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight">
                    Didesain Khusus untuk Kecepatan & Kemudahan Anda
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Nikmati pengalaman mengunduh media dari Telegram Web tanpa hambatan teknis.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Feature 1 -->
                <div class="glass-card rounded-3xl p-8 space-y-4 hover:border-brand-500/50 transition">
                    <div class="w-14 h-14 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <i data-lucide="layers" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Batch Download 1-Klik</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Unduh puluhan foto dan video dari album atau chat secara bersamaan hanya dengan menekan satu tombol. Hemat waktu Anda hingga 90%.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="glass-card rounded-3xl p-8 space-y-4 hover:border-brand-500/50 transition">
                    <div class="w-14 h-14 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <i data-lucide="unlock" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Bypass Restriksi Media</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Mendukung pengunduhan konten dari channel atau grup privat yang menonaktifkan fitur "Simpan Media" / "Restricted Content".
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="glass-card rounded-3xl p-8 space-y-4 hover:border-brand-500/50 transition">
                    <div class="w-14 h-14 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <i data-lucide="sparkles" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Kualitas 100% Full HD Asli</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        File diunduh dalam bitrate dan resolusi asli tanpa kompresi tambahan sehingga hasil gambar dan video tetap jernih dan tajam.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="glass-card rounded-3xl p-8 space-y-4 hover:border-brand-500/50 transition">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <i data-lucide="user-check" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Direct Subscription</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Beli paket langganan dan akun Anda langsung aktif tanpa perlu menyalin kode lisensi secara manual. Otomatis terhubung dengan ekstensi.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="glass-card rounded-3xl p-8 space-y-4 hover:border-brand-500/50 transition">
                    <div class="w-14 h-14 rounded-2xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                        <i data-lucide="shield" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">100% Privasi Terjamin</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Ekstensi bekerja langsung di browser Anda tanpa perantara server pihak ketiga untuk pembacaan pesan teks chat Telegram Anda.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="glass-card rounded-3xl p-8 space-y-4 hover:border-brand-500/50 transition">
                    <div class="w-14 h-14 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <i data-lucide="refresh-cw" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Auto Reset Kuota Harian</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Kuota harian Anda otomatis terisi kembali penuh setiap pukul 00:00. Tidak perlu khawatir kehabisan jatah download esok hari.
                    </p>
                </div>

            </div>

        </div>
    </section>

    <!-- ── How It Works Section ── -->
    <section id="cara-kerja" class="py-20 bg-slate-100/60 dark:bg-dark-900/60 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                <h2 class="text-xs font-extrabold uppercase tracking-widest text-brand-600 dark:text-brand-400">Mudah &amp; Praktis</h2>
                <p class="text-3xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight">
                    Mulai Unduh dalam 3 Langkah Sederhana
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                
                <!-- Step 1 -->
                <div class="glass-card rounded-3xl p-8 text-center space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-brand-600 text-white font-black text-lg flex items-center justify-center mx-auto shadow-lg shadow-brand-600/30">
                        1
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Buka Telegram Web</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Akses akun Telegram Anda di browser Chrome (`web.telegram.org`) dan buka grup/channel favorit Anda.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="glass-card rounded-3xl p-8 text-center space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white font-black text-lg flex items-center justify-center mx-auto shadow-lg shadow-indigo-600/30">
                        2
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Buka Popup Ekstensi</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Klik ikon Tele Downloader di toolbar Chrome Anda. Semua foto dan video yang termuat akan langsung muncul otomatis.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="glass-card rounded-3xl p-8 text-center space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-purple-600 text-white font-black text-lg flex items-center justify-center mx-auto shadow-lg shadow-purple-600/30">
                        3
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Klik &amp; Selesai!</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Pilih foto/video yang Anda inginkan atau tekan tombol **"Unduh Semua"** untuk menyimpan seluruh media ke komputer Anda.
                    </p>
                </div>

            </div>

        </div>
    </section>

    <!-- ── Pricing Plans Section ── -->
    <section id="pricing" class="py-24 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                <h2 class="text-xs font-extrabold uppercase tracking-widest text-brand-600 dark:text-brand-400">Pilihan Paket Langganan</h2>
                <p class="text-3xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight">
                    Harga Terjangkau, Fitur Maksimal
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Pilih paket yang sesuai dengan kebutuhan Anda. Tanpa komitmen terikat, aktif seketika.
                </p>
            </div>

            <!-- Dynamic Pricing Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
                @foreach($plans as $p)
                    <div class="glass-card rounded-3xl p-8 flex flex-col justify-between relative transition hover:scale-[1.02] duration-300 {{ $p->is_featured ? 'border-2 border-brand-500 shadow-2xl shadow-brand-500/20' : '' }}">
                        
                        @if($p->is_featured)
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gradient-to-r from-amber-500 to-amber-600 text-white text-[11px] font-black uppercase tracking-wider py-1 px-4 rounded-full shadow-lg">
                                ⭐ Paling Populer
                            </div>
                        @endif

                        <div class="space-y-6">
                            <div>
                                <h3 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $p->name }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    {{ $p->duration_days ? 'Akses selama ' . $p->duration_days . ' hari' : 'Akses selamanya tanpa biaya bulanan' }}
                                </p>
                            </div>

                            <div class="flex items-baseline gap-1.5">
                                <span class="text-4xl font-black text-slate-900 dark:text-white">Rp {{ number_format($p->price, 0, ',', '.') }}</span>
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                    / {{ $p->duration_days ? $p->duration_days . ' Hari' : 'Sekali Bayar' }}
                                </span>
                            </div>

                            <!-- Quota Pill -->
                            <div>
                                @if($p->daily_download_limit)
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30 text-xs font-bold">
                                        <i data-lucide="image" class="w-3.5 h-3.5"></i>
                                        <span>Batas: {{ $p->daily_download_limit }} Foto / Hari</span>
                                    </div>
                                @else
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 text-xs font-bold">
                                        <i data-lucide="infinity" class="w-3.5 h-3.5"></i>
                                        <span>Download Unlimited (Tanpa Batas)</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Features List -->
                            <div class="pt-4 border-t border-slate-200 dark:border-white/10 space-y-3">
                                @if(is_array($p->features))
                                    @foreach($p->features as $f)
                                        <div class="flex items-start gap-2.5 text-xs text-slate-700 dark:text-slate-300">
                                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5"></i>
                                            <span>{{ $f }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <div class="pt-8 mt-6 border-t border-slate-200 dark:border-white/10">
                            <a href="{{ route('register') }}" class="w-full py-3.5 rounded-2xl text-xs font-bold text-center flex items-center justify-center gap-2 transition {{ $p->is_featured ? 'bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 text-white shadow-lg shadow-brand-600/30' : 'bg-slate-900 dark:bg-white hover:bg-slate-800 dark:hover:bg-slate-100 text-white dark:text-slate-900' }}">
                                <span>Pilih {{ $p->name }}</span>
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </a>
                        </div>

                    </div>
                @endforeach
            </div>

        </div>
    </section>

    <!-- ── FAQ Accordion Section ── -->
    <section id="faq" class="py-20 bg-slate-100/60 dark:bg-dark-900/60 transition-colors duration-300">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <div class="text-center space-y-4">
                <h2 class="text-xs font-extrabold uppercase tracking-widest text-brand-600 dark:text-brand-400">Tanya Jawab</h2>
                <p class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                    Pertanyaan yang Sering Diajukan
                </p>
            </div>

            <div class="space-y-4" x-data="{ activeAccordion: 1 }">
                
                <!-- Q1 -->
                <div class="glass-card rounded-2xl p-6 transition">
                    <button @click="activeAccordion = (activeAccordion === 1 ? null : 1)" class="w-full flex items-center justify-between text-left text-base font-bold text-slate-900 dark:text-white">
                        <span>Apakah Tele Downloader aman digunakan?</span>
                        <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="activeAccordion === 1 ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="activeAccordion === 1" x-cloak class="mt-3 text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Sangat aman. Ekstensi bekerja langsung di sisi browser Anda (client-side) tanpa menyimpan atau membaca kredensial akun Telegram Anda.
                    </div>
                </div>

                <!-- Q2 -->
                <div class="glass-card rounded-2xl p-6 transition">
                    <button @click="activeAccordion = (activeAccordion === 2 ? null : 2)" class="w-full flex items-center justify-between text-left text-base font-bold text-slate-900 dark:text-white">
                        <span>Bagaimana cara kerja kuota harian?</span>
                        <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="activeAccordion === 2 ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="activeAccordion === 2" x-cloak class="mt-3 text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Tiap pengguna gratis mendapatkan 10 file/hari, dan Paket Mingguan memiliki 50 file/hari. Kuota otomatis direset kembali penuh tiap pukul 00:00 WIB. Untuk Paket Bulanan dan Lifetime, Anda mendapatkan akses unduhan tanpa batas (Unlimited).
                    </div>
                </div>

                <!-- Q3 -->
                <div class="glass-card rounded-2xl p-6 transition">
                    <button @click="activeAccordion = (activeAccordion === 3 ? null : 3)" class="w-full flex items-center justify-between text-left text-base font-bold text-slate-900 dark:text-white">
                        <span>Apakah saya perlu memasukkan kode lisensi secara manual?</span>
                        <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="activeAccordion === 3 ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="activeAccordion === 3" x-cloak class="mt-3 text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Tidak perlu! Sistem kami menggunakan **Direct Subscription** berbasis akun. Begitu Anda mendaftar atau membeli paket, ekstensi Chrome Anda otomatis aktif berstatus PRO.
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- ── Bottom CTA Banner ── -->
    <section class="py-20 relative overflow-hidden">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="glass-card rounded-3xl p-10 sm:p-14 text-center space-y-6 relative overflow-hidden bg-gradient-to-r from-brand-600 via-indigo-600 to-purple-600 text-white shadow-2xl">
                
                <h2 class="text-3xl sm:text-5xl font-black tracking-tight leading-tight">
                    Siap Mengunduh Media Telegram Tanpa Batas?
                </h2>
                <p class="text-sm sm:text-base text-blue-100 max-w-xl mx-auto font-normal">
                    Daftar akun gratis Anda sekarang dan rasakan kemudahan mengunduh media sekali klik!
                </p>

                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-white text-slate-900 font-extrabold text-sm shadow-xl hover:scale-105 transition transform">
                        Daftar Akun Gratis Sekarang ↗
                    </a>
                    <a href="https://t.me/{{ str_replace('@', '', $supportTelegram) }}" target="_blank" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-black/25 hover:bg-black/40 text-white font-bold text-sm border border-white/20 transition">
                        Hubungi Support Telegram
                    </a>
                </div>

            </div>
        </div>
    </section>

    <!-- ── Footer ── -->
    <footer class="border-t border-slate-200 dark:border-white/10 py-12 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6 text-xs text-slate-500 dark:text-slate-400">
            <div class="flex items-center gap-2 font-bold text-slate-700 dark:text-slate-300">
                <span>{{ $appName }} &copy; {{ date('Y') }}</span>
                <span>•</span>
                <span>All rights reserved.</span>
            </div>

            <div class="flex items-center gap-6">
                <a href="{{ route('login') }}" class="hover:text-brand-500 transition font-semibold">Portal Web</a>
                <a href="{{ route('register') }}" class="hover:text-brand-500 transition font-semibold">Daftar Akun</a>
                <a href="{{ route('admin.login') }}" class="hover:text-brand-500 transition font-semibold">Admin Panel</a>
                <a href="https://t.me/{{ str_replace('@', '', $supportTelegram) }}" target="_blank" class="hover:text-brand-500 transition font-semibold">Telegram Support</a>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
