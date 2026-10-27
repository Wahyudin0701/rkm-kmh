@extends('layouts.app')

@section('title', 'Dashboard - Admin HSE | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Admin HSE (Persis Gaya Referensi IT KMH) -->
    <div>
        <div class="flex items-center space-x-1.5 mb-1">
            <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                SISTEM ADMINISTRATOR
            </span>
            <!-- Tombol Info Panduan Halaman Sejajar Kicker -->
            <button type="button" 
                    onclick="openGuideModal()" 
                    class="w-3.5 h-3.5 rounded-full bg-slate-200/90 hover:bg-slate-300 text-slate-500 hover:text-slate-800 text-[8px] inline-flex items-center justify-center font-bold transition shadow-2xs cursor-pointer focus:outline-none" 
                    title="Panduan Halaman Dashboard HSE">
                <i class="fa-solid fa-info"></i>
            </button>
        </div>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
            Selamat datang, {{ auth()->user()->name }}
        </h2>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Berikut ringkasan operasional keselamatan dan tata kelola izin mengemudi SIMPER PT Kerinci Merangin Hidro secara keseluruhan.
        </p>
    </div>

    <!-- 4 Kartu Metrik Ringkasan (Persis Gaya Kotak Warna Pastel Referensi) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Karyawan (Pastel Hijau) -->
        <div>
            <div class="bg-[#eef7ee] border border-[#d6ebd7] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#2e7d32]">{{ $totalEmployees }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">TOTAL KARYAWAN</p>
        </div>

        <!-- 2. Driver Aktif (Pastel Biru Muda) -->
        <div>
            <div class="bg-[#edf5fd] border border-[#d2e4fb] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#1976d2]">{{ $totalDrivers }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">DRIVER AKTIF</p>
        </div>

        <!-- 3. SIMPER Kedaluwarsa (Pastel Soft Blue) -->
        <div>
            <div class="bg-[#f0f4fe] border border-[#dae5fd] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#2563eb]">{{ $expiredSimperCount }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">SIMPER KEDALUWARSA</p>
        </div>

        <!-- 4. Bukan Driver (Pastel Amber / Yellow) -->
        <div>
            <div class="bg-[#fef9e8] border border-[#feefc7] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#f59e0b]">{{ $nonDriversCount }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">BUKAN DRIVER</p>
        </div>
    </div>

    <!-- Layout 2 Kolom Bawah: Log Driver & Mutasi SIMPER (Persis Gambar Referensi) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        
        <!-- Kolom Kiri: Daftar Driver & Status SIMPER Terkini -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Daftar Driver & Status Terkini</h3>
                    <p class="text-[11px] text-slate-500">Personel pengemudi terdaftar beserta masa berlaku SIMPER</p>
                </div>
                <a href="{{ route('hse.employees.index') }}" class="text-xs font-bold text-[#1b5e20] hover:underline flex items-center gap-1">
                    <span>Kelola Data</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <div class="space-y-3.5">
                @forelse($recentDrivers as $driver)
                    <div class="flex items-start space-x-3.5 py-2.5 border-b border-slate-50 last:border-b-0 hover:bg-slate-50/60 rounded-xl px-2.5 transition">
                        <!-- Avatar Inisial Bulat Hijau Pastel -->
                        <div class="w-10 h-10 rounded-2xl bg-[#eef7ee] text-[#1b5e20] font-bold text-xs flex items-center justify-center flex-shrink-0 mt-0.5 border border-[#d6ebd7]">
                            {{ strtoupper(substr($driver->name, 0, 1)) }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold text-slate-900 leading-tight">
                                    {{ $driver->name }}
                                </span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    NIK: {{ $driver->nik }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-0.5">
                                {{ $driver->position ?? 'Driver Operasional' }} &bull; Divisi {{ $driver->division ?? '-' }}
                            </p>
                            <p class="text-[10px] text-slate-500 mt-1 flex items-center gap-1.5 font-mono">
                                <i class="fa-regular fa-id-card text-slate-400"></i>
                                <span>Masa Berlaku: <strong>{{ $driver->simper_expired_at ? $driver->simper_expired_at->locale('id')->translatedFormat('d M Y') : 'Belum Diatur' }}</strong></span>
                            </p>
                        </div>

                        <div class="flex-shrink-0">
                            @if($driver->hasValidSimper())
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <i class="fa-solid fa-circle-check text-[9px]"></i>
                                    <span>Aktif</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    <i class="fa-solid fa-triangle-exclamation text-[9px]"></i>
                                    <span>Kedaluwarsa</span>
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-6">Belum ada data driver terdaftar.</p>
                @endforelse
            </div>
        </div>

        <!-- Kolom Kanan: 2 Kartu Stacked (Monitoring Kepatuhan SIMPER & Karyawan Non-Driver) -->
        <div class="space-y-6">
            
            <!-- Kartu Atas: Monitoring Kepatuhan Masa Berlaku SIMPER -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Monitoring Kepatuhan SIMPER</h3>
                        <p class="text-[11px] text-slate-500">Prioritas masa berlaku izin mengemudi driver</p>
                    </div>
                    <a href="{{ route('hse.employees.index', ['simper_status' => 'expired']) }}" class="text-xs font-bold text-[#1b5e20] hover:underline flex items-center gap-1">
                        <span>Filter Data</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                <div class="space-y-3.5">
                    @forelse($simperLogs as $log)
                        @php
                            $isExpired = !$log->simper_expired_at || $log->simper_expired_at->isPast();
                            $daysRemaining = $log->simper_expired_at ? (int) now()->startOfDay()->diffInDays($log->simper_expired_at->startOfDay(), false) : null;
                            $isExpiringSoon = !$isExpired && $daysRemaining !== null && $daysRemaining <= 30;
                        @endphp
                        <div class="flex items-start space-x-3.5 py-2.5 border-b border-slate-50 last:border-b-0 hover:bg-slate-50/60 rounded-xl px-2.5 transition">
                            <!-- Icon Status Berwarna Sesuai Urgensi -->
                            @if($isExpired)
                                <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0 mt-0.5 border border-rose-100" title="SIMPER Kedaluwarsa">
                                    <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                                </div>
                            @elseif($isExpiringSoon)
                                <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0 mt-0.5 border border-amber-100" title="SIMPER Segera Habis">
                                    <i class="fa-solid fa-clock text-sm"></i>
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0 mt-0.5 border border-emerald-100" title="SIMPER Masih Berlaku">
                                    <i class="fa-solid fa-shield-halved text-sm"></i>
                                </div>
                            @endif

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-bold text-slate-900 leading-tight">
                                        {{ $log->name }}
                                    </span>
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-mono text-slate-600 bg-slate-100 border border-slate-200">
                                        NIK: {{ $log->nik }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Divisi {{ $log->division ?? '-' }}
                                </p>

                                <!-- Keterangan Status Waktu yang Sangat Mudah Dipahami -->
                                @if(!$log->simper_expired_at)
                                    <p class="text-[10px] text-slate-400 mt-1 italic">
                                        Belum memiliki tanggal masa berlaku
                                    </p>
                                @elseif($isExpired)
                                    <p class="text-[10px] font-bold text-rose-600 mt-1 flex items-center gap-1 font-mono">
                                        <i class="fa-solid fa-circle-xmark text-[9px]"></i>
                                        <span>Kedaluwarsa: {{ $log->simper_expired_at->locale('id')->translatedFormat('d M Y') }} ({{ $log->simper_expired_at->locale('id')->diffForHumans() }})</span>
                                    </p>
                                @elseif($isExpiringSoon)
                                    <p class="text-[10px] font-bold text-amber-600 mt-1 flex items-center gap-1 font-mono">
                                        <i class="fa-solid fa-hourglass-half text-[9px]"></i>
                                        <span>Berakhir: {{ $log->simper_expired_at->locale('id')->translatedFormat('d M Y') }} (Sisa {{ $daysRemaining }} hari)</span>
                                    </p>
                                @else
                                    <p class="text-[10px] font-medium text-emerald-700 mt-1 flex items-center gap-1 font-mono">
                                        <i class="fa-solid fa-circle-check text-[9px] text-emerald-600"></i>
                                        <span>Berlaku s.d. {{ $log->simper_expired_at->locale('id')->translatedFormat('d M Y') }} ({{ $log->simper_expired_at->locale('id')->diffForHumans() }})</span>
                                    </p>
                                @endif
                            </div>

                            <!-- Badge Kanan -->
                            <div class="flex-shrink-0">
                                @if($isExpired)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        Perlu Perpanjangan
                                    </span>
                                @elseif($isExpiringSoon)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        Segera Habis
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aman
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">Belum ada data SIMPER driver.</p>
                    @endforelse
                </div>
            </div>

            <!-- Kartu Bawah: Karyawan Non-Driver (Format Bersih & Rapi) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Karyawan Non-Driver</h3>
                        <p class="text-[11px] text-slate-500">Staf & personel tanpa izin mengemudi SIMPER</p>
                    </div>
                    <a href="{{ route('hse.employees.index', ['driver_status' => 'non_driver']) }}" class="text-xs font-bold text-[#1b5e20] hover:underline flex items-center gap-1">
                        <span>Lihat Semua</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                <div class="space-y-3.5">
                    @forelse($nonDrivers as $staff)
                        <div class="flex items-start space-x-3.5 py-2.5 border-b border-slate-50 last:border-b-0 hover:bg-slate-50/60 rounded-xl px-2.5 transition">
                            <!-- Icon Bulat Netral -->
                            <div class="w-10 h-10 rounded-2xl bg-slate-100 text-slate-500 font-bold text-xs flex items-center justify-center flex-shrink-0 mt-0.5 border border-slate-200">
                                {{ strtoupper(substr($staff->name, 0, 1)) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-bold text-slate-900 leading-tight">
                                        {{ $staff->name }}
                                    </span>
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-mono text-slate-600 bg-slate-100 border border-slate-200">
                                        NIK: {{ $staff->nik }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-600 mt-0.5">
                                    {{ $staff->position ?? 'Staf' }} &bull; Divisi {{ $staff->division ?? '-' }}
                                </p>
                                <p class="text-[10px] text-slate-400 mt-0.5">
                                    Unit Kerja: {{ $staff->unit_kerja ?? '-' }}
                                </p>
                            </div>

                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                    Bukan Driver
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">Belum ada karyawan non-driver.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (DASHBOARD OPERASIONAL HSE)                  -->
<!-- ========================================================================= -->
<div id="guideModal" 
     class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-slate-100 transform transition-all my-8">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-sm border border-slate-200">
                    <i class="fa-solid fa-info"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight leading-tight">Panduan Halaman</h3>
                    <p class="text-xs text-slate-500">Dashboard Operasional</p>
                </div>
            </div>
            <button type="button" 
                    onclick="closeGuideModal()" 
                    class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="mt-4 space-y-4">
            <p class="text-xs text-slate-600 leading-relaxed">
                Selamat datang di <strong>Dashboard</strong>! Halaman ini adalah pusat ringkasan untuk memantau keselamatan operasional dan tata kelola izin mengemudi SIMPER secara cepat. Di halaman ini Anda dapat:
            </p>

            <div class="space-y-3">
                <!-- Fitur 1 (Hijau) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-emerald-200/50">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Memantau Ringkasan Angka Karyawan</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Lihat total karyawan, driver aktif, SIMPER yang kedaluwarsa, dan staf non-driver melalui deretan kartu metrik di bagian atas.
                        </p>
                    </div>
                </div>

                <!-- Fitur 2 (Biru) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-blue-200/50">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Monitoring Kepatuhan Masa Berlaku SIMPER</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Pantau driver yang masa berlakunya telah habis atau segera kedaluwarsa untuk memastikan kepatuhan keselamatan kerja sebelum bertugas.
                        </p>
                    </div>
                </div>

                <!-- Fitur 3 (Amber) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-amber-200/50">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Akses Cepat (Shortcut Menu)</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Klik pada tombol "Kelola Data" atau "Filter Data" pada kartu log untuk melompat langsung ke halaman detail terkait.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Tombol -->
        <div class="pt-4 mt-5 border-t border-slate-100 flex items-center justify-end">
            <button type="button" 
                    onclick="closeGuideModal()" 
                    class="px-6 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer shadow-2xs">
                Saya Mengerti
            </button>
        </div>
    </div>
</div>

<script>
    function openGuideModal() {
        const modal = document.getElementById('guideModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.documentElement.classList.add('modal-open');
            document.body.classList.add('modal-open');
        }
    }

    function closeGuideModal() {
        const modal = document.getElementById('guideModal');
        if (modal) {
            modal.classList.add('hidden');
            document.documentElement.classList.remove('modal-open');
            document.body.classList.remove('modal-open');
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGuideModal();
        }
    });
</script>
@endsection
