<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') - Tele Downloader PRO</title>
    
    @if(Auth::check())
    <meta name="tld-auth-user" content="{{ json_encode([
        'token' => Auth::user()->createToken('admin-web-sync')->plainTextToken,
        'user' => [
            'id' => Auth::user()->id,
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
            'is_pro' => Auth::user()->isPro(),
            'plan' => Auth::user()->activeSubscription?->plan?->code ?? 'lifetime',
            'plan_name' => Auth::user()->activeSubscription?->plan?->name ?? 'PRO Administrator'
        ]
    ]) }}">
    @endif

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (Tailwind CDN + Custom Styles) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        dark: {
                            950: '#070a12',
                            900: '#0b0f19',
                            850: '#111726',
                            800: '#161f36',
                            750: '#1d2847',
                            700: '#26355d',
                        },
                        brand: {
                            50: '#f0f4ff',
                            100: '#dbe4fe',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        accent: {
                            cyan: '#06b6d4',
                            purple: '#a855f7',
                            emerald: '#10b981',
                            amber: '#f59e0b',
                            rose: '#f43f5e',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        [x-cloak] { display: none !important; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #0b0f19;
        }
        ::-webkit-scrollbar-thumb {
            background: #1d2847;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #3b82f6;
        }
        
        .glass-panel {
            background: rgba(17, 23, 38, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .glass-card {
            background: linear-gradient(135deg, rgba(22, 31, 54, 0.75) 0%, rgba(17, 23, 38, 0.9) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.07);
        }
        
        .glow-brand {
            box-shadow: 0 0 25px -5px rgba(59, 130, 246, 0.3);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-dark-950 text-slate-200 font-sans antialiased min-h-screen flex flex-col selection:bg-brand-500 selection:text-white" x-data="{ sidebarOpen: false, searchOpen: false }">

    <!-- Global Toast Alerts -->
    <div x-data="{ 
        toasts: [],
        add(msg, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, msg, type });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }" 
    x-init="
        @if(session('success')) add('{{ addslashes(session('success')) }}', 'success'); @endif
        @if(session('error')) add('{{ addslashes(session('error')) }}', 'error'); @endif
        @if(session('info')) add('{{ addslashes(session('info')) }}', 'info'); @endif
        @if($errors->any()) add('{{ addslashes($errors->first()) }}', 'error'); @endif
    "
    class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-md w-full pointer-events-none px-4 sm:px-0">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="true" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                 class="pointer-events-auto p-4 rounded-xl shadow-2xl border flex items-start gap-3 glass-panel"
                 :class="{
                     'border-emerald-500/40 bg-emerald-950/80 text-emerald-100': toast.type === 'success',
                     'border-rose-500/40 bg-rose-950/80 text-rose-100': toast.type === 'error',
                     'border-brand-500/40 bg-brand-950/80 text-blue-100': toast.type === 'info'
                 }">
                <div class="mt-0.5 shrink-0">
                    <template x-if="toast.type === 'success'"><i data-lucide="check-circle" class="w-5 h-5 text-emerald-400"></i></template>
                    <template x-if="toast.type === 'error'"><i data-lucide="alert-circle" class="w-5 h-5 text-rose-400"></i></template>
                    <template x-if="toast.type === 'info'"><i data-lucide="info" class="w-5 h-5 text-brand-400"></i></template>
                </div>
                <div class="flex-1 text-sm font-medium leading-snug" x-text="toast.msg"></div>
                <button @click="remove(toast.id)" class="text-slate-400 hover:text-white transition">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </template>
    </div>

    <div class="flex h-screen overflow-hidden">
        
        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-black/80 backdrop-blur-sm lg:hidden"
             x-cloak></div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 w-72 bg-dark-900 border-r border-white/5 flex flex-col transition-transform duration-300 ease-in-out lg:static lg:translate-x-0">
            
            <!-- Sidebar Header -->
            <div class="h-20 flex items-center justify-between px-6 border-b border-white/5 bg-dark-950/40">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 via-indigo-500 to-accent-purple flex items-center justify-center shadow-lg shadow-brand-500/25 group-hover:scale-105 transition-transform duration-300">
                        <i data-lucide="download" class="w-5 h-5 text-white stroke-[2.5]"></i>
                    </div>
                    <div>
                        <div class="font-extrabold text-base tracking-tight text-white flex items-center gap-1.5">
                            Tele Downloader
                            <span class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-brand-500/20 text-brand-400 border border-brand-500/30 uppercase tracking-widest">PRO</span>
                        </div>
                        <p class="text-xs text-slate-400 font-medium">Admin Central</p>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white p-1 rounded-lg hover:bg-white/5">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto px-4 py-6 space-y-1.5">
                <div class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-300">Menu Utama</div>

                <a href="{{ route('admin.dashboard') }}" 
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('admin.users.index') }}" 
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('admin.users.*') ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                    <i data-lucide="users" class="w-4 h-4 {{ request()->routeIs('admin.users.*') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span>Kelola Pengguna</span>
                </a>

                <a href="{{ route('admin.subscriptions.index') }}" 
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('admin.subscriptions.*') ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                    <i data-lucide="badge-check" class="w-4 h-4 {{ request()->routeIs('admin.subscriptions.*') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span class="flex-1">Langganan User</span>
                    <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Direct</span>
                </a>

                <a href="{{ route('admin.transactions.index') }}" 
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('admin.transactions.*') ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                    <i data-lucide="credit-card" class="w-4 h-4 {{ request()->routeIs('admin.transactions.*') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span>Transaksi</span>
                </a>

                <div class="pt-5 px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-300">Konfigurasi</div>

                <a href="{{ route('admin.plans.index') }}" 
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('admin.plans.*') ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                    <i data-lucide="layers" class="w-4 h-4 {{ request()->routeIs('admin.plans.*') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span>Paket Harga</span>
                </a>

                <a href="{{ route('admin.downloads.index') }}" 
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('admin.downloads.*') ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                    <i data-lucide="bar-chart-2" class="w-4 h-4 {{ request()->routeIs('admin.downloads.*') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span>Log Download</span>
                </a>

                <a href="{{ route('admin.settings.index') }}" 
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('admin.settings.*') ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                    <i data-lucide="sliders" class="w-4 h-4 {{ request()->routeIs('admin.settings.*') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span class="flex-1">Pengaturan Fitur</span>
                    <span class="w-2 h-2 rounded-full bg-accent-cyan animate-pulse"></span>
                </a>
            </div>

            <!-- Sidebar Footer (Admin User Info & Logout) -->
            <div class="p-4 border-t border-white/5 bg-dark-950/60">
                <div class="flex items-center justify-between p-2 rounded-xl bg-dark-850 border border-white/5">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-tr from-brand-500 to-accent-purple flex items-center justify-center font-bold text-white text-sm shrink-0">
                            {{ substr(auth()->user()->name ?? 'Admin', 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name ?? 'Administrator' }}</p>
                            <p class="text-[11px] text-slate-400 truncate">{{ auth()->user()->email ?? 'admin@teledownloader.com' }}</p>
                        </div>
                    </div>
                    <form action="{{ route('admin.logout') }}" method="POST" class="shrink-0">
                        @csrf
                        <button type="submit" title="Keluar" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-dark-950">
            
            <!-- Topbar -->
            <header class="h-20 bg-dark-900/80 backdrop-blur-md border-b border-white/5 flex items-center justify-between px-4 sm:px-8 z-30 shrink-0">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-xl text-slate-400 hover:text-white hover:bg-white/5">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-white tracking-tight">@yield('header_title', 'Dashboard')</h1>
                        <p class="text-xs text-slate-400 hidden sm:block">@yield('header_subtitle', 'Pantau performa, langganan user, dan pengaturan sistem.')</p>
                    </div>
                </div>

                <!-- Topbar Actions -->
                <div class="flex items-center gap-3">
                    <a href="/api/v1/config" target="_blank" class="hidden md:flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-400 hover:text-brand-400 bg-white/5 hover:bg-brand-500/10 border border-white/5 transition">
                        <i data-lucide="code" class="w-3.5 h-3.5"></i>
                        <span>API Config</span>
                    </a>
                    
                    <div class="flex items-center gap-2 pl-2 sm:border-l sm:border-white/10">
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span class="text-xs font-medium text-slate-300 hidden sm:inline">MySQL Online</span>
                    </div>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 space-y-8">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Initialize Lucide Icons -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
        document.addEventListener('alpine:initialized', () => {
            lucide.createIcons();
        });
    </script>
    @stack('scripts')
</body>
</html>
