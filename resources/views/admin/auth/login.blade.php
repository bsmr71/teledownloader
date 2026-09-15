<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tele Downloader PRO</title>
    <meta name="tld-auth-guest" content="1">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                        },
                        brand: {
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-dark-950 text-slate-200 font-sans antialiased min-h-screen flex items-center justify-center p-4 relative overflow-hidden">

    <!-- Glowing Background Orbs -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-gradient-to-tr from-brand-600/20 via-indigo-600/15 to-purple-600/20 blur-[130px] rounded-full pointer-events-none"></div>
    <div class="absolute -bottom-20 -right-20 w-96 h-96 bg-cyan-500/10 blur-[120px] rounded-full pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        
        <!-- Logo & Branding -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-brand-600 via-indigo-500 to-purple-600 shadow-xl shadow-brand-500/30 mb-4 ring-1 ring-white/20">
                <i data-lucide="download" class="w-7 h-7 text-white stroke-[2.5]"></i>
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Tele Downloader PRO</h1>
            <p class="text-sm text-slate-400 mt-1">Admin Central &amp; Subscription Portal</p>
        </div>

        <!-- Login Card -->
        <div class="bg-dark-900/90 backdrop-blur-xl border border-white/10 rounded-2xl p-6 sm:p-8 shadow-2xl shadow-black/80">
            
            @if(session('error'))
                <div class="mb-6 p-4 rounded-xl bg-rose-950/70 border border-rose-500/40 text-rose-200 text-sm flex items-start gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-rose-400 shrink-0 mt-0.5"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-950/70 border border-emerald-500/40 text-emerald-200 text-sm flex items-start gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Email Admin</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                        </div>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               value="{{ old('email', 'bismar71@gmail.com') }}" 
                               required 
                               autocomplete="email"
                               placeholder="nama@domain.com" 
                               class="w-full bg-dark-850 border border-white/10 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition">
                    </div>
                    @error('email')
                        <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Input -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Password</label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </div>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               value="zabuaz71"
                               required 
                               autocomplete="current-password"
                               placeholder="••••••••" 
                               class="w-full bg-dark-850 border border-white/10 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition">
                    </div>
                    @error('password')
                        <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me Checkbox -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" value="1" checked class="w-4 h-4 rounded bg-dark-850 border-white/20 text-brand-600 focus:ring-brand-500 focus:ring-offset-dark-900">
                        <span class="text-xs text-slate-300">Ingat sesi saya</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-brand-600 via-indigo-600 to-purple-600 hover:from-brand-500 hover:to-purple-500 text-white font-bold rounded-xl shadow-lg shadow-brand-600/30 hover:shadow-brand-500/50 transition duration-200 flex items-center justify-center gap-2 text-sm">
                    <span>Masuk ke Admin Dashboard</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>

            <!-- Quick Autofill Note & Register Link -->
            <div class="mt-6 pt-5 border-t border-white/5 space-y-2 text-center">
                <p class="text-xs text-slate-300">
                    Kredensial Admin: <span class="text-brand-400 font-mono font-medium">bismar71@gmail.com</span> / <span class="text-brand-400 font-mono font-medium">zabuaz71</span>
                </p>
                <p class="text-xs text-slate-400">
                    Ingin mendaftar akun user? 
                    <a href="{{ route('register') }}" class="text-brand-400 hover:text-brand-300 font-semibold underline underline-offset-4 ml-1">
                        Daftar di sini
                    </a>
                </p>
            </div>
        </div>

        <div class="text-center mt-6 text-xs text-slate-400">
            &copy; {{ date('Y') }} Tele Downloader Backend. Secure Admin Area.
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
