<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Sistem RKM - PT Kerinci Merangin Hidro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }

        /* Panel kiri: pola garis diagonal halus di atas gradasi */
        .brand-panel {
            background: linear-gradient(160deg, #1a3a1a 0%, #1b5e20 60%, #256127 100%);
            position: relative;
            overflow: hidden;
        }

        /* Pola grid titik-titik elegan di background */
        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,0.07) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        /* Cahaya sorot lembut dari tengah atas */
        .brand-panel::after {
            content: '';
            position: absolute;
            top: -80px;
            left: 50%;
            transform: translateX(-50%);
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(255,255,255,0.09) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Input focus state */
        .form-input {
            border: 1.5px solid #e2e8f0;
            background: #f9fafb;
            transition: all 0.18s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #1b5e20;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(27, 94, 32, 0.1);
        }

        /* Tombol login */
        .btn-login {
            background: #1b5e20;
            transition: background 0.18s ease, box-shadow 0.18s ease, transform 0.12s ease;
            box-shadow: 0 4px 12px rgba(27, 94, 32, 0.30);
        }
        .btn-login:hover {
            background: #155117;
            box-shadow: 0 6px 18px rgba(27, 94, 32, 0.38);
            transform: translateY(-1px);
        }
        .btn-login:active { transform: translateY(0); }

        /* Logo putih di panel kiri */
        .logo-white {
            filter: brightness(0) invert(1);
            opacity: 0.92;
        }

        /* Animasi ikon panah tombol */
        .btn-login:hover .arrow-icon { transform: translateX(4px); }
        .arrow-icon { transition: transform 0.18s ease; display: inline-block; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">

    <!-- Container: Card split dua kolom -->
    <div class="w-full max-w-[880px] bg-white rounded-[28px] shadow-2xl shadow-slate-300/60 overflow-hidden flex flex-col md:flex-row">

        <!-- ============================================================
             PANEL KIRI — Branding (Hanya tampil di layar MD ke atas)
        ============================================================ -->
        <div class="brand-panel hidden md:flex flex-col items-center justify-center text-center md:w-[42%] p-10 gap-0">

            <!-- Logo -->
            <div class="relative z-10 mb-7">
                <img src="{{ asset('images/logo-kerinci.png') }}"
                     alt="Kerinci Hydropower"
                     class="logo-white h-8 w-auto object-contain mx-auto">
            </div>

            <!-- Identitas Sistem -->
            <div class="relative z-10">
                <p class="text-emerald-300/70 text-[11px] font-semibold tracking-[0.25em] uppercase mb-2">
                    Sistem Operasional
                </p>
                <h1 class="text-white font-black leading-none tracking-tight" style="font-size: clamp(52px, 7vw, 72px);">
                    RKM
                </h1>
                <!-- Garis aksen tipis -->
                <div class="flex items-center justify-center space-x-2 mt-5">
                    <span class="block h-[2px] w-8 rounded-full bg-white/30"></span>
                    <span class="block h-[2px] w-3 rounded-full bg-[#d4af37]/80"></span>
                </div>
                <p class="text-white/40 text-xs font-medium mt-5 leading-relaxed">
                    PT Kerinci Merangin Hidro<br>PLTA Merangin 350 MW
                </p>
            </div>

            <!-- Badge Sistem Aktif -->
            <div class="relative z-10 mt-8">
                <span class="inline-flex items-center space-x-1.5 bg-white/10 border border-white/15 rounded-full px-3 py-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-white/60 text-[10px] font-medium tracking-wide">Sistem Aktif</span>
                </span>
            </div>
        </div>

        <!-- ============================================================
             PANEL KANAN — Form Login
        ============================================================ -->
        <div class="flex flex-col justify-center flex-1 px-8 py-10 sm:px-12 sm:py-12">

            <!-- Header Mobile: Logo + nama sistem (Hanya tampil di layar kecil) -->
            <div class="flex flex-col items-center mb-8 md:hidden">
                <img src="{{ asset('images/logo-kerinci.png') }}"
                     alt="Kerinci Hydropower"
                     class="h-7 w-auto object-contain mb-4">
                <div class="text-center">
                    <p class="text-[10px] font-semibold text-slate-400 tracking-[0.2em] uppercase">Sistem Operasional</p>
                    <h1 class="text-2xl font-black text-[#1b5e20] tracking-tight">RKM</h1>
                </div>
                <div class="w-px h-5 bg-slate-200 mt-4"></div>
            </div>

            <!-- Sambutan -->
            <div class="mb-7">
                <h2 class="text-[22px] font-bold text-slate-900 tracking-tight">Selamat Datang</h2>
                <p class="text-sm text-slate-500 mt-1">Masuk menggunakan akun yang telah terdaftar</p>
            </div>

            <!-- Notifikasi Error -->
            @if($errors->any())
                <div class="mb-5 flex items-start space-x-3 p-4 bg-rose-50 border border-rose-100 rounded-2xl text-xs">
                    <i class="fa-solid fa-circle-xmark text-rose-500 mt-0.5 flex-shrink-0"></i>
                    <div class="text-rose-700">
                        <p class="font-semibold mb-0.5">Autentikasi gagal</p>
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="mb-5 flex items-center space-x-2.5 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl text-xs text-emerald-700">
                    <i class="fa-solid fa-circle-check flex-shrink-0"></i>
                    <p class="font-medium">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 flex items-center space-x-2.5 p-4 bg-amber-50 border border-amber-100 rounded-2xl text-xs text-amber-700">
                    <i class="fa-solid fa-triangle-exclamation flex-shrink-0"></i>
                    <p class="font-medium">{{ session('error') }}</p>
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Username -->
                <div class="space-y-1.5">
                    <label for="username" class="block text-xs font-semibold text-slate-500 tracking-wider uppercase">
                        Username atau Email
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-regular fa-user text-sm"></i>
                        </span>
                        <input type="text"
                               name="username"
                               id="username"
                               value="{{ old('username') }}"
                               required autofocus
                               placeholder="Masukkan username atau email"
                               class="form-input w-full text-sm pl-11 pr-4 py-3.5 rounded-xl text-slate-800 placeholder-slate-400">
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-1.5">
                    <label for="password" class="block text-xs font-semibold text-slate-500 tracking-wider uppercase">
                        Password
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </span>
                        <input type="password"
                               name="password"
                               id="password"
                               required
                               placeholder="Masukkan password"
                               class="form-input w-full text-sm pl-11 pr-4 py-3.5 rounded-xl text-slate-800 placeholder-slate-400">
                    </div>
                </div>

                <!-- Tombol Masuk & Navigasi Kembali ke Beranda -->
                <div class="pt-2 space-y-3">
                    <button type="submit"
                            class="btn-login w-full text-white font-semibold text-sm py-4 rounded-xl flex items-center justify-center gap-2">
                        Masuk ke Sistem
                        <i class="fa-solid fa-arrow-right text-xs arrow-icon"></i>
                    </button>

                    <a href="{{ route('home') }}"
                       class="w-full py-3.5 px-4 rounded-xl border border-slate-200 bg-slate-50/80 hover:bg-slate-100 text-slate-600 hover:text-[#1b5e20] hover:border-slate-300 font-semibold text-sm flex items-center justify-center gap-2 transition duration-150 group">
                        <i class="fa-solid fa-arrow-left text-xs transition-transform group-hover:-translate-x-1"></i>
                        <span>Kembali ke Beranda RKM</span>
                    </a>
                </div>
            </form>

            <!-- Footer -->
            <div class="mt-7 pt-5 border-t border-slate-100 flex items-center justify-between">
                <details class="text-[11px] text-slate-400 cursor-pointer group">
                    <summary class="list-none flex items-center gap-1.5 font-medium hover:text-slate-600 transition-colors">
                        <i class="fa-solid fa-circle-info text-[#1b5e20]/60"></i>
                        Akun Pengujian
                    </summary>
                    <div class="absolute mt-2 bg-white rounded-xl p-3 border border-slate-200 shadow-lg text-left space-y-2 w-60 z-50">
                        <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Akun Tersedia</div>
                        @php
                            $accounts = [
                                ['role' => 'Admin HSE', 'username' => '1185'],
                                ['role' => 'Admin QMA', 'username' => '1171'],
                                ['role' => 'Admin PG',  'username' => 'AdminPG'],
                            ];
                        @endphp
                        @foreach($accounts as $acc)
                            <div class="flex items-center justify-between py-1.5 border-b border-slate-100 last:border-0">
                                <div>
                                    <span class="text-[11px] font-semibold text-slate-700">{{ $acc['role'] }}</span>
                                    <code class="ml-1.5 text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded-md">{{ $acc['username'] }}</code>
                                </div>
                                <span class="text-[10px] text-slate-400 italic">password</span>
                            </div>
                        @endforeach
                    </div>
                </details>

                <p class="text-[11px] text-slate-400">&copy; 2026 PT KMH</p>
            </div>
        </div>

    </div>
</body>
</html>
