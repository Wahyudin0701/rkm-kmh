@extends('layouts.app')

@section('title', 'Dashboard Karyawan - Sistem RKM | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Halaman Simple Kayak Satpam (Seamless, Tanpa Card Wrapper) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
        <div class="flex items-center space-x-3.5 min-w-0">
            @php
                $empPhoto = $employee?->photo_url ?: auth()->user()->photo_url;
                $empName = $employee ? $employee->name : auth()->user()->name;
            @endphp
            @if($empPhoto)
                <img src="{{ $empPhoto }}" alt="{{ $empName }}" class="w-12 h-12 rounded-2xl object-cover ring-2 ring-emerald-500/20 shadow-sm flex-shrink-0">
            @else
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-700 to-[#1b5e20] text-white font-black text-lg flex items-center justify-center ring-2 ring-emerald-500/20 shadow-sm flex-shrink-0">
                    {{ strtoupper(substr($empName, 0, 1)) }}
                </div>
            @endif
            <div class="min-w-0">
                <div class="flex items-center flex-wrap gap-2.5">
                    <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                        {{ $empName }}
                    </h1>
                    @if($employee && $employee->hasValidSimper())
                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                            SIMPER Aktif
                        </span>
                    @elseif($employee)
                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-black bg-rose-50 text-rose-700 border border-rose-200">
                            SIMPER Exp
                        </span>
                    @endif
                </div>
                <div class="text-xs sm:text-sm text-slate-500 font-medium truncate mt-0.5 flex items-center flex-wrap gap-1.5">
                    @if($employee && $employee->nik)
                        <span class="text-slate-700 font-mono font-semibold">NIK: {{ $employee->nik }}</span>
                    @endif
                    @if($employee && $employee->division)
                        <span class="text-slate-300">&bull;</span>
                        <span class="text-slate-600">{{ $employee->division }}</span>
                    @endif
                    @if($employee && $employee->position)
                        <span class="text-slate-300">&bull;</span>
                        <span class="text-slate-600 font-semibold">{{ $employee->position }}</span>
                    @endif
                </div>
            </div>
        </div>

        @if($employee && $employee->simper_expired_at)
            <div class="text-xs text-slate-500 font-medium self-start sm:self-center bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200/60">
                Masa Berlaku SIMPER: <span class="font-bold text-slate-800">{{ $employee->simper_expired_at->format('d/m/Y') }}</span>
            </div>
        @endif
    </div>

    {{-- CARD PERJALANAN AKTIF (MENUNGGU PEMERIKSAAN SATPAM) --}}
    @if(isset($pendingActivity) && $pendingActivity)
    <div id="pendingTripCard" 
         data-activity-id="{{ $pendingActivity->id }}"
         class="bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-white border-2 border-amber-300/80 rounded-2xl p-5 sm:p-6 shadow-sm relative overflow-hidden transition-all duration-300">
        {{-- Dekorasi Latar Belakang --}}
        <div class="absolute -right-6 -bottom-6 text-amber-100/50 pointer-events-none text-9xl">
            <i class="fa-solid fa-car-side"></i>
        </div>

        <div class="relative z-10 space-y-4">
            {{-- Header Card Perjalanan --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-3 border-b border-amber-200/60">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                        <i class="fa-solid fa-route"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-900">Perjalanan Aktif</span>
                            <span class="text-[11px] font-mono font-bold text-amber-700 bg-amber-100/80 px-2 py-0.5 rounded-md border border-amber-200">
                                {{ $pendingActivity->transaction_no }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Input: {{ $pendingActivity->recorded_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</p>
                    </div>
                </div>

                <div class="self-start sm:self-center flex flex-wrap items-center gap-2">
                    <!-- Timer Countdown Sisa Waktu 5 Jam -->
                    <div id="pendingTripCountdownContainer"
                         data-expires-at="{{ $pendingActivity->expires_at?->toISOString() }}"
                         class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-bold bg-white text-rose-700 border border-rose-300 shadow-2xs">
                        <i class="fa-solid fa-stopwatch mr-1.5 text-rose-600 animate-pulse"></i>
                        <span>Sisa: <strong id="tripCountdownTimerText">--:--:--</strong></span>
                    </div>

                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping mr-1.5"></span>
                        <span class="w-2 h-2 rounded-full bg-amber-500 mr-1.5 -ml-3.5"></span>
                        Menunggu Pemeriksaan Satpam
                    </span>

                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-2xs" title="Sistem memantau persetujuan satpam secara otomatis tanpa refresh">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse mr-1.5"></span>
                        <span>Auto-Check Aktif</span>
                    </span>
                </div>
            </div>

            {{-- Visual Foto Mobil LV & Foto Driver --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Foto Unit Kendaraan (LV) -->
                <div class="flex items-center space-x-3 bg-white/95 p-3 rounded-xl border border-amber-200/80 shadow-2xs">
                    @if($pendingActivity->vehicle && $pendingActivity->vehicle->photo_url)
                        <img src="{{ $pendingActivity->vehicle->photo_url }}" alt="{{ $pendingActivity->vehicle->brand_type }}" class="w-14 h-14 rounded-xl object-cover ring-2 ring-blue-500/20 shadow-2xs flex-shrink-0">
                    @else
                        <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-xl flex-shrink-0 ring-1 ring-blue-200">
                            <i class="fa-solid fa-car"></i>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Unit Kendaraan:</span>
                        <p class="text-xs font-black text-slate-900 truncate">{{ $pendingActivity->vehicle->brand_type }}</p>
                        <p class="text-[11px] font-mono font-bold text-amber-800">{{ $pendingActivity->vehicle->code }} &bull; {{ $pendingActivity->vehicle->license_plate ?? 'Tanpa Plat' }}</p>
                    </div>
                </div>

                <!-- Foto Pengemudi / Driver -->
                <div class="flex items-center space-x-3 bg-white/95 p-3 rounded-xl border border-amber-200/80 shadow-2xs">
                    @php $driverPhoto = $pendingActivity->driver?->photo_url ?: auth()->user()->photo_url; @endphp
                    @if($driverPhoto)
                        <img src="{{ $driverPhoto }}" alt="{{ $pendingActivity->driver->name }}" class="w-14 h-14 rounded-xl object-cover ring-2 ring-emerald-500/20 shadow-2xs flex-shrink-0">
                    @else
                        <div class="w-14 h-14 rounded-xl bg-emerald-50 text-[#1b5e20] flex items-center justify-center text-lg font-black flex-shrink-0 ring-1 ring-emerald-200">
                            {{ strtoupper(substr($pendingActivity->driver->name ?? 'D', 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Driver Terverifikasi:</span>
                        <p class="text-xs font-black text-slate-900 truncate">{{ $pendingActivity->driver->name }}</p>
                        <p class="text-[11px] text-slate-500 truncate">NIK: {{ $pendingActivity->driver->nik ?? '-' }} &bull; {{ $pendingActivity->driver->division ?? 'Operasional' }}</p>
                    </div>
                </div>
            </div>

            {{-- Grid Rincian Perjalanan --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-white/90 p-4 rounded-xl border border-amber-200/70 text-xs shadow-xs">
                <div>
                    <span class="text-[11px] text-slate-400 font-medium block">Kendaraan (Unit):</span>
                    <span class="font-bold text-slate-900 text-sm font-mono block mt-0.5">{{ $pendingActivity->vehicle->code }}</span>
                    <span class="text-[10px] text-slate-500 block truncate">{{ $pendingActivity->vehicle->brand_type }}</span>
                </div>
                <div>
                    <span class="text-[11px] text-slate-400 font-medium block">Plat Nomor:</span>
                    <span class="font-bold text-amber-800 text-sm font-mono block mt-0.5">{{ $pendingActivity->vehicle->license_plate ?? '-' }}</span>
                    <span class="text-[10px] text-slate-500 block">{{ $pendingActivity->gate->name }}</span>
                </div>
                <div>
                    <span class="text-[11px] text-slate-400 font-medium block">Arah Gerbang:</span>
                    <div class="mt-1">
                        @if($pendingActivity->direction === 'in')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                                <i class="fa-solid fa-arrow-right-to-bracket mr-1"></i> MASUK
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black bg-blue-100 text-blue-800 border border-blue-300">
                                <i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> KELUAR
                            </span>
                        @endif
                    </div>
                    <span class="text-[10px] text-slate-400 block mt-0.5">KM Awal: {{ number_format($pendingActivity->previous_km, 0, ',', '.') }}</span>
                </div>
                <div>
                    <span class="text-[11px] text-slate-400 font-medium block">KM Input Saat Ini:</span>
                    <span class="font-bold text-emerald-700 text-sm font-mono block mt-0.5">{{ number_format($pendingActivity->current_km, 0, ',', '.') }} KM</span>
                    <span class="text-[10px] font-bold text-emerald-600 block">Jarak: +{{ number_format($pendingActivity->distance, 0, ',', '.') }} KM</span>
                </div>
            </div>

            {{-- Keperluan Perjalanan --}}
            <div class="bg-amber-50/60 p-3 rounded-xl border border-amber-200/50 text-xs">
                <span class="font-bold text-slate-700">Keperluan:</span>
                <span class="text-slate-600 ml-1">{{ $pendingActivity->purpose }}</span>
            </div>

            {{-- Petunjuk & Tombol Aksi Driver --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                <div class="text-[11px] text-slate-500 flex items-center">
                    <i class="fa-solid fa-circle-info text-amber-600 mr-1.5 flex-shrink-0"></i>
                    <span>Tunjukkan data perjalanan ini ke petugas Satpam di pos gerbang untuk verifikasi fisik speedometer.</span>
                </div>

                <div class="flex items-center space-x-2 self-end sm:self-auto flex-shrink-0">
                    {{-- Tombol Koreksi KM Driver --}}
                    <button type="button" 
                            onclick="openDriverEditKmModal({{ json_encode([
                                'id' => $pendingActivity->id,
                                'trx' => $pendingActivity->transaction_no,
                                'code' => $pendingActivity->vehicle->code,
                                'previous_km' => (float)$pendingActivity->previous_km,
                                'current_km' => (float)$pendingActivity->current_km,
                                'distance' => (float)$pendingActivity->distance,
                            ]) }})"
                            class="inline-flex items-center space-x-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 hover:text-slate-900 shadow-xs transition cursor-pointer">
                        <i class="fa-solid fa-pen-to-square text-amber-600"></i>
                        <span>Koreksi Angka KM</span>
                    </button>

                    {{-- Tombol Batalkan Driver --}}
                    <button type="button"
                            onclick="openDriverCancelModal({{ json_encode([
                                'id' => $pendingActivity->id,
                                'trx' => $pendingActivity->transaction_no,
                                'code' => $pendingActivity->vehicle->code,
                            ]) }})"
                            class="inline-flex items-center space-x-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                        <span>Batalkan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- 2 Card Fitur: Pemeriksaan P2H dan Timesheet (Dikontrol Hak Akses RBAC) --}}
    @if(auth()->user()->can('employee.p2h') || auth()->user()->can('employee.timesheet'))
    <div class="grid grid-cols-1 {{ auth()->user()->can('employee.p2h') && auth()->user()->can('employee.timesheet') ? 'md:grid-cols-2' : '' }} gap-5 sm:gap-6">
        {{-- Card 1: Pemeriksaan P2H --}}
        @can('employee.p2h')
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
        @endcan

        <!-- Card 2: Timesheet Kendaraan Ringan (LV) -->
        @can('employee.timesheet')
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

            <div class="mt-6 pt-4 border-t border-slate-100 space-y-2">
                <button type="button" 
                        onclick="openUnitQrScannerModal()" 
                        class="w-full bg-[#1b5e20] hover:bg-[#144918] active:scale-[0.99] text-white font-bold py-3 px-4 rounded-xl shadow-sm transition flex items-center justify-center space-x-2 text-xs sm:text-sm cursor-pointer">
                    <i class="fa-solid fa-qrcode text-base"></i>
                    <span>Scan Barcode Unit Mobil</span>
                </button>
                <a href="{{ route('timesheet.index') }}" class="w-full py-2 px-3 text-center block text-xs text-slate-500 hover:text-slate-800 font-semibold rounded-lg hover:bg-slate-50 transition">
                    Atau Pilih Manual dari Katalog Unit &rarr;
                </a>
            </div>
        </div>
        @endcan
    </div>
    @else
    <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center shadow-sm">
        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl mb-3">
            <i class="fa-solid fa-lock"></i>
        </div>
        <h3 class="text-base font-bold text-slate-800">Modul Kerja Mandiri Tidak Aktif</h3>
        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
            Akun Anda saat ini tidak memiliki izin akses untuk pengisian formulir Pemeriksaan P2H maupun Timesheet. Silakan hubungi Administrator jika membutuhkan akses operasional ini.
        </p>
    </div>
    @endif

    {{-- TABEL / RIWAYAT PERJALANAN SAYA (BUKTI DIGITAL PENGEMUDI) --}}
    @if(isset($myRecentActivities) && $myRecentActivities->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-sm font-bold flex-shrink-0">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-black text-slate-900 tracking-tight">Riwayat Perjalanan Terakhir Saya</h3>
                    <p class="text-xs text-slate-500">Catatan mobilisasi kendaraan yang pernah Anda isi di sistem.</p>
                </div>
            </div>
            <span class="text-xs font-semibold text-slate-400 self-start sm:self-center">
                {{ $myRecentActivities->count() }} Perjalanan Terbaru
            </span>
        </div>

        <div class="overflow-x-auto -mx-5 sm:mx-0">
            <table class="w-full text-xs text-left min-w-[640px]">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <th class="py-2.5 px-4">Waktu</th>
                        <th class="py-2.5 px-4">Unit Kendaraan</th>
                        <th class="py-2.5 px-4">Pos Gerbang</th>
                        <th class="py-2.5 px-4 text-center">Arah</th>
                        <th class="py-2.5 px-4 text-right">Odometer & Jarak</th>
                        <th class="py-2.5 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach($myRecentActivities as $act)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-bold text-slate-900 block">{{ $act->recorded_at->locale('id')->translatedFormat('d M Y') }}</span>
                            <span class="text-[11px] text-slate-400 block">{{ $act->recorded_at->format('H:i') }} WIB</span>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-bold text-slate-800 font-mono text-xs block">{{ $act->vehicle->code }}</span>
                            <span class="text-[11px] text-slate-500 block truncate max-w-[140px]">{{ $act->vehicle->brand_type }}</span>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-slate-600 font-medium">
                            {{ $act->gate->name }}
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            @if($act->direction === 'in')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    MASUK
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    KELUAR
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right whitespace-nowrap font-mono">
                            <div class="font-bold text-slate-900">{{ number_format($act->current_km, 0, ',', '.') }} KM</div>
                            <div class="text-[11px] text-emerald-600 font-semibold">+{{ number_format($act->distance, 0, ',', '.') }} KM</div>
                            @if($act->is_corrected)
                                <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-200" title="Dikoreksi QMA: {{ $act->correction_reason }}">
                                    Terkoreksi QMA
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            @if($act->validation_status === 'approved')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Divalidasi oleh: {{ $act->securityGuard->name ?? 'Satpam' }}">
                                    <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i> Disetujui
                                </span>
                            @elseif($act->validation_status === 'cancelled')
                                @if($act->isCancelledBySystem())
                                    <div class="inline-flex flex-col items-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200" title="{{ $act->rejection_note }}">
                                            <i class="fa-solid fa-robot mr-1 text-amber-600"></i> Batal Sistem
                                        </span>
                                        <span class="text-[9px] text-amber-600 mt-0.5 font-medium">Timeout > 5 Jam</span>
                                    </div>
                                @else
                                    <div class="inline-flex flex-col items-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200" title="{{ $act->rejection_note }}">
                                            <i class="fa-solid fa-user-xmark mr-1 text-slate-500"></i> Batal Pengemudi
                                        </span>
                                        @if($act->rejection_note && $act->rejection_note !== 'Dibatalkan mandiri oleh pengemudi')
                                            <span class="text-[9px] text-slate-500 mt-0.5 truncate max-w-[120px]" title="{{ $act->rejection_note }}">{{ $act->rejection_note }}</span>
                                        @endif
                                    </div>
                                @endif
                            @elseif($act->validation_status === 'rejected')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="Alasan: {{ $act->rejection_note }}">
                                    <i class="fa-solid fa-circle-xmark mr-1 text-rose-600"></i> Ditolak
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 animate-pulse">
                                    <i class="fa-solid fa-clock mr-1 text-amber-600"></i> Menunggu Satpam
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

{{-- MODAL 1: KOREKSI ODOMETER DRIVER (HANYA STATUS PENDING) --}}
<div id="driverEditKmModal" 
     onclick="if(event.target === this) closeDriverEditKmModal()" 
     class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto" 
     aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="relative z-10 bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-8 max-w-lg w-full border border-slate-200 animate-scale-in">
        <form id="driverEditKmForm" method="POST" action="">
            @csrf
            <div class="p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-base font-bold flex-shrink-0 border border-amber-200">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 text-sm sm:text-base">Koreksi Angka Odometer (KM)</h3>
                            <p class="text-xs text-slate-500">Perbaiki angka KM sebelum diverifikasi oleh Satpam di pos gerbang.</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeDriverEditKmModal()" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                {{-- Info Unit & Perjalanan --}}
                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs space-y-1.5 font-medium text-slate-600">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Nomor Perjalanan:</span>
                        <span id="modalEditTrxNo" class="font-mono font-bold text-slate-800">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Unit Kendaraan:</span>
                        <span id="modalEditVehicleCode" class="font-mono font-bold text-slate-800">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">KM Awal Sebelumnya:</span>
                        <span id="modalEditPreviousKmText" class="font-mono font-bold text-slate-800">- KM</span>
                    </div>
                </div>

                {{-- Input Angka KM Baru --}}
                <div>
                    <label for="driverInputCurrentKm" class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Angka Odometer Saat Ini (KM) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" 
                               step="0.01" 
                               name="current_km" 
                               id="driverInputCurrentKm" 
                               required 
                               oninput="calculateDriverDistancePreview()"
                               placeholder="Contoh: 50150"
                               class="w-full pl-3.5 pr-14 py-2.5 rounded-xl border border-slate-300 text-sm font-mono font-bold text-slate-800 focus:border-[#1b5e20] focus:ring-3 focus:ring-[#1b5e20]/10 transition">
                        <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-xs font-bold text-slate-400">
                            KM
                        </span>
                    </div>
                    <input type="hidden" id="driverModalPreviousKm" value="0">
                </div>

                {{-- Live Preview Kalkulasi Jarak Tempuh --}}
                <div id="driverDistancePreviewBox" class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs flex items-center justify-between">
                    <span class="text-emerald-800 font-medium">Estimasi Jarak Tempuh Baru:</span>
                    <span id="driverDistancePreviewValue" class="font-mono font-bold text-emerald-800 text-sm">+0 KM</span>
                </div>

                {{-- Warning jika angka lebih kecil dari KM awal --}}
                <div id="driverDistanceErrorBox" class="hidden p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 font-medium">
                    <i class="fa-solid fa-triangle-exclamation mr-1 text-rose-600"></i>
                    <span>Angka KM baru tidak boleh lebih kecil dari KM awal (<span id="driverDistanceMinText">0</span> KM).</span>
                </div>
            </div>

            <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" onclick="closeDriverEditKmModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" id="btnSubmitDriverEditKm" class="px-5 py-2 text-xs font-bold text-white bg-[#1b5e20] hover:bg-[#144918] active:scale-[0.99] rounded-xl shadow-xs transition flex items-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-check text-xs"></i>
                    <span>Simpan Perbaikan Odometer</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 2: KONFIRMASI BATALKAN PERJALANAN --}}
<div id="driverCancelModal" 
     onclick="if(event.target === this) closeDriverCancelModal()" 
     class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto" 
     aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="relative z-10 bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-8 max-w-md w-full border border-slate-200 animate-scale-in">
        <form id="driverCancelForm" method="POST" action="">
            @csrf
            <div class="p-6 space-y-4">
                <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-xl mx-auto">
                    <i class="fa-solid fa-trash-can"></i>
                </div>

                <div class="text-center space-y-1">
                    <h3 class="font-bold text-slate-900 text-base">Batalkan Perjalanan?</h3>
                    <p class="text-xs text-slate-500">Perjalanan untuk unit <strong id="modalCancelVehicleCode" class="text-slate-800 font-mono">-</strong> (<span id="modalCancelTrxNo">-</span>) akan dibatalkan dan dihapus dari antrean pos Satpam.</p>
                </div>

                <div>
                    <label for="cancelReasonInput" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Alasan Pembatalan (Opsional)
                    </label>
                    <select name="cancel_reason" id="cancelReasonInput" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 text-slate-800 focus:outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/10">
                        <option value="Dibatalkan mandiri oleh pengemudi (Batal jalan)">Batal melakukan perjalanan</option>
                        <option value="Salah input unit mobil">Salah memilih unit mobil</option>
                        <option value="Perubahan pengemudi/tugas">Perubahan penugasan kerja</option>
                        <option value="Salah input form / input ulang">Ingin mengisi ulang formulir dari awal</option>
                    </select>
                </div>
            </div>

            <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" onclick="closeDriverCancelModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 rounded-xl transition cursor-pointer">
                    Kembali
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:scale-[0.99] rounded-xl shadow-xs transition flex items-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-ban text-xs"></i>
                    <span>Ya, Batalkan Perjalanan</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 3: IN-APP CAMERA SCANNER QR CODE / BARCODE UNIT LV --}}
<div id="unitQrScannerModal" 
     onclick="if(event.target === this) closeUnitQrScannerModal()" 
     class="hidden fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto" 
     aria-labelledby="qr-scanner-title" role="dialog" aria-modal="true">
    <div class="relative z-10 bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-8 max-w-md w-full border border-slate-200 animate-scale-in">
        <!-- Header Modal Scanner -->
        <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-sm flex-shrink-0">
                    <i class="fa-solid fa-qrcode"></i>
                </div>
                <div>
                    <h3 id="qr-scanner-title" class="font-bold text-sm text-white">Scan Barcode Unit Mobil</h3>
                    <p class="text-[11px] text-slate-400">Arahkan kamera ke stiker barcode di kaca unit</p>
                </div>
            </div>
            <button type="button" onclick="closeUnitQrScannerModal()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Area Viewport Kamera -->
        <div class="p-4 bg-slate-950 flex flex-col items-center justify-center relative min-h-[300px]">
            <div id="qr-reader" class="w-full rounded-xl overflow-hidden bg-black"></div>
            
            <!-- Loading Indicator -->
            <div id="qrScannerLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/95 text-white space-y-2.5 z-20">
                <i class="fa-solid fa-circle-notch fa-spin text-2xl text-emerald-500"></i>
                <span class="text-xs text-slate-300 font-medium">Menghubungkan ke kamera perangkat...</span>
            </div>

            <!-- Error State (Izin Kamera ditolak / tidak tersedia) -->
            <div id="qrScannerError" class="hidden absolute inset-0 p-6 flex flex-col items-center justify-center bg-slate-950 text-center text-white space-y-3 z-30">
                <div class="w-12 h-12 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-video-slash"></i>
                </div>
                <h4 class="font-bold text-sm text-rose-200">Kamera Tidak Dapat Dibuka</h4>
                <p class="text-xs text-slate-400" id="qrScannerErrorMsg">Pastikan Anda telah memberikan izin akses kamera (Camera Permission) di peramban browser Anda.</p>
                <div class="flex items-center space-x-2 pt-2">
                    <button type="button" onclick="startQrScanner()" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-600 text-xs font-bold rounded-xl transition cursor-pointer">
                        <i class="fa-solid fa-rotate-right mr-1.5"></i> Coba Buka Lagi
                    </button>
                    <a href="{{ route('timesheet.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-bold rounded-xl transition">
                        Pilih Manual
                    </a>
                </div>
            </div>

            <!-- Overlay Sukses Terdeteksi -->
            <div id="qrScannerSuccessOverlay" class="hidden absolute inset-0 flex flex-col items-center justify-center bg-emerald-950/90 text-white space-y-2 z-40">
                <div class="w-12 h-12 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <span class="text-sm font-bold text-emerald-200">Unit Terdeteksi! Membuka Timesheet...</span>
            </div>
        </div>

        <!-- Footer / Opsi Manual -->
        <div class="p-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
            <span class="text-[11px] text-slate-500 flex items-center">
                <i class="fa-solid fa-circle-info text-emerald-600 mr-1.5"></i>
                Kamera buram / kendala scan?
            </span>
            <a href="{{ route('timesheet.index') }}" class="text-xs font-bold text-[#1b5e20] hover:text-[#144918] hover:underline flex-shrink-0">
                Pilih Manual dari Katalog &rarr;
            </a>
        </div>
    </div>
</div>

{{-- MODAL 4: NOTIFIKASI HASIL VALIDASI POS GERBANG (POLLING REAL-TIME) --}}
<div id="validationNotificationModal" 
     class="hidden fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
     aria-labelledby="validationNotificationTitle" role="dialog" aria-modal="true">
    <div id="validationNotificationCard" class="relative z-10 bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all duration-300 scale-95">
        <div id="validationNotificationBanner" class="p-6 text-center text-white bg-gradient-to-br from-emerald-600 to-[#1b5e20]">
            <div id="validationNotificationIcon" class="w-16 h-16 mx-auto rounded-full bg-white/20 flex items-center justify-center text-3xl mb-3 shadow-inner">
                <i class="fa-solid fa-circle-check text-emerald-100"></i>
            </div>
            <h3 id="validationNotificationTitle" class="text-lg font-black tracking-tight">Perjalanan Disetujui!</h3>
            <p id="validationNotificationSubtitle" class="text-xs text-white/90 mt-1">Verifikasi di Pos Gerbang Berhasil</p>
        </div>

        <div class="p-6 space-y-4">
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/80 text-xs space-y-2.5">
                <div class="flex justify-between items-center pb-2 border-b border-slate-200/60">
                    <span class="text-slate-400 font-medium">No. Transaksi:</span>
                    <span id="validationTrxNo" class="font-mono font-bold text-slate-800">-</span>
                </div>
                <div class="flex justify-between items-center pb-2 border-b border-slate-200/60">
                    <span class="text-slate-400 font-medium">Unit Kendaraan:</span>
                    <span id="validationVehicle" class="font-bold text-slate-800">-</span>
                </div>
                <div class="flex justify-between items-center pb-2 border-b border-slate-200/60">
                    <span class="text-slate-400 font-medium">Pos Gerbang:</span>
                    <span id="validationGate" class="font-bold text-slate-800">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 font-medium">Petugas Satpam:</span>
                    <span id="validationGuard" class="font-bold text-slate-800">-</span>
                </div>
                <div id="validationNoteRow" class="pt-2 border-t border-slate-200 hidden">
                    <span class="text-slate-400 font-medium block mb-1">Catatan Penolakan:</span>
                    <p id="validationNote" class="text-slate-700 italic bg-white p-2.5 rounded-lg border border-slate-200 text-[11px]">-</p>
                </div>
            </div>

            <button type="button" 
                    id="validationDismissBtn"
                    onclick="handleValidationDismiss()" 
                    class="w-full bg-[#1b5e20] hover:bg-[#144918] active:scale-[0.99] text-white font-bold py-3 px-4 rounded-xl text-xs sm:text-sm shadow-md transition flex items-center justify-center space-x-2 cursor-pointer">
                <span>Lanjutkan ke Dashboard</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
        </div>
    </div>
</div>

<style>
    #qr-reader {
        border: none !important;
    }
    #qr-reader video {
        width: 100% !important;
        height: auto !important;
        border-radius: 0.75rem;
        object-fit: cover;
    }
    #qr-reader__scan_region {
        background: transparent !important;
    }
    #qr-reader__scan_region video {
        border-radius: 0.75rem;
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>
    function openDriverEditKmModal(data) {
        document.getElementById('modalEditTrxNo').innerText = data.trx;
        document.getElementById('modalEditVehicleCode').innerText = data.code;
        document.getElementById('modalEditPreviousKmText').innerText = Number(data.previous_km).toLocaleString('id-ID') + ' KM';
        document.getElementById('driverModalPreviousKm').value = data.previous_km;
        document.getElementById('driverInputCurrentKm').value = data.current_km;
        document.getElementById('driverDistanceMinText').innerText = Number(data.previous_km).toLocaleString('id-ID');

        const form = document.getElementById('driverEditKmForm');
        form.action = '/karyawan/aktivitas/' + data.id + '/update-km';

        calculateDriverDistancePreview();
        document.getElementById('driverEditKmModal').classList.remove('hidden');
    }

    function closeDriverEditKmModal() {
        document.getElementById('driverEditKmModal').classList.add('hidden');
    }

    function calculateDriverDistancePreview() {
        const prevKm = parseFloat(document.getElementById('driverModalPreviousKm').value) || 0;
        const inputVal = parseFloat(document.getElementById('driverInputCurrentKm').value) || 0;
        const diff = inputVal - prevKm;

        const previewBox = document.getElementById('driverDistancePreviewBox');
        const errorBox = document.getElementById('driverDistanceErrorBox');
        const submitBtn = document.getElementById('btnSubmitDriverEditKm');
        const valText = document.getElementById('driverDistancePreviewValue');

        if (inputVal < prevKm) {
            previewBox.classList.add('hidden');
            errorBox.classList.remove('hidden');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            previewBox.classList.remove('hidden');
            errorBox.classList.add('hidden');
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            valText.innerText = '+' + diff.toLocaleString('id-ID') + ' KM';
        }
    }

    function openDriverCancelModal(data) {
        document.getElementById('modalCancelTrxNo').innerText = data.trx;
        document.getElementById('modalCancelVehicleCode').innerText = data.code;

        const form = document.getElementById('driverCancelForm');
        form.action = '/karyawan/aktivitas/' + data.id + '/cancel';

        document.getElementById('driverCancelModal').classList.remove('hidden');
    }

    function closeDriverCancelModal() {
        document.getElementById('driverCancelModal').classList.add('hidden');
    }

    // --- IN-APP QR CODE SCANNER LOGIC ---
    let html5QrScanner = null;
    let isScanProcessing = false;

    function parseScannedCode(decodedText) {
        if (!decodedText) return null;
        decodedText = decodedText.trim();

        // Check if it's already a full URL
        try {
            const url = new URL(decodedText);
            if (url.pathname.includes('/gate-activity/')) {
                return url.pathname;
            }
        } catch (e) {
            // Not a full URL, continue
        }

        if (decodedText.startsWith('/gate-activity/')) {
            return decodedText;
        }

        const match = decodedText.match(/\/gate-activity\/([a-zA-Z0-9_\-]+)/i);
        if (match) {
            return '/gate-activity/' + match[1];
        }

        // Direct code string (misal "LV01" atau "LV-01")
        const cleanCode = decodedText.replace(/[^a-zA-Z0-9_\-]/g, '');
        if (cleanCode.length >= 2) {
            return '/gate-activity/' + cleanCode;
        }

        return null;
    }

    function openUnitQrScannerModal() {
        isScanProcessing = false;
        document.getElementById('unitQrScannerModal').classList.remove('hidden');
        document.getElementById('qrScannerSuccessOverlay').classList.add('hidden');
        startQrScanner();
    }

    async function closeUnitQrScannerModal() {
        await stopQrScanner();
        document.getElementById('unitQrScannerModal').classList.add('hidden');
        isScanProcessing = false;
    }

    async function stopQrScanner() {
        if (html5QrScanner) {
            try {
                if (html5QrScanner.isScanning) {
                    await html5QrScanner.stop();
                }
                html5QrScanner.clear();
            } catch (e) {
                console.error('Error stopping scanner:', e);
            }
            html5QrScanner = null;
        }
    }

    async function startQrScanner() {
        const loadingEl = document.getElementById('qrScannerLoading');
        const errorEl = document.getElementById('qrScannerError');
        const errorMsg = document.getElementById('qrScannerErrorMsg');

        loadingEl.classList.remove('hidden');
        errorEl.classList.add('hidden');

        await stopQrScanner();

        if (typeof Html5Qrcode === 'undefined') {
            loadingEl.classList.add('hidden');
            errorEl.classList.remove('hidden');
            errorMsg.textContent = 'Komponen scanner kamera gagal dimuat. Periksa koneksi internet Anda atau pilih manual.';
            return;
        }

        try {
            html5QrScanner = new Html5Qrcode("qr-reader");
            const config = {
                fps: 10,
                qrbox: { width: 220, height: 220 },
                aspectRatio: 1.0
            };

            await html5QrScanner.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    if (isScanProcessing) return;
                    const targetUrl = parseScannedCode(decodedText);
                    if (targetUrl) {
                        isScanProcessing = true;
                        const successOverlay = document.getElementById('qrScannerSuccessOverlay');
                        successOverlay.classList.remove('hidden');
                        
                        stopQrScanner().then(() => {
                            window.location.href = targetUrl;
                        }).catch(() => {
                            window.location.href = targetUrl;
                        });
                    }
                },
                (error) => {
                    // Frame scan non-match, silently continue
                }
            );
            loadingEl.classList.add('hidden');
        } catch (err) {
            loadingEl.classList.add('hidden');
            errorEl.classList.remove('hidden');
            errorMsg.textContent = (err && err.message) ? err.message : 'Kamera tidak dapat diakses. Pastikan peramban browser diberikan izin akses kamera (Camera Permission).';
        }
    }

    // Countdown Timer Tiket Pending (Batas Waktu 5 Jam)
    const countdownContainer = document.getElementById('pendingTripCountdownContainer');
    if (countdownContainer) {
        const expiresAtRaw = countdownContainer.getAttribute('data-expires-at');
        const timerTextEl = document.getElementById('tripCountdownTimerText');
        if (expiresAtRaw && timerTextEl) {
            const expireTime = new Date(expiresAtRaw).getTime();

            function updateTripCountdown() {
                const now = new Date().getTime();
                const distance = expireTime - now;

                if (distance <= 0) {
                    timerTextEl.textContent = 'Kedaluwarsa';
                    countdownContainer.classList.remove('text-rose-700', 'border-rose-300');
                    countdownContainer.classList.add('text-slate-600', 'bg-slate-100', 'border-slate-300');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2500);
                    return;
                }

                const hours = Math.floor(distance / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                const pad = (n) => String(n).padStart(2, '0');
                timerTextEl.textContent = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
            }

            updateTripCountdown();
            setInterval(updateTripCountdown, 1000);
        }
    }

    // Real-Time Lightweight Polling untuk Memantau Status Validasi Pos Gerbang
    const pendingTripCard = document.getElementById('pendingTripCard');
    if (pendingTripCard) {
        const activityId = pendingTripCard.getAttribute('data-activity-id');
        let pollingTimer = null;
        let isPollingInProgress = false;

        async function pollTripStatus() {
            // Jangan lakukan polling jika tab sedang di-minimize/background untuk hemat resource
            if (document.hidden) return;
            if (isPollingInProgress) return;

            isPollingInProgress = true;
            try {
                const response = await fetch(`{{ route('employee.activity.status_polling') }}?activity_id=${activityId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    isPollingInProgress = false;
                    return;
                }

                const data = await response.json();
                isPollingInProgress = false;

                // Jika status sudah berubah dari pending, hentikan polling dan tampilkan notifikasi
                if (data && data.status && data.status !== 'pending') {
                    if (pollingTimer) {
                        clearInterval(pollingTimer);
                        pollingTimer = null;
                    }

                    showValidationNotification(data);
                }
            } catch (err) {
                isPollingInProgress = false;
                // Supresi error jaringan agar tidak mengganggu driver
            }
        }

        // Jalankan polling ringan berkala setiap 4 detik
        pollingTimer = setInterval(pollTripStatus, 4000);

        // Jika user kembali ke tab browser, langsung cek status 1 kali
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && pollingTimer) {
                pollTripStatus();
            }
        });
    }

    function showValidationNotification(data) {
        const modal = document.getElementById('validationNotificationModal');
        const card = document.getElementById('validationNotificationCard');
        const banner = document.getElementById('validationNotificationBanner');
        const icon = document.getElementById('validationNotificationIcon');
        const title = document.getElementById('validationNotificationTitle');
        const subtitle = document.getElementById('validationNotificationSubtitle');
        const trxNo = document.getElementById('validationTrxNo');
        const vehicle = document.getElementById('validationVehicle');
        const gate = document.getElementById('validationGate');
        const guard = document.getElementById('validationGuard');
        const noteRow = document.getElementById('validationNoteRow');
        const note = document.getElementById('validationNote');
        const dismissBtn = document.getElementById('validationDismissBtn');

        if (!modal) return;

        trxNo.textContent = data.transaction_no || '-';
        vehicle.textContent = `${data.vehicle_code} (${data.vehicle_name})`;
        gate.textContent = data.gate_name || 'Pos Gerbang';
        guard.textContent = data.guard_name || 'Petugas Satpam';

        if (data.is_approved) {
            banner.className = 'p-6 text-center text-white bg-gradient-to-br from-emerald-600 to-[#1b5e20]';
            icon.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-100"></i>';
            title.textContent = 'Perjalanan Disetujui!';
            subtitle.textContent = `Pemeriksaan selesai oleh Satpam ${data.guard_name} pada ${data.validated_at || 'hari ini'}`;
            dismissBtn.className = 'w-full bg-[#1b5e20] hover:bg-[#144918] text-white font-bold py-3 px-4 rounded-xl text-xs sm:text-sm shadow-md transition flex items-center justify-center space-x-2 cursor-pointer';
            dismissBtn.innerHTML = '<span>Lanjutkan Perjalanan (OK)</span><i class="fa-solid fa-arrow-right text-xs"></i>';
            noteRow.classList.add('hidden');
        } else if (data.is_rejected) {
            banner.className = 'p-6 text-center text-white bg-gradient-to-br from-rose-600 to-rose-800';
            icon.innerHTML = '<i class="fa-solid fa-circle-xmark text-rose-100"></i>';
            title.textContent = 'Perjalanan Ditolak';
            subtitle.textContent = `Ditolak oleh Satpam ${data.guard_name} di ${data.gate_name}`;
            dismissBtn.className = 'w-full bg-rose-700 hover:bg-rose-800 text-white font-bold py-3 px-4 rounded-xl text-xs sm:text-sm shadow-md transition flex items-center justify-center space-x-2 cursor-pointer';
            dismissBtn.innerHTML = '<span>Tutup & Periksa Dashboard</span>';
            if (data.rejection_note) {
                note.textContent = data.rejection_note;
                noteRow.classList.remove('hidden');
            }
        } else if (data.is_cancelled) {
            banner.className = 'p-6 text-center text-white bg-gradient-to-br from-amber-600 to-amber-800';
            icon.innerHTML = '<i class="fa-solid fa-ban text-amber-100"></i>';
            title.textContent = data.cancellation_source || 'Perjalanan Dibatalkan';
            subtitle.textContent = 'Status tiket perjalanan telah dibatalkan';
            dismissBtn.className = 'w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-3 px-4 rounded-xl text-xs sm:text-sm shadow-md transition flex items-center justify-center space-x-2 cursor-pointer';
            dismissBtn.innerHTML = '<span>Tutup</span>';
            if (data.rejection_note) {
                note.textContent = data.rejection_note;
                noteRow.classList.remove('hidden');
            }
        }

        modal.classList.remove('hidden');
        setTimeout(() => {
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 10);
    }

    function handleValidationDismiss() {
        // Refresh halus setelah driver klik agar state tabel tersinkron permanen
        window.location.reload();
    }
</script>
@endsection
