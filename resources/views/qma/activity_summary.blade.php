@extends('layouts.app')

@section('title', 'Summary Aktivitas Kendaraan QMA')

@section('content')
<div class="w-full space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-1.5 mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                    MANAJEMEN SISTEM
                </span>
                <!-- Tombol Info Panduan Halaman Sejajar Kicker -->
                <button type="button" 
                        onclick="openGuideModal()" 
                        class="w-3.5 h-3.5 rounded-full bg-slate-200/90 hover:bg-slate-300 text-slate-500 hover:text-slate-800 text-[8px] inline-flex items-center justify-center font-bold transition shadow-2xs cursor-pointer focus:outline-none" 
                        title="Panduan Halaman Summary Aktivitas Kendaraan">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Summary Aktivitas Kendaraan
            </h2>
        </div>

        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 sm:gap-2.5 w-full sm:w-auto">
            <!-- Dropdown Menu Ekspor Format it.allrole.web.id -->
            <div class="relative inline-block flex-1 sm:flex-none" id="exportActivitySummaryDropdownContainer">
                <button type="button" 
                        onclick="toggleActivitySummaryExportMenu(event)" 
                        id="btnActivitySummaryExportTrigger"
                        class="w-full sm:w-auto flex items-center justify-center space-x-2 border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3.5 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                    <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                    <span class="truncate">Ekspor Summary</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
                </button>
                <div id="exportMenuActivitySummary" style="display: none;" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 z-50 overflow-hidden">
                    <a href="{{ route('qma.activity.summary.export_pdf', request()->query()) }}" target="_blank" class="flex items-center px-4 py-3 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#1b5e20] font-bold transition">
                        <i class="fa-regular fa-file-pdf text-rose-600 text-sm mr-2.5"></i>
                        <span>Format PDF (Cetak)</span>
                    </a>
                    <div class="h-px bg-slate-100 mx-2"></div>
                    <a href="{{ route('qma.activity.summary.export_excel', request()->query()) }}" class="flex items-center px-4 py-3 text-xs text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i class="fa-regular fa-file-excel text-emerald-600 text-sm mr-2.5"></i>
                        <span>Format Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            <a href="{{ route('qma.activity.log') }}" class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-sm transition group">
                <i class="fa-solid fa-clipboard-list mr-2 text-slate-500 group-hover:text-slate-800"></i>
                <span class="whitespace-nowrap">Log Aktivitas Detail</span>
            </a>
        </div>
    </div>

    @php
        $activeFilterCount = count(array_filter(request()->only(['start_date', 'end_date', 'type', 'gate_id'])));
        $isFilterOpen = request()->anyFilled(['start_date', 'end_date', 'type', 'gate_id']);
    @endphp

    <!-- Form Filter & Pencarian Cepat -->
    <form method="GET" action="{{ route('qma.activity.summary') }}" class="space-y-3">
        <!-- Baris Atas: Search Bar Mandiri di Luar Card Filter & Tombol Toggle -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <!-- Search Bar Utama (Real-Time) -->
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
                <input type="text" 
                       name="search" 
                       id="summarySearchInput"
                       value="{{ request('search') }}" 
                       placeholder="Cari Kode LV, Unit, Plat, Penanggung Jawab..." 
                       class="w-full text-xs rounded-xl border border-slate-200 bg-white hover:border-slate-300 focus:bg-white pl-9 pr-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition shadow-2xs">
            </div>

            <!-- Tombol Buka/Tutup Filter Card & Reset Filter -->
            <div class="flex items-center gap-2.5 self-end sm:self-auto w-full sm:w-auto justify-between sm:justify-start">
                <button type="button" 
                        id="filterToggleBtn"
                        onclick="toggleFilterCard()" 
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs transition group cursor-pointer">
                    <i class="fa-solid fa-sliders text-slate-500 mr-2 group-hover:text-[#1b5e20] transition-colors"></i>
                    <span>Filter</span>
                    @if($activeFilterCount > 0)
                        <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-[#1b5e20] text-white">
                            {{ $activeFilterCount }}
                        </span>
                    @endif
                    <i id="filterChevron" class="fa-solid fa-chevron-down ml-2 text-[10px] text-slate-400 transition-transform duration-200 {{ $isFilterOpen ? 'rotate-180' : '' }}"></i>
                </button>

                <div id="headerResetFilterContainer">
                    @if(request()->anyFilled(['search', 'start_date', 'end_date', 'type', 'gate_id']))
                        <a href="{{ route('qma.activity.summary') }}" 
                           id="headerResetFilterBtn" 
                           class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center space-x-1 transition px-3 py-2 rounded-xl hover:bg-rose-50 border border-transparent hover:border-rose-100">
                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                            <span>Reset Filter</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Card Filter Lanjutan (Bisa Di Buka-Tutup / Collapsible) -->
        <div id="collapsibleFilterCard" class="{{ $isFilterOpen ? '' : 'hidden ' }}bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 transition-all">
            <!-- Header Card Filter -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3.5">
                <div class="flex items-center space-x-2">
                    <span class="text-slate-800 font-bold text-xs uppercase tracking-wider">
                        Opsi Filter Lanjutan
                    </span>
                    @if($activeFilterCount > 0)
                        <span class="text-[11px] text-slate-400 font-medium">({{ $activeFilterCount }} filter aktif)</span>
                    @endif
                </div>
                <button type="button" 
                        onclick="toggleFilterCard()" 
                        class="text-xs text-slate-400 hover:text-slate-600 transition flex items-center space-x-1 cursor-pointer">
                    <span>Tutup</span>
                    <i class="fa-solid fa-xmark text-[11px]"></i>
                </button>
            </div>

            <!-- Baris Input Filter (Grid Responsif 4 Kolom) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                <!-- Mulai Tanggal -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Mulai Tanggal
                    </label>
                    <input type="date" 
                           name="start_date" 
                           value="{{ request('start_date', $startDate ?? now()->format('Y-m-d')) }}" 
                           onchange="this.form.submit()"
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Sampai Tanggal -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Sampai Tanggal
                    </label>
                    <input type="date" 
                           name="end_date" 
                           value="{{ request('end_date', $endDate ?? now()->format('Y-m-d')) }}" 
                           onchange="this.form.submit()"
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Tipe Kendaraan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Kategori Unit
                    </label>
                    <select name="type" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Kategori</option>
                        <option value="lv" {{ request('type') === 'lv' ? 'selected' : '' }}>Light Vehicle (LV)</option>
                        <option value="support" {{ request('type') === 'support' ? 'selected' : '' }}>Heavy Equipment</option>
                    </select>
                </div>

                <!-- Pos Gerbang -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Pos Gerbang
                    </label>
                    <select name="gate_id" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Gerbang</option>
                        @foreach($gates as $g)
                            <option value="{{ $g->id }}" {{ request('gate_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

    <!-- KPI Metric Cards (Mobile: Format 2 Kolom Horisontal x 2 Kolom Vertikal) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Total Pergerakan / Trips -->
        <div class="bg-white rounded-2xl p-3.5 sm:p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <p class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Total Pergerakan</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">{{ number_format($totalActivities, 0, ',', '.') }}</h3>
            </div>
            <div class="flex flex-wrap items-center gap-1 sm:space-x-2 mt-1.5 sm:mt-1 text-[10px] sm:text-[11px]">
                <span class="text-emerald-700 font-semibold"><i class="fa-solid fa-arrow-down mr-0.5"></i> {{ $inCount }} Masuk</span>
                <span class="text-slate-300 hidden sm:inline">•</span>
                <span class="text-blue-700 font-semibold"><i class="fa-solid fa-arrow-up mr-0.5"></i> {{ $outCount }} Keluar</span>
            </div>
        </div>

        <!-- Akumulasi Jarak Tempuh (KM) -->
        <div class="bg-white rounded-2xl p-3.5 sm:p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <p class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Total Jarak Tempuh</p>
                <h3 class="text-xl sm:text-2xl font-black text-emerald-700 font-mono mt-1">
                    {{ number_format($totalDistance, 0, ',', '.') }} <span class="text-xs font-sans font-bold text-slate-500">KM</span>
                </h3>
            </div>
            <p class="text-[10px] sm:text-[11px] text-slate-500 mt-1.5 sm:mt-1">
                Rata-rata: <span class="font-bold font-mono text-slate-700">{{ number_format($avgDistancePerTrip, 1, ',', '.') }} KM</span>/trip
            </p>
        </div>

        <!-- Armada Beroperasi -->
        <div class="bg-white rounded-2xl p-3.5 sm:p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <p class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Armada Beroperasi</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">{{ $activeVehiclesCount }} <span class="text-xs font-bold text-slate-500 font-sans">Unit</span></h3>
            </div>
            <p class="text-[10px] sm:text-[11px] text-slate-500 mt-1.5 sm:mt-1">
                Unit aktif dalam periode ini
            </p>
        </div>

        <!-- Status Validasi PG -->
        <div class="bg-white rounded-2xl p-3.5 sm:p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <p class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Status Validasi PG</p>
                <div class="flex flex-wrap items-center gap-1 sm:space-x-2 mt-2">
                    <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ $approvedCount }} Disetujui
                    </span>
                    <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        {{ $pendingCount }} Menunggu
                    </span>
                </div>
            </div>
            @if($rejectedCount > 0)
                <p class="text-[10px] text-rose-600 font-semibold mt-1">
                    {{ $rejectedCount }} Ditolak
                </p>
            @endif
        </div>
    </div>

    <!-- Rekapitulasi per Kendaraan (Tabel Summary Armada) -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-base font-bold text-slate-900">Rekapitulasi Aktivitas per Armada Kendaraan</h3>
                <p class="text-xs text-slate-500">Akumulasi pergerakan, jarak tempuh, dan frekuensi per unit kendaraan</p>
            </div>
            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-xl">
                {{ $vehicleSummaries->count() }} Kendaraan Aktif
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Kode Unit</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">Unit / Merek</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">Kategori</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">Penanggung Jawab</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Trip Masuk</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Trip Keluar</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Total Trip</th>
                        <th class="py-3.5 px-4 text-right whitespace-nowrap">Total Jarak</th>
                        <th class="py-3.5 px-4 text-right whitespace-nowrap">Odometer Terkini</th>
                    </tr>
                </thead>
                <tbody id="summaryTableBody" class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($vehicleSummaries as $vSummary)
                        @php
                            $veh = $vSummary->vehicle;
                        @endphp
                        <tr class="summary-vehicle-row hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center whitespace-nowrap font-mono">
                                <span class="inline-block bg-emerald-50 text-emerald-800 font-bold px-2.5 py-1 rounded border border-emerald-200">
                                    {{ $veh->code ?? '-' }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $veh->brand_type ?? '-' }}</div>
                                @if($veh && $veh->license_plate)
                                    <span class="inline-block text-[10px] font-mono font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 mt-0.5">
                                        {{ $veh->license_plate }}
                                    </span>
                                @else
                                    <span class="text-[10px] text-slate-400 italic">-</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($veh && $veh->isLightVehicle())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                        Light Vehicle
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800">
                                        Heavy Equipment
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-slate-600 font-medium">
                                {{ $veh->ownership ?? '-' }}
                                <div class="text-[10px] text-slate-400">{{ $veh->division ?? '-' }}</div>
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold text-emerald-700 whitespace-nowrap">
                                {{ $vSummary->in_trips }}
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold text-blue-700 whitespace-nowrap">
                                {{ $vSummary->out_trips }}
                            </td>

                            <td class="py-3.5 px-4 text-center font-black text-slate-900 whitespace-nowrap">
                                {{ $vSummary->total_trips }}
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-700 whitespace-nowrap">
                                {{ number_format($vSummary->total_km, 0, ',', '.') }}
                                <span class="text-[10px] text-slate-400 font-sans font-normal">KM</span>
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono font-medium text-slate-600 whitespace-nowrap">
                                {{ number_format($veh->current_km ?? 0, 0, ',', '.') }}
                                <span class="text-[10px] text-slate-400 font-sans font-normal">KM</span>
                            </td>
                        </tr>
                    @empty
                        <tr id="serverSummaryEmptyRow">
                            <td colspan="9" class="py-12 px-4 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl shadow-xs">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </div>
                                    @if(request('search'))
                                        <h4 class="text-sm font-bold text-slate-800">
                                            Data "<span class="text-rose-600 font-bold">{{ request('search') }}</span>" tidak ditemukan di tabel
                                        </h4>
                                        <p class="text-xs text-slate-400 max-w-sm">
                                            Pastikan kata kunci yang dicari sudah benar atau gunakan filter lainnya.
                                        </p>
                                        <div class="mt-4">
                                            <a href="{{ route('qma.activity.summary') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                                <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                                <span>Hapus Pencarian</span>
                                            </a>
                                        </div>
                                    @else
                                        <div class="text-sm font-bold text-slate-700">Belum Ada Data Rekapitulasi</div>
                                        <p class="text-xs text-slate-400">Belum ada data aktivitas kendaraan untuk periode yang dipilih.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Grid: Top Driver & Rekapitulasi Pos Gerbang -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top 10 Driver Pengemudi Teraktif -->
        <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Pengemudi Teraktif</h3>
                <p class="text-xs text-slate-500">Berdasarkan total jarak tempuh & frekuensi trip</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs text-left border-collapse">
                    <thead class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Driver</th>
                            <th class="py-3 px-4">Divisi</th>
                            <th class="py-3 px-4 text-center">Total Trip</th>
                            <th class="py-3 px-4 text-right">Total KM</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($driverSummaries as $dSummary)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ $dSummary->driver->name ?? 'Pengemudi Tidak Terdaftar' }}
                                    <div class="text-[10px] text-slate-400 font-mono font-normal">{{ $dSummary->driver->nik ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ $dSummary->driver->division ?? '-' }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-slate-900">
                                    {{ $dSummary->total_trips }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">
                                    {{ number_format($dSummary->total_km, 0, ',', '.') }} KM
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400">
                                    Belum ada data driver pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rekapitulasi Pos Gerbang -->
        <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Trafik Pos Gerbang</h3>
                <p class="text-xs text-slate-500">Distribusi pergerakan armada per pos inspeksi</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs text-left border-collapse">
                    <thead class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Pos Gerbang</th>
                            <th class="py-3 px-4 text-center">Masuk</th>
                            <th class="py-3 px-4 text-center">Keluar</th>
                            <th class="py-3 px-4 text-right">Total Pergerakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($gateSummaries as $gSummary)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ $gSummary->gate->name ?? 'Gerbang' }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-emerald-700">
                                    {{ $gSummary->in_trips }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-blue-700">
                                    {{ $gSummary->out_trips }}
                                </td>
                                <td class="py-3 px-4 text-right font-black text-slate-900">
                                    {{ $gSummary->total_trips }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400">
                                    Belum ada data trafik gerbang pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (SUMMARY AKTIVITAS KENDARAAN)                -->
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
                    <p class="text-xs text-slate-500">Summary Aktivitas Kendaraan</p>
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
                Selamat datang di halaman <strong>Summary Aktivitas Kendaraan</strong>! Halaman analitik ini menyajikan rekapitulasi performa pergerakan armada armada operasional PT Kerinci Merangin Hidro. Di halaman ini Anda dapat:
            </p>

            <div class="space-y-3">
                <!-- Fitur 1 (Hijau) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-emerald-200/50">
                        <i class="fa-solid fa-chart-simple"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Kartu Metrik & Total Jarak</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Melihat total trip pergerakan, akumulasi kilometer riil seluruh armada, serta unit aktif dalam periode terpilih.
                        </p>
                    </div>
                </div>

                <!-- Fitur 2 (Biru) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-blue-200/50">
                        <i class="fa-solid fa-table-list"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Rekapitulasi per Unit & Driver</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Mengevaluasi tabel frekuensi perjalanan dan jarak tempuh per kendaraan maupun pengemudi untuk optimalisasi aset.
                        </p>
                    </div>
                </div>

                <!-- Fitur 3 (Amber) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-amber-200/50">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Penyaringan Berdasarkan Periode</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Tentukan tanggal mulai dan akhir untuk memfilter laporan berkala (harian, mingguan, atau bulanan).
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
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('summarySearchInput');
    const tableBody = document.getElementById('summaryTableBody');
    const resetContainer = document.getElementById('headerResetFilterContainer');
    const serverEmptyRow = document.getElementById('serverSummaryEmptyRow');
    const hasServerFilters = {{ request()->anyFilled(['start_date', 'end_date', 'type', 'gate_id']) ? 'true' : 'false' }};

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function (e) {
            const rawValue = e.target.value;
            const query = rawValue.toLowerCase().trim();
            const rows = tableBody.querySelectorAll('tr.summary-vehicle-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const match = text.includes(query);
                row.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            // Sembunyikan pesan SSR kosong saat user mengetik
            if (serverEmptyRow) {
                serverEmptyRow.style.display = query.length > 0 ? 'none' : '';
            }

            // Hapus baris pesan kosong pencarian client jika ada sebelumnya
            const existingEmptyRow = document.getElementById('summaryClientEmptyRow');
            if (existingEmptyRow) {
                existingEmptyRow.remove();
            }

            // Tampilkan pesan jika tidak ada data yang cocok di tabel
            if (visibleCount === 0 && query.length > 0) {
                const emptyTr = document.createElement('tr');
                emptyTr.id = 'summaryClientEmptyRow';
                emptyTr.innerHTML = `
                    <td colspan="9" class="py-12 px-4 text-center">
                        <div class="flex flex-col items-center justify-center space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </div>
                            <h4 class="text-sm font-bold text-slate-800">
                                Data "<span class="text-rose-600 font-bold">${escapeHtml(rawValue)}</span>" tidak ditemukan di tabel
                            </h4>
                            <p class="text-xs text-slate-400 max-w-sm">
                                Pastikan kata kunci yang dicari sudah benar atau gunakan filter lainnya.
                            </p>
                            <div class="mt-4">
                                <button type="button" 
                                        onclick="const inp = document.getElementById('summarySearchInput'); if(inp){ inp.value=''; inp.dispatchEvent(new Event('input')); inp.focus(); }" 
                                        class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                    <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                    <span>Hapus Pencarian</span>
                                </button>
                            </div>
                        </div>
                    </td>
                `;
                tableBody.appendChild(emptyTr);
            }

            // Tampilkan atau sembunyikan tombol Reset Filter secara real-time
            if (resetContainer) {
                let resetBtn = document.getElementById('headerResetFilterBtn');
                if (query.length > 0 || hasServerFilters) {
                    if (!resetBtn) {
                        resetBtn = document.createElement('a');
                        resetBtn.href = "{{ route('qma.activity.summary') }}";
                        resetBtn.id = 'headerResetFilterBtn';
                        resetBtn.className = 'text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center space-x-1 transition px-3 py-2 rounded-xl hover:bg-rose-50 border border-transparent hover:border-rose-100';

                        const resetIcon = document.createElement('i');
                        resetIcon.className = 'fa-solid fa-rotate-left text-[11px]';
                        const resetText = document.createElement('span');
                        resetText.textContent = 'Reset Filter';

                        resetBtn.appendChild(resetIcon);
                        resetBtn.appendChild(resetText);
                        resetContainer.appendChild(resetBtn);
                    }
                } else {
                    if (resetBtn && !hasServerFilters) {
                        resetBtn.remove();
                    }
                }
            }
        });
    }
});

    // Toggle Card Filter Lanjutan
    function toggleFilterCard() {
        const card = document.getElementById('collapsibleFilterCard');
        const chevron = document.getElementById('filterChevron');
        if (card) {
            card.classList.toggle('hidden');
        }
        if (chevron) {
            chevron.classList.toggle('rotate-180');
        }
    }

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

    // Dropdown Menu Ekspor Summary Aktivitas Kendaraan
    function toggleActivitySummaryExportMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('exportMenuActivitySummary');
        if (menu) {
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }
    }

    document.addEventListener('click', function(event) {
        const container = document.getElementById('exportActivitySummaryDropdownContainer');
        const menu = document.getElementById('exportMenuActivitySummary');
        if (container && !container.contains(event.target)) {
            if (menu) {
                menu.style.display = 'none';
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGuideModal();
            const exportMenu = document.getElementById('exportMenuActivitySummary');
            if (exportMenu) exportMenu.style.display = 'none';
        }
    });
</script>
@endsection
