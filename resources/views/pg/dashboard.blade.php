@extends('layouts.app')

@section('title', 'Dashboard - Admin Private Guard | PT Kerinci Merangin Hidro')

@section('content')
<div class="space-y-6">

    <!-- Header Admin PG (Persis Gaya Referensi IT KMH) -->
    <div>
        <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20] block mb-1">
            SISTEM ADMINISTRATOR
        </span>
        <div class="flex items-center space-x-2.5">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                Selamat datang, {{ auth()->user()->name }}
            </h2>
            <span class="w-5 h-5 rounded-full bg-slate-200/90 text-slate-500 text-xs inline-flex items-center justify-center font-bold shadow-sm" title="Dashboard Operasional Admin Private Guard">
                <i class="fa-solid fa-info text-[10px]"></i>
            </span>
        </div>
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
                <a href="{{ route('pg.logs.index') }}" class="text-xs font-bold text-[#1b5e20] hover:underline">
                    Lihat Semua
                </a>
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
                    <a href="{{ route('pg.guards.index') }}" class="text-xs font-bold text-[#1b5e20] hover:underline">
                        Lihat Semua
                    </a>
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
@endsection
