@extends('layouts.app')

@section('title', 'Dashboard Karyawan - Sistem RKM | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Halaman Simple Kayak Satpam (Seamless, Tanpa Card Wrapper) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/80">
        <div class="min-w-0">
            <div class="flex items-center flex-wrap gap-2.5">
                <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                    Dashboard Karyawan
                </h1>
                @if($employee && $employee->hasValidSimper())
                    <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                        SIMPER Aktif
                    </span>
                @elseif($employee)
                    <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-rose-50 text-rose-700 border border-rose-200">
                        SIMPER Exp
                    </span>
                @endif
            </div>
            <div class="text-sm sm:text-base text-slate-600 font-medium truncate mt-1 flex items-center flex-wrap gap-1.5">
                <span>Karyawan: <strong class="text-slate-900 font-bold">{{ $employee ? $employee->name : auth()->user()->name }}</strong></span>
                @if($employee && $employee->nik)
                    <span class="text-slate-500 font-mono text-xs sm:text-sm font-semibold">({{ $employee->nik }})</span>
                @endif
                @if($employee && $employee->division)
                    <span class="text-slate-400">&bull;</span>
                    <span class="text-slate-500 text-xs sm:text-sm">{{ $employee->division }}</span>
                @endif
            </div>
        </div>

        @if($employee && $employee->simper_expired_at)
            <div class="text-xs text-slate-500 font-medium self-start sm:self-center">
                Masa Berlaku SIMPER: <span class="font-bold text-slate-800">{{ $employee->simper_expired_at->format('d/m/Y') }}</span>
            </div>
        @endif
    </div>

    <!-- 2 Card Fitur: Pemeriksaan P2H dan Timesheet -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
        <!-- Card 1: Pemeriksaan P2H -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition flex flex-col justify-between group">
            <div>
                <div class="flex items-center justify-end mb-4">
                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                        Unit Support / Alat Berat
                    </span>
                </div>
                <h2 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight mb-1">
                    Pemeriksaan Pra-Harian (P2H)
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed mb-4">
                    Pengisian formulir checklist kelaikan operasi harian sebelum mengoperasikan alat berat support unit (Excavator, Crane, Bulldozer, dan unit support lainnya).
                </p>
                <div class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100 flex items-center justify-between">
                    <span class="text-slate-400">Total Unit Terdaftar:</span>
                    <span class="font-extrabold text-[#1b5e20]">{{ $totalSupport }} Unit Support</span>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100">
                <a href="{{ route('p2h.index') }}" class="w-full bg-[#1b5e20] hover:bg-[#144918] active:scale-[0.99] text-white font-bold py-3 px-4 rounded-xl shadow-sm transition flex items-center justify-center space-x-2 text-xs sm:text-sm">
                    <span>Buka Modul Pemeriksaan P2H</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>
        </div>

        <!-- Card 2: Timesheet Kendaraan Ringan (LV) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition flex flex-col justify-between group">
            <div>
                <div class="flex items-center justify-end mb-4">
                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        Kendaraan Ringan (LV)
                    </span>
                </div>
                <h2 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight mb-1">
                    Timesheet Kendaraan Ringan
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed mb-4">
                    Pencatatan aktivitas mobilisasi harian light vehicle (LV), odometer kilometer (KM), tujuan penugasan operasional, dan verifikasi pos gerbang.
                </p>
                <div class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100 flex items-center justify-between">
                    <span class="text-slate-400">Armada Tersedia:</span>
                    <span class="font-extrabold text-[#1b5e20]">{{ $totalLv }} Unit Kendaraan Ringan</span>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100">
                <a href="{{ route('timesheet.index') }}" class="w-full bg-[#1b5e20] hover:bg-[#144918] active:scale-[0.99] text-white font-bold py-3 px-4 rounded-xl shadow-sm transition flex items-center justify-center space-x-2 text-xs sm:text-sm">
                    <span>Buka Modul Timesheet LV</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
