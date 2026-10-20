<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak | PT Kerinci Merangin Hidro</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .hero-pattern {
            background-color: #0d2810;
            background-image: 
                radial-gradient(circle at 15% 20%, rgba(27, 94, 32, 0.45) 0%, transparent 40%),
                radial-gradient(circle at 85% 80%, rgba(16, 185, 129, 0.15) 0%, transparent 45%),
                radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 28px 28px;
        }
    </style>
</head>
<body class="hero-pattern min-h-screen text-slate-100 flex items-center justify-center p-4 antialiased selection:bg-emerald-600 selection:text-white">

    <div class="max-w-lg w-full bg-slate-900/80 backdrop-blur-xl border border-white/10 rounded-3xl p-6 sm:p-10 shadow-2xl text-center relative overflow-hidden">
        
        <!-- Ambient Glow -->
        <div class="absolute -top-12 -left-12 w-48 h-48 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -right-12 w-48 h-48 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 space-y-6">
            
            <!-- Logo Brand -->
            <div class="flex justify-center">
                <a href="{{ route('home') }}" class="inline-block transition hover:opacity-90">
                    <img src="{{ asset('images/logo-kerinci.png') }}" 
                         alt="Kerinci Hydropower" 
                         class="h-10 sm:h-12 w-auto object-contain brightness-0 invert">
                </a>
            </div>

            <!-- Lock Icon Badge -->
            <div class="flex justify-center">
                <div class="w-20 h-20 rounded-3xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-3xl shadow-lg shadow-amber-500/5">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
            </div>

            <!-- Error Code & Heading -->
            <div class="space-y-2">
                <span class="inline-block px-3 py-1 text-xs font-black tracking-widest uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-full">
                    Error 403 &bull; Dibatasi Sistem
                </span>
                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                    Akses Tidak Diizinkan
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed max-w-sm mx-auto">
                    {{ $exception->getMessage() ?: 'Anda tidak memiliki wewenang atau hak akses yang cukup untuk membuka halaman internal ini.' }}
                </p>
            </div>

            <!-- Information Card -->
            <div class="bg-white/5 border border-white/10 rounded-2xl p-4 text-left flex items-start space-x-3 text-xs text-slate-300">
                <i class="fa-solid fa-circle-info text-amber-400 text-sm mt-0.5 flex-shrink-0"></i>
                <div>
                    <span class="font-bold text-white block mb-0.5">Mengapa saya melihat ini?</span>
                    Halaman ini dilindungi oleh sistem matriks hak akses (*Role-Based Access Control*). Jika Anda memerlukan akses operasional ini, silakan hubungi Administrator Sistem.
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                <a href="{{ route('home') }}" 
                   class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-xl text-xs sm:text-sm font-bold text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 shadow-sm transition">
                    <i class="fa-solid fa-house mr-2"></i>
                    <span>Ke Lobi Utama</span>
                </a>

                @auth
                    @php
                        $u = auth()->user();
                        $roleRoute = match(true) {
                            $u->isSuperAdmin() || $u->can('admin.dashboard') => route('admin.dashboard'),
                            $u->can('qma.vehicles.view') => route('qma.dashboard'),
                            $u->can('hse.employees.view') => route('hse.dashboard'),
                            $u->can('pg.guards.view') => route('pg.dashboard'),
                            $u->can('gate.inspect') => route('pg.gate.inspection'),
                            $u->can('employee.dashboard') => route('employee.dashboard'),
                            $u->can('employee.p2h') => route('p2h.index'),
                            $u->can('employee.timesheet') => route('timesheet.index'),
                            default => route('profile.index'),
                        };
                    @endphp
                    <a href="{{ $roleRoute }}" 
                       class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-xl text-xs sm:text-sm font-bold text-white bg-[#1b5e20] hover:bg-[#144918] shadow-lg shadow-emerald-900/30 transition">
                        <i class="fa-solid fa-gauge-high mr-2"></i>
                        <span>Buka Dashboard Saya</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" 
                       class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-xl text-xs sm:text-sm font-bold text-white bg-[#1b5e20] hover:bg-[#144918] shadow-lg shadow-emerald-900/30 transition">
                        <i class="fa-solid fa-right-to-bracket mr-2"></i>
                        <span>Login Akun</span>
                    </a>
                @endauth
            </div>

            <!-- Footer Small Note -->
            <div class="text-[11px] text-slate-500 pt-2 border-t border-white/5">
                Sistem RKM &bull; PT Kerinci Merangin Hidro
            </div>

        </div>

    </div>

</body>
</html>
