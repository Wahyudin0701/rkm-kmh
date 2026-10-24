<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem RKM - Rekapitulasi Kendaraan & Mobilitas Gerbang | PT Kerinci Merangin Hidro</title>
    
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

        .glass-card {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .brand-green {
            background-color: #1b5e20;
        }

        .brand-green-hover:hover {
            background-color: #144918;
        }

        .text-brand-green {
            color: #1b5e20;
        }

        .glow-btn {
            box-shadow: 0 0 25px rgba(27, 94, 32, 0.45);
            transition: all 0.25s ease;
        }

        .glow-btn:hover {
            box-shadow: 0 0 35px rgba(34, 197, 94, 0.6);
            transform: translateY(-2px);
        }

        .badge-pulse {
            animation: pulse-dot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .5; transform: scale(1.15); }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-emerald-600 selection:text-white">

    <!-- ========================================================================= -->
    <!-- NAVBAR UTAMA -->
    <!-- ========================================================================= -->
    <nav class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200 transition-all duration-200 shadow-sm">
        <div class="w-full px-6 sm:px-10 lg:px-12 xl:px-16">
            <div class="flex items-center justify-between h-20">
                
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center">
                        <img src="{{ asset('images/logo-kerinci.png') }}" 
                             alt="Kerinci Hydropower" 
                             class="h-8 sm:h-9 w-auto object-contain transition hover:opacity-90">
                    </a>
                </div>

                <!-- Navigasi Menu Desktop (Tanpa Icon) -->
                <div class="hidden lg:flex items-center space-x-8 text-sm font-semibold text-slate-600">
                    <a href="#ringkasan" class="hover:text-[#1b5e20] transition-colors">
                        Ringkasan Sistem
                    </a>
                    <a href="#modul" class="hover:text-[#1b5e20] transition-colors">
                        3 Modul Utama
                    </a>
                    <a href="#alur" class="hover:text-[#1b5e20] transition-colors">
                        Alur Gerbang
                    </a>
                </div>

                <!-- Tombol Navigasi -->
                <div class="flex items-center">
                    <!-- Tombol Auth: Login / Dashboard -->
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
                           class="inline-flex items-center px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-[#1b5e20] hover:bg-[#144918] shadow-sm hover:shadow transition">
                            <span class="truncate max-w-[120px] sm:max-w-none">{{ auth()->user()->name }}</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" 
                           class="inline-flex items-center px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-[#1b5e20] hover:bg-[#144918] shadow-sm hover:shadow transition">
                            Login
                        </a>
                    @endauth
                </div>

            </div>
        </div>
    </nav>

    <!-- ========================================================================= -->
    <!-- FLASH ALERT NOTIFICATION (WARNING, ERROR, SUCCESS) -->
    <!-- ========================================================================= -->
    @if(session('warning') || session('error') || session('success'))
    <div class="w-full max-w-5xl mx-auto px-4 sm:px-6 pt-4 pb-1">
        @if(session('warning'))
            <div class="flex items-center p-4 mb-2 text-amber-900 rounded-2xl bg-amber-50 border border-amber-300 shadow-sm transition" role="alert">
                <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-600 flex items-center justify-center mr-3 flex-shrink-0 text-sm">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="text-xs sm:text-sm font-semibold flex-1">
                    {{ session('warning') }}
                </div>
            </div>
        @endif
        @if(session('error'))
            <div class="flex items-center p-4 mb-2 text-rose-900 rounded-2xl bg-rose-50 border border-rose-300 shadow-sm transition" role="alert">
                <div class="w-8 h-8 rounded-xl bg-rose-500/20 text-rose-600 flex items-center justify-center mr-3 flex-shrink-0 text-sm">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
                <div class="text-xs sm:text-sm font-semibold flex-1">
                    {{ session('error') }}
                </div>
            </div>
        @endif
        @if(session('success'))
            <div class="flex items-center p-4 mb-2 text-emerald-900 rounded-2xl bg-emerald-50 border border-emerald-300 shadow-sm transition" role="alert">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-600 flex items-center justify-center mr-3 flex-shrink-0 text-sm">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="text-xs sm:text-sm font-semibold flex-1">
                    {{ session('success') }}
                </div>
            </div>
        @endif
    </div>
    @endif

    <!-- ========================================================================= -->
    <!-- HERO SECTION: HEADLINE & CALL TO ACTION AKTIVITAS SISTEM -->
    <!-- ========================================================================= -->
    <section class="hero-pattern text-white relative overflow-hidden min-h-[calc(100vh-5rem)] flex items-center justify-center py-12">
        <!-- Floating Ambient Blurs -->
        <div class="absolute top-10 left-1/2 -translate-x-1/2 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full">
            <div class="max-w-3xl mx-auto text-center">
                
                <!-- Main Title -->
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight mb-6">
                    Sistem RKM & Kontrol Mobilitas <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-emerald-200 to-teal-100">Kendaraan Lewat Gate</span>
                </h1>

                <!-- Subtitle / Deskripsi Ringkas -->
                <p class="text-slate-300 text-base sm:text-lg leading-relaxed mb-10 font-normal">
                    Pusat pemantauan terpadu mobilitas kendaraan ringan (<span class="text-emerald-300 font-semibold">Light Vehicle</span>), verifikasi kelaikan lisensi <span class="text-emerald-300 font-semibold">SIMPER HSE</span> pengemudi, dan inspeksi fisik pos gerbang (<span class="text-emerald-300 font-semibold">Private Guard Checkpoint</span>) di seluruh area proyek PLTA Merangin.
                </p>

                <!-- Tombol Aksi Utama (Hero CTA) -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    
                    <!-- BUTTON LOGIN -->
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
                           class="w-full sm:w-auto glow-btn inline-flex items-center justify-center space-x-2.5 px-8 py-4 rounded-2xl text-base font-extrabold text-white bg-gradient-to-r from-emerald-600 to-[#1b5e20] hover:from-emerald-500 hover:to-[#17521c] border border-emerald-400/30">
                            <span>Dashboard Saya</span>
                            <i class="fa-solid fa-arrow-right text-sm"></i>
                        </a>
                    @else
                        <a href="{{ route('login') }}" 
                           class="w-full sm:w-auto glow-btn inline-flex items-center justify-center space-x-2.5 px-8 py-4 rounded-2xl text-base font-extrabold text-white bg-gradient-to-r from-emerald-600 to-[#1b5e20] hover:from-emerald-500 hover:to-[#17521c] border border-emerald-400/30">
                            <span>Masuk Ke Sistem RKM</span>
                            <i class="fa-solid fa-arrow-right text-sm"></i>
                        </a>
                    @endauth

                    <!-- Button Scroll Ringkasan Sistem -->
                    <a href="#ringkasan" 
                       class="w-full sm:w-auto inline-flex items-center justify-center space-x-2.5 px-6 py-4 rounded-2xl text-base font-semibold text-slate-200 glass-card hover:bg-white/10 transition">
                        <span>Pelajari Ringkasan Sistem</span>
                    </a>
                </div>

            </div>

        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- SECTION 1: RINGKASAN SISTEM RKM (OVERVIEW ARSITEKTUR BISNIS) -->
    <!-- ========================================================================= -->
    <section id="ringkasan" class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-[#1b5e20] text-xs font-bold uppercase tracking-widest bg-emerald-50 px-3.5 py-1 rounded-full border border-emerald-200">
                    Ringkasan Sistem
                </span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900 mt-4 tracking-tight">
                    Mengenal Sistem RKM & Tujuannya
                </h2>
                <div class="w-16 h-1 bg-[#1b5e20] mx-auto mt-4 rounded-full"></div>
                <p class="text-slate-600 mt-4 text-sm sm:text-base leading-relaxed">
                    Sistem <strong>RKM</strong> (Rekapitulasi Kendaraan & Mobilitas) adalah platform operasional cerdas yang dirancang khusus untuk mengelola mobilitas armada, pengemudi, dan inspeksi akses gerbang di kawasan megaprojek <strong>PLTA Merangin 350 MW</strong>.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                
                <!-- Kolom Kiri: Penjelasan Bisnis & Nilai Strategis -->
                <div class="space-y-6">
                    <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200">
                        <div class="flex items-start space-x-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-[#1b5e20] flex items-center justify-center flex-shrink-0 font-bold text-lg">
                                1
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 mb-1">Pengendalian Akses Gerbang Proyek (Gate Access Control)</h3>
                                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                    Setiap kendaraan yang melintas antar-lokasi proyek (seperti terowongan Adit, Bendungan/DAM, Mess Kumun, Power House) wajib dicatat melalui pos pemeriksaan gerbang untuk mencegah akses tak berizin dan melacak posisi unit secara berkala.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200">
                        <div class="flex items-start space-x-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-[#1b5e20] flex items-center justify-center flex-shrink-0 font-bold text-lg">
                                2
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 mb-1">Kepatuhan Keselamatan & SIMPER K3 Terintegrasi</h3>
                                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                    Hanya karyawan dengan SIMPER (Surat Izin Mengemudi Perusahaan) yang sah dan belum kedaluwarsa yang diizinkan mengemudikan kendaraan operasional. Sistem otomatis memblokir form jika SIMPER ditemukan kedaluwarsa.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200">
                        <div class="flex items-start space-x-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-[#1b5e20] flex items-center justify-center flex-shrink-0 font-bold text-lg">
                                3
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 mb-1">Akurasi Odometer & Efisiensi Aset Kendaraan</h3>
                                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                    Mencatat Kilometer (KM) sebelumnya vs KM aktual untuk menghitung jarak tempuh harian (distance), memastikan akurasi data pemeliharaan berkala, konsumsi bahan bakar, dan log perjalanan unit.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Card Ringkasan Proyek & Lokasi -->
                <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-[#123616] text-white p-8 rounded-3xl shadow-xl border border-slate-700 space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-700/80 pb-5">
                        <div>
                            <span class="text-emerald-400 text-xs font-bold uppercase tracking-wider">Identitas Proyek</span>
                            <h3 class="text-xl font-black text-white mt-0.5">PLTA Merangin 350 MW</h3>
                        </div>
                        <img src="{{ asset('images/logo-kerinci.png') }}" alt="KMH" class="h-8 w-auto object-contain brightness-0 invert opacity-90">
                    </div>

                    <p class="text-slate-300 text-xs sm:text-sm leading-relaxed">
                        Pembangkit Listrik Tenaga Air (PLTA) Merangin merupakan salah satu proyek infrastruktur energi terbarukan strategis nasional. Sistem RKM mengontrol pergerakan seluruh armada kendaraan di lebih dari 15 pos gerbang pengamanan:
                    </p>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
                        @foreach($gates->take(9) as $gate)
                            <div class="bg-white/10 hover:bg-white/15 px-3 py-2 rounded-lg border border-white/10 flex items-center space-x-2 truncate">
                                <i class="fa-solid fa-location-dot text-emerald-400 text-[10px]"></i>
                                <span class="font-medium text-slate-200 truncate">{{ $gate->name }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-4 border-t border-slate-700/80 flex items-center justify-between">
                        <span class="text-xs text-slate-400">Total {{ $totalGates }} Titik Gerbang Terproteksi</span>
                        <span class="text-xs text-emerald-400 font-medium flex items-center space-x-1.5">
                            <i class="fa-solid fa-qrcode text-[11px]"></i>
                            <span>Scan QR pada unit untuk catat gerbang</span>
                        </span>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- SECTION 2: TIGA PILAR MODUL UTAMA SISTEM RKM -->
    <!-- ========================================================================= -->
    <section id="modul" class="py-20 bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-[#1b5e20] text-xs font-bold uppercase tracking-widest bg-emerald-100 text-[#1b5e20] px-3.5 py-1 rounded-full border border-emerald-300">
                    Struktur Modul
                </span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900 mt-4 tracking-tight">
                    3 Pilar Pengelolaan Sistem RKM
                </h2>
                <div class="w-16 h-1 bg-[#1b5e20] mx-auto mt-4 rounded-full"></div>
                <p class="text-slate-600 mt-4 text-sm sm:text-base leading-relaxed">
                    Sistem RKM mengintegrasikan tiga departemen utama yang berkolaborasi secara real-time untuk menjamin kepatuhan, keandalan aset, dan keamanan gerbang.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Pilar 1: HSE -->
                <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-200 text-[#1b5e20] flex items-center justify-center text-xl mb-5 shadow-sm">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider block mb-1">Modul 1: HSE</span>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Master Data SIMPER & Pengemudi</h3>
                        <p class="text-slate-600 text-xs sm:text-sm leading-relaxed mb-4">
                            Dikelola oleh Divisi HSE untuk mendata seluruh karyawan, operator, dan driver yang berwenang mengemudikan kendaraan proyek.
                        </p>
                        <ul class="space-y-2 text-xs text-slate-600">
                            <li class="flex items-center space-x-2">
                                <i class="fa-solid fa-check text-emerald-600 text-[11px]"></i>
                                <span>Pencatatan NIK, Nama, Divisi, dan Unit Kerja</span>
                            </li>
                            <li class="flex items-center space-x-2">
                                <i class="fa-solid fa-check text-emerald-600 text-[11px]"></i>
                                <span>Validasi otomatis kedaluwarsa masa SIMPER</span>
                            </li>
                            <li class="flex items-center space-x-2">
                                <i class="fa-solid fa-check text-emerald-600 text-[11px]"></i>
                                <span>Kunci pengamanan berkendara di area kerja K3</span>
                            </li>
                        </ul>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-500 font-medium">
                        Admin Penanggung Jawab: HSE Department
                    </div>
                </div>

                <!-- Pilar 2: QMA -->
                <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 flex items-center justify-center text-xl mb-5 shadow-sm">
                            <i class="fa-solid fa-car-side"></i>
                        </div>
                        <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider block mb-1">Modul 2: QMA</span>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Master Kendaraan & Log Aktivitas</h3>
                        <p class="text-slate-600 text-xs sm:text-sm leading-relaxed mb-4">
                            Dikelola oleh Quality Management Asset untuk memantau armada Kendaraan Ringan (LV) dan Heavy Equipment proyek.
                        </p>
                        <ul class="space-y-2 text-xs text-slate-600">
                            <li class="flex items-center space-x-2">
                                <i class="fa-solid fa-check text-blue-600 text-[11px]"></i>
                                <span>Master Light Vehicle (LV) & Heavy Equipment</span>
                            </li>
                            <li class="flex items-center space-x-2">
                                <i class="fa-solid fa-check text-blue-600 text-[11px]"></i>
                                <span>Pelacakan Odometer / KM aktual secara runut</span>
                            </li>
                            <li class="flex items-center space-x-2">
                                <i class="fa-solid fa-check text-blue-600 text-[11px]"></i>
                                <span>Rekapitulasi log aktivitas harian & jarak tempuh</span>
                            </li>
                        </ul>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-500 font-medium">
                        Admin Penanggung Jawab: QMA Department
                    </div>
                </div>

                <!-- Pilar 3: Private Guard (PG) -->
                <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center text-xl mb-5 shadow-sm">
                            <i class="fa-solid fa-shield"></i>
                        </div>
                        <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider block mb-1">Modul 3: Private Guard (PG)</span>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Inspeksi & Verifikasi Pos Gerbang</h3>
                        <p class="text-slate-600 text-xs sm:text-sm leading-relaxed mb-4">
                            Petugas keamanan satpam di tiap pos gerbang melakukan pemeriksaan fisik real-time saat kendaraan tiba.
                        </p>
                        <ul class="space-y-2 text-xs text-slate-600">
                            <li class="flex items-center space-x-2">
                                <i class="fa-solid fa-check text-amber-600 text-[11px]"></i>
                                <span>Monitoring antrean unit di pos gerbang</span>
                            </li>
                            <li class="flex items-center space-x-2">
                                <i class="fa-solid fa-check text-amber-600 text-[11px]"></i>
                                <span>Verifikasi fisik, KM odometer & identitas driver</span>
                            </li>
                            <li class="flex items-center space-x-2">
                                <i class="fa-solid fa-check text-amber-600 text-[11px]"></i>
                                <span>Otorisasi izin melintas (Approve / Reject)</span>
                            </li>
                        </ul>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-500 font-medium">
                        Petugas: Satpam Pos Gerbang (PG)
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- SECTION 3: ALUR KERJA AKTIVITAS KENDARAAN LEWAT GATE -->
    <!-- ========================================================================= -->
    <section id="alur" class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-[#1b5e20] text-xs font-bold uppercase tracking-widest bg-emerald-50 px-3.5 py-1 rounded-full border border-emerald-200">
                    Alur Operasional
                </span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900 mt-4 tracking-tight">
                    Bagaimana Aktivitas Kendaraan Lewat Gate Bekerja?
                </h2>
                <div class="w-16 h-1 bg-[#1b5e20] mx-auto mt-4 rounded-full"></div>
                <p class="text-slate-600 mt-4 text-sm sm:text-base leading-relaxed">
                    Setiap kali pengemudi mengoperasikan kendaraan melintasi gerbang, ada 4 tahapan ringkas yang dilakukan untuk memastikan kepatuhan keselamatan dan validasi pos.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- Step 1 -->
                <div class="relative bg-slate-50 rounded-2xl p-6 border border-slate-200">
                    <span class="absolute top-4 right-4 text-3xl font-black text-slate-200">01</span>
                    <div class="w-10 h-10 rounded-xl bg-[#1b5e20] text-white flex items-center justify-center text-sm font-bold mb-4 shadow-sm">
                        <i class="fa-solid fa-qrcode"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 mb-2">Scan QR Pada Unit</h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Driver memindai stiker fisik QR Code yang terpasang pada mobil unit kendaraan operasional (misal: <strong>LV62</strong>) menggunakan kamera smartphone.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="relative bg-slate-50 rounded-2xl p-6 border border-slate-200">
                    <span class="absolute top-4 right-4 text-3xl font-black text-slate-200">02</span>
                    <div class="w-10 h-10 rounded-xl bg-[#1b5e20] text-white flex items-center justify-center text-sm font-bold mb-4 shadow-sm">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 mb-2">Cek SIMPER & KM Awal</h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Sistem memverifikasi status masa aktif SIMPER pengemudi dari database HSE dan memuat riwayat KM terakhir kendaraan secara otomatis.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="relative bg-slate-50 rounded-2xl p-6 border border-slate-200">
                    <span class="absolute top-4 right-4 text-3xl font-black text-slate-200">03</span>
                    <div class="w-10 h-10 rounded-xl bg-[#1b5e20] text-white flex items-center justify-center text-sm font-bold mb-4 shadow-sm">
                        <i class="fa-solid fa-file-pen"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 mb-2">Isi Form Timesheet</h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Driver memilih lokasi pos gerbang, arah melintas (<strong>Masuk / In</strong> atau <strong>Keluar / Out</strong>), angka KM aktual, dan rencana kerja perjalanan.
                    </p>
                </div>

                <!-- Step 4 -->
                <div class="relative bg-slate-50 rounded-2xl p-6 border border-slate-200">
                    <span class="absolute top-4 right-4 text-3xl font-black text-slate-200">04</span>
                    <div class="w-10 h-10 rounded-xl bg-[#1b5e20] text-white flex items-center justify-center text-sm font-bold mb-4 shadow-sm">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 mb-2">Validasi Satpam (PG)</h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Petugas Satpam di pos gerbang memeriksa fisik kendaraan dan mencocokkan form di sistem sebelum menekan tombol persetujuan (Approve) izin lewat.
                    </p>
                </div>

            </div>


        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- FOOTER RESMI PERUSAHAAN -->
    <!-- ========================================================================= -->
    <footer class="bg-slate-900 text-slate-400 py-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 pb-8 border-b border-slate-800">
                <div class="flex items-center space-x-3.5">
                    <img src="{{ asset('images/logo-kerinci.png') }}" alt="KMH" class="h-8 w-auto object-contain brightness-0 invert opacity-90">
                    <div>
                        <p class="font-bold text-white text-sm">PT Kerinci Merangin Hidro</p>
                        <p class="text-xs text-slate-400">Proyek PLTA Merangin 350 MW &bull; Jambi, Indonesia</p>
                    </div>
                </div>

                <div class="flex items-center space-x-6 text-xs font-semibold">
                    <a href="{{ route('home') }}" class="hover:text-white transition">Beranda</a>
                    <a href="{{ route('login') }}" class="hover:text-white transition">Portal Petugas</a>
                </div>
            </div>

            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-3">
                <p>&copy; 2026 PT Kerinci Merangin Hidro. Seluruh hak cipta dilindungi undang-undang.</p>
                <p class="flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>Sistem Manajemen RKM Beroperasi Normal</span>
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
