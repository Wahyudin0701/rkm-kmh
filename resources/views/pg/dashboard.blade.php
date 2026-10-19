@extends('layouts.app')

@section('title', 'Dashboard - Admin Private Guard | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Admin PG (Persis Gaya Referensi IT KMH) -->
    <div>
        <div class="flex items-center space-x-1.5 mb-1">
            <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                SISTEM ADMINISTRATOR
            </span>
            <!-- Tombol Info Panduan Halaman Sejajar Kicker -->
            <button type="button" 
                    onclick="openGuideModal()" 
                    class="w-3.5 h-3.5 rounded-full bg-slate-200/90 hover:bg-slate-300 text-slate-500 hover:text-slate-800 text-[8px] inline-flex items-center justify-center font-bold transition shadow-2xs cursor-pointer focus:outline-none" 
                    title="Panduan Halaman Dashboard Private Guard">
                <i class="fa-solid fa-info"></i>
            </button>
        </div>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
            Selamat datang, {{ auth()->user()->name }}
        </h2>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Berikut ringkasan operasional sistem keamanan pos gerbang dan tata kelola personel Satpam PT Kerinci Merangin Hidro secara keseluruhan.
        </p>
    </div>

    <!-- 5 Kartu Metrik Ringkasan (Persis Gaya Kotak Warna Pastel Gambar Referensi) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- 1. Total Satpam (Pastel Hijau) -->
        <div>
            <div class="bg-[#eef7ee] border border-[#d6ebd7] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#2e7d32]">{{ $totalGuards }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">TOTAL SATPAM</p>
        </div>

        <!-- 2. Satpam Aktif (Pastel Biru Muda) -->
        <div>
            <div class="bg-[#edf5fd] border border-[#d2e4fb] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#1976d2]">{{ $activeGuards }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">SATPAM AKTIF</p>
        </div>

        <!-- 3. Pos Gerbang (Pastel Mint / Emerald) -->
        <div>
            <div class="bg-[#edfbf4] border border-[#d1f5e2] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#10b981]">{{ $totalGates }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">POS GERBANG</p>
        </div>

        <!-- 4. Verifikasi Hari Ini (Pastel Soft Blue) -->
        <div>
            <div class="bg-[#f0f4fe] border border-[#dae5fd] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#2563eb]">{{ $todayVerifications }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">VERIFIKASI HARI INI</p>
        </div>

        <!-- 5. Penolakan Gerbang (Pastel Amber / Yellow) -->
        <div>
            <div class="bg-[#fef9e8] border border-[#feefc7] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#f59e0b]">{{ $totalRejections }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">PENOLAKAN GERBANG</p>
        </div>
    </div>

    <!-- Layout 2 Kolom Bawah: Log Verifikasi & Personel Satpam (Persis Gambar Referensi) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        
        <!-- Kolom Kiri: Log Verifikasi Gerbang Terkini (Format Log Serah Terima Gambar Referensi) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-sm font-bold text-slate-900">Log Verifikasi Gerbang Terkini</h3>
                @can('pg.logs.view')
                <a href="{{ route('pg.logs.index') }}" class="text-xs font-bold text-[#1b5e20] hover:underline">
                    Lihat Semua
                </a>
                @endcan
            </div>

            <div class="space-y-4">
                @forelse($recentActivities as $act)
                    <div class="flex items-start justify-between py-2.5 border-b border-slate-50 last:border-b-0">
                        <div class="flex items-start space-x-3.5 min-w-0">
                            <!-- Avatar Inisial Bulat Hijau Pastel -->
                            <div class="w-9 h-9 rounded-full bg-[#eef7ee] text-[#1b5e20] font-bold text-xs flex items-center justify-center flex-shrink-0 mt-0.5">
                                {{ strtoupper(substr($act->driver->name ?? 'G', 0, 1)) }}
                            </div>

                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-900 leading-tight truncate">
                                    {{ $act->transaction_no }} <span class="text-slate-400 font-normal">ke</span> {{ $act->driver->name ?? 'Pengemudi' }}
                                </p>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    {{ $act->vehicle->code ?? 'Armada' }} &bull; Pos {{ $act->gate->name ?? '-' }} (Arah: {{ $act->direction === 'in' ? 'Masuk' : 'Keluar' }})
                                </p>
                                <p class="text-[10px] text-slate-400 mt-0.5 font-mono">
                                    {{ $act->recorded_at ? $act->recorded_at->format('d M Y H:i') : '-' }} WIB
                                </p>
                            </div>
                        </div>

                        <!-- Status Badge Kanan -->
                        <div class="flex-shrink-0 ml-3">
                            @if($act->validation_status === 'approved')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    Disetujui
                                </span>
                            @elseif($act->validation_status === 'rejected')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">
                                    Ditolak
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                    Menunggu
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400 text-sm">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                        <p class="text-xs font-bold text-slate-600">Belum ada aktivitas verifikasi gerbang</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Aktivitas kendaraan di pos gerbang akan muncul otomatis di sini.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Kolom Kanan: 2 Kartu Stacked (Log Mutasi & Log Pengembalian Gambar Referensi) -->
        <div class="space-y-6">
            
            <!-- Kartu 1: Petugas Satpam per Regu (Format Log Mutasi Gambar Referensi) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-bold text-slate-900">Petugas Satpam per Regu</h3>
                    @can('pg.guards.view')
                    <a href="{{ route('pg.guards.index') }}" class="text-xs font-bold text-[#1b5e20] hover:underline">
                        Lihat Semua
                    </a>
                    @endcan
                </div>

                <div class="space-y-4">
                    @forelse($recentGuards as $g)
                        <div class="flex items-start justify-between py-2 border-b border-slate-50 last:border-b-0">
                            <div class="flex items-start space-x-3.5 min-w-0">
                                <!-- Icon Inisial Bulat Biru Pastel -->
                                <div class="w-9 h-9 rounded-full bg-[#edf5fd] text-[#1976d2] font-bold text-xs flex items-center justify-center flex-shrink-0 mt-0.5">
                                    {{ strtoupper(substr($g->name, 0, 1)) }}
                                </div>

                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-900 leading-tight">
                                        {{ $g->badge_id }} <span class="text-slate-400 font-normal">&bull;</span> {{ $g->name }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Pos: {{ $g->gate->name ?? 'Belum Ditentukan' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Badge Regu Kanan -->
                            <div class="flex-shrink-0 ml-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $g->team == 1 ? 'bg-blue-100 text-blue-700' : ($g->team == 2 ? 'bg-amber-100 text-amber-700' : 'bg-purple-100 text-purple-700') }}">
                                    Regu {{ $g->team }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400">
                            <p class="text-xs font-bold text-slate-600">Belum ada data Satpam</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Kartu 2: Ringkasan Personel Regu (Format Log Pengembalian Gambar Referensi) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-900">Ringkasan Personel Regu Tugas</h3>
                    <a href="{{ route('pg.guards.index') }}" class="text-xs font-bold text-[#1b5e20] hover:underline">
                        Kelola Satpam
                    </a>
                </div>

                <div class="grid grid-cols-3 gap-3 pt-1">
                    <!-- Regu 1 -->
                    <div class="p-3 rounded-xl bg-blue-50/60 border border-blue-100 text-center">
                        <p class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">REGU 1</p>
                        <p class="text-xl font-black text-blue-900 mt-1">{{ $team1Count }}</p>
                        <p class="text-[10px] text-blue-500 font-medium mt-0.5">Personel</p>
                    </div>

                    <!-- Regu 2 -->
                    <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-100 text-center">
                        <p class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">REGU 2</p>
                        <p class="text-xl font-black text-amber-900 mt-1">{{ $team2Count }}</p>
                        <p class="text-[10px] text-amber-500 font-medium mt-0.5">Personel</p>
                    </div>

                    <!-- Regu 3 -->
                    <div class="p-3 rounded-xl bg-purple-50/60 border border-purple-100 text-center">
                        <p class="text-[10px] font-bold text-purple-600 uppercase tracking-wider">REGU 3</p>
                        <p class="text-xl font-black text-purple-900 mt-1">{{ $team3Count }}</p>
                        <p class="text-[10px] text-purple-500 font-medium mt-0.5">Personel</p>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (DASHBOARD PRIVATE GUARD)                    -->
<!-- ========================================================================= -->
<div id="guideModal" 
     onclick="if(event.target === this) closeGuideModal()" 
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
                    <p class="text-xs text-slate-500">Dashboard Private Guard</p>
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
                Selamat datang di <strong>Dashboard Private Guard</strong>! Halaman ini adalah pusat ringkasan untuk memantau personel pengamanan pos dan alur verifikasi fisik armada di PT Kerinci Merangin Hidro. Di halaman ini Anda dapat:
            </p>

            <div class="space-y-3">
                <!-- Fitur 1 (Hijau) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-emerald-200/50">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Monitoring Personel Satpam & Regu</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Pantau total petugas satpam aktif serta pembagian giliran piket per regu tugas (Regu 1, Regu 2, dan Regu 3) secara real-time.
                        </p>
                    </div>
                </div>

                <!-- Fitur 2 (Biru) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-blue-200/50">
                        <i class="fa-solid fa-torii-gate"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Pemeriksaan & Validasi Pos Gerbang</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Akses menu verifikasi gerbang untuk memvalidasi kelayakan fisik kendaraan, odometer, serta izin masuk/keluar sebelum portal dibuka.
                        </p>
                    </div>
                </div>

                <!-- Fitur 3 (Amber) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-amber-200/50">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Rekapitulasi Log Riwayat Gerbang</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Lihat catatan historis kendaraan yang disetujui atau ditolak lengkap dengan catatan penolakan dan petugas pemeriksa.
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
@endsection

@section('scripts')
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
