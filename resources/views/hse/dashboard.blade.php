@extends('layouts.app')

@section('title', 'Dashboard - Admin HSE | PT Kerinci Merangin Hidro')

@section('content')
<div class="space-y-6">

    <!-- Header Admin HSE (Persis Gaya Referensi IT KMH) -->
    <div>
        <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20] block mb-1">
            SISTEM ADMINISTRATOR
        </span>
        <div class="flex items-center space-x-2.5">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                Selamat datang, {{ auth()->user()->name }}
            </h2>
            <span class="w-5 h-5 rounded-full bg-slate-200/90 text-slate-500 text-xs inline-flex items-center justify-center font-bold shadow-sm" title="Dashboard Operasional Admin HSE">
                <i class="fa-solid fa-info text-[10px]"></i>
            </span>
        </div>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Berikut ringkasan operasional keselamatan dan tata kelola izin mengemudi SIMPER PT Kerinci Merangin Hidro secara keseluruhan.
        </p>
    </div>

    <!-- 5 Kartu Metrik Ringkasan (Persis Gaya Kotak Warna Pastel Referensi) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
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

        <!-- 3. SIMPER Tersedia (Pastel Mint / Emerald) -->
        <div>
            <div class="bg-[#edfbf4] border border-[#d1f5e2] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#10b981]">{{ $validSimperCount }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">SIMPER TERSEDIA</p>
        </div>

        <!-- 4. SIMPER Kedaluwarsa (Pastel Soft Blue) -->
        <div>
            <div class="bg-[#f0f4fe] border border-[#dae5fd] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#2563eb]">{{ $expiredSimperCount }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">SIMPER KEDALUWARSA</p>
        </div>

        <!-- 5. Bukan Driver (Pastel Amber / Yellow) -->
        <div>
            <div class="bg-[#fef9e8] border border-[#feefc7] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#f59e0b]">{{ $nonDriversCount }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">BUKAN DRIVER</p>
        </div>
    </div>

    <!-- Layout 2 Kolom Bawah: Log Driver & Mutasi SIMPER (Persis Gambar Referensi) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        
        <!-- Kolom Kiri: Log Driver & SIMPER Terkini (Format Log Serah Terima) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-sm font-bold text-slate-900">Log Driver & SIMPER Terkini</h3>
                <a href="{{ route('hse.employees.index') }}" class="text-xs font-bold text-[#1b5e20] hover:underline">
                    Lihat Semua
                </a>
            </div>

            <div class="space-y-4">
                @forelse($recentDrivers as $driver)
                    <div class="flex items-start space-x-3.5 py-2 border-b border-slate-50 last:border-b-0">
                        <!-- Avatar Inisial Bulat Hijau Pastel -->
                        <div class="w-9 h-9 rounded-full bg-[#eef7ee] text-[#1b5e20] font-bold text-xs flex items-center justify-center flex-shrink-0 mt-0.5">
                            {{ strtoupper(substr($driver->name, 0, 1)) }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-slate-900 leading-tight">
                                {{ $driver->nik }} <span class="text-slate-400 font-normal">driver</span> {{ $driver->name }}
                            </p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                {{ $driver->position }} &bull; {{ $driver->division }}
                            </p>
                            <p class="text-[10px] text-slate-400 mt-0.5">
                                SIMPER: {{ $driver->simper_expired_at ? $driver->simper_expired_at->format('d M Y') : 'Tidak Ada Tanggal' }}
                            </p>
                        </div>

                        <div class="flex-shrink-0">
                            @if($driver->hasValidSimper())
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    Kedaluwarsa
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-6">Belum ada data driver.</p>
                @endforelse
            </div>
        </div>

        <!-- Kolom Kanan: 2 Kartu Stacked (Format Log Mutasi & Log Pengembalian) -->
        <div class="space-y-6">
            
            <!-- Kartu Atas: Log Status SIMPER (Format Log Mutasi) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-bold text-slate-900">Log Status SIMPER</h3>
                    <a href="{{ route('hse.employees.index') }}" class="text-xs font-bold text-[#1b5e20] hover:underline">
                        Lihat Semua
                    </a>
                </div>

                <div class="space-y-4">
                    @forelse($simperLogs as $log)
                        <div class="flex items-start space-x-3.5 py-2 border-b border-slate-50 last:border-b-0">
                            <!-- Icon Bulat Biru Pastel (Swap / Arrow Icon) -->
                            <div class="w-9 h-9 rounded-full bg-[#edf5fd] text-[#1976d2] flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-slate-900 leading-tight">
                                    {{ $log->nik }} &rarr; {{ $log->name }}
                                </p>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Divisi {{ $log->division }}
                                </p>
                                <p class="text-[10px] text-slate-400 mt-0.5">
                                    SIMPER: {{ $log->simper_expired_at ? $log->simper_expired_at->format('d M Y') : 'Belum Diatur' }}
                                </p>
                            </div>

                            <div class="flex-shrink-0">
                                @if($log->hasValidSimper())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700">
                                        Valid
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700">
                                        Kedaluwarsa
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">Belum ada riwayat SIMPER.</p>
                    @endforelse
                </div>
            </div>

            <!-- Kartu Bawah: Log Karyawan Non-Driver (Format Log Pengembalian) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-bold text-slate-900">Log Karyawan Non-Driver</h3>
                    <a href="{{ route('hse.employees.index') }}" class="text-xs font-bold text-[#1b5e20] hover:underline">
                        Lihat Semua
                    </a>
                </div>

                <div class="space-y-4">
                    @forelse($nonDrivers as $staff)
                        <div class="flex items-start space-x-3.5 py-2 border-b border-slate-50 last:border-b-0">
                            <!-- Icon Bulat Netral -->
                            <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 font-bold text-xs flex items-center justify-center flex-shrink-0 mt-0.5">
                                {{ strtoupper(substr($staff->name, 0, 1)) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-slate-900 leading-tight">
                                    {{ $staff->nik }} &bull; {{ $staff->name }}
                                </p>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    {{ $staff->position }} &bull; {{ $staff->division }}
                                </p>
                                <p class="text-[10px] text-slate-400 mt-0.5">
                                    Unit Kerja: {{ $staff->unit_kerja }}
                                </p>
                            </div>

                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">
                                    Non-Driver
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
@endsection
