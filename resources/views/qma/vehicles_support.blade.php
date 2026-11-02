@extends('layouts.app')

@section('title', 'Heavy Equipment - Admin QMA | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Breadcrumb Navigasi Hirarki 3 Level -->
    <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
        <a href="{{ route('qma.dashboard') }}" class="hover:text-emerald-700 transition">Dashboard</a>
        <i class="fa-solid fa-chevron-right text-[9px] text-slate-300"></i>
        <a href="{{ route('qma.vehicles.index') }}" class="hover:text-emerald-700 transition">Data Armada</a>
        <i class="fa-solid fa-chevron-right text-[9px] text-slate-300"></i>
        @if($level === 'groups')
            <span class="text-slate-900 font-extrabold">Heavy Equipment</span>
        @else
            <a href="{{ route('qma.vehicles.support') }}" class="hover:text-emerald-700 transition">Heavy Equipment</a>
            <i class="fa-solid fa-chevron-right text-[9px] text-slate-300"></i>
            @if($level === 'types')
                <span class="text-slate-900 font-extrabold">{{ $selectedGroup }}</span>
            @elseif($level === 'units')
                <a href="{{ route('qma.vehicles.support', ['group' => $selectedGroup]) }}" class="hover:text-emerald-700 transition">{{ $selectedGroup }}</a>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-300"></i>
                <span class="text-slate-900 font-extrabold">{{ $selectedType }}</span>
            @endif
        @endif
    </nav>

    <!-- Header Judul & Tombol Aksi -->
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
                        title="Panduan Halaman Heavy Equipment">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                @if($level === 'groups')
                    Heavy Equipment
                @elseif($level === 'types')
                    Kelompok: {{ $selectedGroup }}
                @elseif($level === 'units')
                    {{ $selectedType }}
                @endif
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                @if($level === 'groups')
                    Navigasi hierarki 3 level: Kelompok Operasional &rarr; Tipe Alat Berat &rarr; Unit Fisik Individual.
                @elseif($level === 'types')
                    Daftar tipe alat berat operasional di bawah kelompok <span class="font-bold text-slate-800">{{ $selectedGroup }}</span>.
                @elseif($level === 'units')
                    Daftar unit alat berat fisik individual untuk tipe <span class="font-bold text-slate-800">{{ $selectedType }}</span> (Kelompok {{ $selectedGroup }}).
                @endif
            </p>
        </div>

        <!-- Tombol Aksi Kanan -->
        <div class="grid grid-cols-2 gap-2.5 w-full sm:flex sm:items-center sm:gap-3 sm:w-auto">
            <!-- Dropdown Menu Ekspor Format it.allrole.web.id -->
            <div class="relative inline-block w-full sm:w-auto" id="exportSupportDropdownContainer">
                <button type="button" 
                        onclick="toggleSupportExportMenu(event)" 
                        id="btnSupportExportTrigger"
                        class="w-full sm:w-auto flex items-center justify-center space-x-2 border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                    <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                    <span class="truncate">Ekspor Data</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
                </button>
                <div id="exportMenuSupport" style="display: none;" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 z-50 overflow-hidden">
                    <a href="{{ route('qma.vehicles.support.export_pdf', request()->query()) }}" target="_blank" class="flex items-center px-4 py-3 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#1b5e20] font-bold transition">
                        <i class="fa-regular fa-file-pdf text-rose-600 text-sm mr-2.5"></i>
                        <span>Format PDF (Cetak)</span>
                    </a>
                    <div class="h-px bg-slate-100 mx-2"></div>
                    <a href="{{ route('qma.vehicles.support.export_excel', request()->query()) }}" class="flex items-center px-4 py-3 text-xs text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i class="fa-regular fa-file-excel text-emerald-600 text-sm mr-2.5"></i>
                        <span>Format Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            @can('qma.vehicles.create')
            <button onclick="openAddModal()" 
                    class="w-full sm:w-auto flex items-center justify-center space-x-2 bg-[#1b5e20] hover:bg-[#144919] text-white px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span class="truncate">Tambah Unit</span>
            </button>
            @endcan
        </div>
    </div>

    <!-- STATISTIK KARTU (LEVEL 1: TAMPILAN UTAMA KELOMPOK) -->
    @if($level === 'groups')
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs hover:shadow-xs transition">
            <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider block">TOTAL ALAT BERAT</span>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 mt-1 flex items-baseline gap-1.5">
                <span>{{ $totalUnits }}</span>
                <span class="text-xs font-semibold text-slate-400">Unit Fisik</span>
            </div>
            <div class="mt-2 text-[11px] text-slate-500 font-medium flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>6 Kelompok Operasional (Excel)</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs hover:shadow-xs transition">
            <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider block">STATUS OPERASI</span>
            <div class="text-2xl sm:text-3xl font-black text-emerald-600 mt-1 flex items-baseline gap-1.5">
                <span>{{ $totalOperation }}</span>
                <span class="text-xs font-semibold text-slate-400">Unit Aktif</span>
            </div>
            <div class="mt-2 text-[11px] text-emerald-700 font-medium">
                {{ $totalUnits > 0 ? round(($totalOperation / $totalUnits) * 100, 1) : 0 }}% Utility Rate Operasional
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs hover:shadow-xs transition">
            <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider block">STATUS STANDBY</span>
            <div class="text-2xl sm:text-3xl font-black text-amber-500 mt-1 flex items-baseline gap-1.5">
                <span>{{ $totalStandby }}</span>
                <span class="text-xs font-semibold text-slate-400">Unit Siap</span>
            </div>
            <div class="mt-2 text-[11px] text-amber-700 font-medium">
                Siap Dimobilisasi / Beroperasi
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs hover:shadow-xs transition">
            <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider block">STATUS BREAKDOWN</span>
            <div class="text-2xl sm:text-3xl font-black text-rose-600 mt-1 flex items-baseline gap-1.5">
                <span>{{ $totalBreakdown }}</span>
                <span class="text-xs font-semibold text-slate-400">Unit Rusak</span>
            </div>
            <div class="mt-2 text-[11px] text-rose-700 font-medium">
                Dalam Perbaikan / Maintenance
            </div>
        </div>
    </div>
    @endif

    <!-- BANNER NAVIGASI LEVEL 2 (TIPE DALAM KELOMPOK TERPILIH) -->
    @if($level === 'types')
    @php
        $curMeta = $groupMetadata[$selectedGroup] ?? [
            'icon' => 'fa-truck-monster',
            'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'icon_bg' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
            'desc' => 'Daftar tipe alat berat dalam kelompok ' . $selectedGroup,
        ];
    @endphp
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 sm:p-5 bg-gradient-to-r from-slate-900 to-[#06213f] text-white rounded-2xl shadow-sm">
        <div class="flex items-start sm:items-center space-x-3.5">
            <a href="{{ route('qma.vehicles.support') }}" class="px-3.5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition flex items-center gap-2 border border-white/10 flex-shrink-0 cursor-pointer">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Semua Kelompok</span>
            </a>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="text-lg sm:text-xl font-extrabold text-white tracking-tight">{{ $selectedGroup }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-white/20 text-white border border-white/30 uppercase tracking-wider">
                        {{ $groupTypesCount }} Tipe Alat
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        {{ $groupTotalUnits }} Unit Fisik
                    </span>
                </div>
                <p class="text-xs text-slate-300 mt-1 max-w-xl font-normal leading-relaxed">
                    {{ $curMeta['desc'] }}
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
            <span class="px-2.5 py-1 rounded-lg font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                {{ $groupOpCount ?? 0 }} Operation
            </span>
            <span class="px-2.5 py-1 rounded-lg font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                {{ $groupStCount ?? 0 }} Standby
            </span>
            <span class="px-2.5 py-1 rounded-lg font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                {{ $groupBdCount ?? 0 }} Breakdown
            </span>
            <span class="px-2.5 py-1 rounded-lg font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">
                {{ $groupInsideCount ?? 0 }} Site / {{ $groupOutsideCount ?? 0 }} Luar
            </span>
        </div>
    </div>
    @endif

    <!-- BANNER NAVIGASI LEVEL 3 (UNIT FISIK DALAM TIPE TERPILIH) -->
    @if($level === 'units')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 bg-emerald-50/80 border border-emerald-200 rounded-2xl shadow-2xs">
        <div class="flex items-center space-x-3">
            <a href="{{ route('qma.vehicles.support', ['group' => $selectedGroup]) }}" class="px-3.5 py-2 rounded-xl bg-white border border-emerald-300 hover:bg-emerald-100/60 text-[#1b5e20] text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fa-solid fa-arrow-left text-[11px]"></i>
                <span>Tipe {{ $selectedGroup }}</span>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200 uppercase">
                        {{ $selectedGroup }}
                    </span>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900">
                        {{ $selectedType }}
                    </h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-600 text-white">
                        {{ $totalUnitsOfType ?? $typeUnits->total() }} Unit Fisik
                    </span>
                </div>
                <p class="text-[11px] text-emerald-800 font-medium mt-0.5">
                    Daftar unit alat berat fisik individual untuk tipe {{ $selectedType }} di kelompok {{ $selectedGroup }}.
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
            <span class="px-2.5 py-1 rounded-lg font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                {{ $opCount ?? 0 }} Operation
            </span>
            <span class="px-2.5 py-1 rounded-lg font-bold bg-amber-100 text-amber-800 border border-amber-200">
                {{ $stCount ?? 0 }} Standby
            </span>
            <span class="px-2.5 py-1 rounded-lg font-bold bg-rose-100 text-rose-800 border border-rose-200">
                {{ $bdCount ?? 0 }} Breakdown
            </span>
            <span class="px-2.5 py-1 rounded-lg font-bold bg-blue-100 text-blue-800 border border-blue-200">
                {{ $insideCount ?? 0 }} Site / {{ $outsideCount ?? 0 }} Luar
            </span>
        </div>
    </div>
    @endif

    <!-- Kotak Pencarian & Filter Alat Berat -->
    <form method="GET" action="{{ route('qma.vehicles.support') }}" id="supportFilterForm" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        @if(isset($selectedGroup) && $selectedGroup)
            <input type="hidden" name="group" value="{{ $selectedGroup }}">
        @endif
        @if(isset($selectedType) && $selectedType)
            <input type="hidden" name="type" value="{{ $selectedType }}">
        @endif

        <div class="relative w-full max-w-sm sm:max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" 
                   id="supportSearchInput"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="@if($level === 'units')Cari kode unit, nomor seri, model, lokasi di {{ $selectedType }}...@elseif($level === 'types')Cari tipe alat berat di kelompok {{ $selectedGroup }}...@elseCari kelompok operasional atau tipe alat berat (Hauling, Excavator, Dump Truck)...@endif" 
                   class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 shadow-sm transition">
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($level === 'groups')
            <!-- Filter Kelompok Operasional Langsung -->
            <select name="group" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20]">
                <option value="">Semua Kelompok Operasional</option>
                @foreach($availableGroups as $grp)
                    <option value="{{ $grp }}" {{ request('group') === $grp ? 'selected' : '' }}>{{ $grp }}</option>
                @endforeach
            </select>
            @elseif($level === 'types')
            <!-- Filter Kategori Alat Berat di Level 2 -->
            <select name="unit_category" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20]">
                <option value="">Semua Kategori Sistem</option>
                @foreach($availableCategories as $cat)
                    <option value="{{ $cat }}" {{ request('unit_category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
            @elseif($level === 'units')
            <!-- Filter Status Operasional untuk Drilldown Unit Fisik -->
            <select name="status" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20]">
                <option value="">Semua Status Operasi</option>
                <option value="Operation" {{ request('status') === 'Operation' ? 'selected' : '' }}>Operation</option>
                <option value="Standby" {{ request('status') === 'Standby' ? 'selected' : '' }}>Standby</option>
                <option value="Breakdown" {{ request('status') === 'Breakdown' ? 'selected' : '' }}>Breakdown</option>
            </select>
            <select name="site_status" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20]">
                <option value="">Semua Area</option>
                <option value="inside_site" {{ request('site_status') === 'inside_site' ? 'selected' : '' }}>Di Dalam Site</option>
                <option value="outside_site" {{ request('site_status') === 'outside_site' ? 'selected' : '' }}>Di Luar Site</option>
            </select>
            @endif

            <!-- Filter Jumlah Per Halaman -->
            <select name="per_page" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20]">
                <option value="15" {{ request('per_page', '15') == '15' ? 'selected' : '' }}>15 / Hal</option>
                <option value="50" {{ request('per_page', '50') == '50' ? 'selected' : '' }}>50 / Hal</option>
                <option value="100" {{ request('per_page', '100') == '100' ? 'selected' : '' }}>Semua (100)</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition shadow-sm cursor-pointer whitespace-nowrap">
                Cari
            </button>

            <div id="supportResetWrapper" class="flex items-center">
                @if(request()->anyFilled(['search', 'group', 'type', 'unit_category', 'status', 'site_status', 'per_page']))
                    @php
                        if ($level === 'units') {
                            $resetUrl = route('qma.vehicles.support', ['group' => $selectedGroup, 'type' => $selectedType]);
                        } elseif ($level === 'types') {
                            $resetUrl = route('qma.vehicles.support', ['group' => $selectedGroup]);
                        } else {
                            $resetUrl = route('qma.vehicles.support');
                        }
                    @endphp
                    <a id="supportResetBtn" href="{{ $resetUrl }}" class="text-xs text-rose-600 hover:text-rose-800 font-semibold flex items-center space-x-1 whitespace-nowrap ml-1">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>Reset</span>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Tabel Data Heavy Equipment (Header Navy Solid #06213f) -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">

            {{-- ========================================================================= --}}
            {{-- LEVEL 3: TABEL DAFTAR UNIT FISIK INDIVIDUAL SPESIFIK TIPE TERPILIH        --}}
            {{-- ========================================================================= --}}
            @if($level === 'units')
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4">KODE UNIT</th>
                        <th class="py-3.5 px-4">MEREK & MODEL</th>
                        <th class="py-3.5 px-4">NO. SERI / RANGKA</th>
                        <th class="py-3.5 px-4 text-center">TAHUN</th>
                        <th class="py-3.5 px-4">LOKASI SAAT INI</th>
                        <th class="py-3.5 px-4 text-center">AREA</th>
                        <th class="py-3.5 px-4 text-center">STATUS</th>
                        <th class="py-3.5 px-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($typeUnits as $index => $u)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                {{ $typeUnits->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 font-mono text-xs whitespace-nowrap">
                                <span class="bg-blue-50 text-blue-800 px-2.5 py-1 rounded border border-blue-200">
                                    {{ $u->code }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $u->brand_type ?: ($u->brand ?: '-') }}</div>
                                @if($u->model_number)
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">Model: {{ $u->model_number }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-600">
                                {{ $u->serial_number ?: '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center text-slate-600">
                                {{ $u->production_year ?: '-' }}
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-800">
                                <div class="flex items-center space-x-1">
                                    <i class="fa-solid fa-location-dot text-emerald-600 text-[10px]"></i>
                                    <span>{{ $u->location ?: 'Site Kerinci' }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $u->isOutsideSite() ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">
                                    {{ $u->site_status_label }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($u->status === 'Operation')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Operation
                                    </span>
                                @elseif($u->status === 'Standby')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Standby
                                    </span>
                                @elseif($u->status === 'Breakdown')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Breakdown
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                                        {{ $u->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <button onclick='openDetailModal(@json($u))' 
                                            class="inline-flex items-center space-x-1 text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-2 py-1 rounded-lg transition"
                                            title="Lihat Detail Unit">
                                        <i class="fa-regular fa-eye text-sm"></i>
                                    </button>
                                    @can('qma.vehicles.edit')
                                    <button onclick='openEditModal(@json($u))' 
                                            class="inline-flex items-center space-x-1 text-amber-600 hover:text-amber-800 hover:bg-amber-50 px-2 py-1 rounded-lg transition"
                                            title="Edit Data Unit">
                                        <i class="fa-regular fa-pen-to-square text-sm"></i>
                                    </button>
                                    @endcan
                                    @can('qma.vehicles.delete')
                                    <button type="button" 
                                            onclick="openDeleteModal('{{ $u->id }}', '{{ $u->code }}', '{{ addslashes($u->brand_type ?? '') }}')"
                                            class="inline-flex items-center space-x-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-2 py-1 rounded-lg transition"
                                            title="Hapus Unit">
                                        <i class="fa-regular fa-trash-can text-sm"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
                                <p class="text-sm font-semibold text-slate-700">Tidak ada unit fisik untuk tipe {{ $selectedType }}</p>
                                <p class="text-xs text-slate-400 mt-1">Belum ada unit terdaftar atau sesuai kriteria filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- ========================================================================= --}}
            {{-- LEVEL 2: TABEL DAFTAR TIPE ALAT BERAT DI DALAM KELOMPOK TERPILIH           --}}
            {{-- ========================================================================= --}}
            @elseif($level === 'types')
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4">TIPE ALAT BERAT</th>
                        <th class="py-3.5 px-4">KELOMPOK</th>
                        <th class="py-3.5 px-4 text-center">JUMLAH (QTY)</th>
                        <th class="py-3.5 px-4">STATUS OPERASIONAL</th>
                        <th class="py-3.5 px-4">DISTRIBUSI AREA</th>
                        <th class="py-3.5 px-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($types as $index => $t)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                {{ $types->firstItem() + $index }}
                            </td>

                            <td class="py-3.5 px-4 font-bold text-slate-900 text-xs whitespace-nowrap">
                                <a href="{{ route('qma.vehicles.support', ['group' => $selectedGroup, 'type' => $t->unit_type]) }}" class="hover:text-[#1b5e20] transition flex items-center space-x-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center justify-center flex-shrink-0">
                                        <i class="fa-solid fa-truck-monster text-xs"></i>
                                    </div>
                                    <div>
                                        <span class="font-extrabold text-slate-900 block text-xs">{{ $t->unit_type }}</span>
                                        @if(isset($t->sample_brand_type) && $t->sample_brand_type)
                                            <span class="text-[10px] text-slate-400 font-normal block">{{ $t->sample_brand_type }}</span>
                                        @endif
                                    </div>
                                </a>
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase tracking-wider">
                                    {{ $selectedGroup }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <a href="{{ route('qma.vehicles.support', ['group' => $selectedGroup, 'type' => $t->unit_type]) }}" 
                                   class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-[#e8f8f0] text-[#00897b] border border-[#a7f3d0] hover:bg-[#d1fae5] transition shadow-2xs group cursor-pointer"
                                   title="Buka daftar {{ $t->units_count ?? 0 }} unit fisik tipe {{ $t->unit_type }}">
                                    <span>{{ $t->units_count ?? 0 }} Unit</span>
                                    <i class="fa-solid fa-chevron-right text-[10px] ml-1 transition group-hover:translate-x-0.5"></i>
                                </a>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-1.5 text-[10px] font-bold">
                                    <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        {{ $t->total_operation ?? 0 }} Operasi
                                    </span>
                                    <span class="text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                        {{ $t->total_standby ?? 0 }} Standby
                                    </span>
                                    <span class="text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                                        {{ $t->total_breakdown ?? 0 }} Breakdown
                                    </span>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-slate-600 text-[11px] whitespace-nowrap">
                                <span class="font-medium text-emerald-800">{{ $t->inside_site_count ?? 0 }} Di Dalam Site</span>
                                <span class="text-slate-300 mx-1">&bull;</span>
                                <span class="text-slate-500">{{ $t->outside_site_count ?? 0 }} Di Luar Site</span>
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <a href="{{ route('qma.vehicles.support', ['group' => $selectedGroup, 'type' => $t->unit_type]) }}" 
                                   class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-[#1b5e20] hover:bg-[#144919] text-white rounded-xl text-xs font-bold transition shadow-2xs">
                                    <i class="fa-regular fa-folder-open text-xs"></i>
                                    <span>Buka Unit</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
                                <p class="text-sm font-semibold text-slate-700">Tidak ada tipe alat berat di kelompok {{ $selectedGroup }}</p>
                                <p class="text-xs text-slate-400 mt-1">Belum ada tipe alat yang sesuai dengan filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- ========================================================================= --}}
            {{-- LEVEL 1: TABEL DAFTAR KELOMPOK OPERASIONAL HEAVY EQUIPMENT (EXCEL)        --}}
            {{-- ========================================================================= --}}
            @else
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4">KELOMPOK OPERASIONAL</th>
                        <th class="py-3.5 px-4 text-center">TIPE ALAT</th>
                        <th class="py-3.5 px-4 text-center">JUMLAH (QTY)</th>
                        <th class="py-3.5 px-4">STATUS OPERASIONAL</th>
                        <th class="py-3.5 px-4">DISTRIBUSI AREA</th>
                        <th class="py-3.5 px-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($groups as $index => $g)
                        @php
                            $meta = $groupMetadata[$g->operational_group] ?? [
                                'icon' => 'fa-truck-monster',
                                'badge' => 'bg-slate-100 text-slate-700 border-slate-200',
                                'icon_bg' => 'bg-slate-100 text-slate-700 border-slate-200',
                                'desc' => 'Kelompok operasional alat berat',
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                {{ $groups->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4">
                                <a href="{{ route('qma.vehicles.support', ['group' => $g->operational_group]) }}" class="flex items-center space-x-3 group">
                                    <div class="w-10 h-10 rounded-xl {{ $meta['icon_bg'] }} border flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition shadow-2xs">
                                        <i class="fa-solid {{ $meta['icon'] }} text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <span class="font-extrabold text-slate-900 group-hover:text-emerald-700 text-sm transition">{{ $g->operational_group }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wider {{ $meta['badge'] }}">
                                                {{ $g->operational_group }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-400 line-clamp-1 mt-0.5 font-normal max-w-md">
                                            {{ $meta['desc'] }}
                                        </p>
                                    </div>
                                </a>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $g->types_count ?? 0 }} Tipe Alat
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <a href="{{ route('qma.vehicles.support', ['group' => $g->operational_group]) }}" 
                                   class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 rounded-full text-xs font-extrabold bg-[#e8f8f0] text-[#00897b] border border-[#a7f3d0] hover:bg-[#d1fae5] transition shadow-2xs group cursor-pointer"
                                   title="Lihat kategori/tipe dalam kelompok {{ $g->operational_group }}">
                                    <span>{{ $g->units_count ?? 0 }} Unit</span>
                                    <i class="fa-solid fa-chevron-right text-[10px] ml-1 transition group-hover:translate-x-0.5"></i>
                                </a>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-1.5 text-[10px] font-bold">
                                    <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        {{ $g->total_operation ?? 0 }} Operasi
                                    </span>
                                    <span class="text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                        {{ $g->total_standby ?? 0 }} Standby
                                    </span>
                                    <span class="text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                                        {{ $g->total_breakdown ?? 0 }} Breakdown
                                    </span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 text-[11px] whitespace-nowrap">
                                <span class="font-medium text-emerald-800">{{ $g->inside_site_count ?? 0 }} Dalam Site</span>
                                <span class="text-slate-300 mx-1">&bull;</span>
                                <span class="text-slate-500">{{ $g->outside_site_count ?? 0 }} Luar Site</span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <a href="{{ route('qma.vehicles.support', ['group' => $g->operational_group]) }}" 
                                   class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-[#1b5e20] hover:bg-[#144919] text-white rounded-xl text-xs font-bold transition shadow-2xs">
                                    <i class="fa-solid fa-list-ul text-xs"></i>
                                    <span>Lihat Kategori</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
                                <p class="text-sm font-semibold text-slate-700">Kelompok operasional tidak ditemukan</p>
                                <p class="text-xs text-slate-400 mt-1">Silakan sesuaikan kata kunci pencarian Anda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @endif
        </div>

        <div id="supportPaginationContainer" class="p-4 bg-slate-50 border-t border-slate-200">
            @if($level === 'units')
                {{ $typeUnits->links() }}
            @elseif($level === 'types')
                {{ $types->links() }}
            @else
                {{ $groups->links() }}
            @endif
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- DATALIST UNTUK AUTO-SUGGEST LOKASI & MEREK ALAT BERAT                    -->
<!-- ========================================================================= -->
<datalist id="locationsList">
    @foreach($availableLocations ?? [] as $loc)
        <option value="{{ $loc }}"></option>
    @endforeach
</datalist>

<datalist id="brandsList">
    @foreach($availableBrands ?? [] as $b)
        <option value="{{ $b }}"></option>
    @endforeach
</datalist>

<!-- ========================================================================= -->
<!-- MODAL: TAMBAH UNIT ALAT BERAT (HEAVY EQUIPMENT)                           -->
<!-- ========================================================================= -->
<div id="addVehicleModal" 
     data-backdrop="static"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 sm:p-7 border border-slate-100 max-h-[92vh] overflow-y-auto no-scrollbar">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#1b5e20] border border-emerald-200 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-truck-monster text-base"></i>
                </div>
                <div>
                    <h3 id="addModalTitle" class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">
                        Tambah Unit Alat Berat
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Mendaftarkan unit alat berat fisik baru ke sistem armada proyek.</p>
                </div>
            </div>
            <button type="button" onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="addVehicleForm" action="{{ route('qma.vehicles.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="category" value="unit_support">

            <!-- Upload Foto Unit -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Foto Unit Alat Berat (Opsional)</label>
                <div class="flex items-center space-x-3.5 p-3 rounded-2xl border border-slate-200 bg-slate-50/50">
                    <div id="addPhotoPreviewContainer" class="w-14 h-14 rounded-xl border-2 border-dashed border-slate-300 bg-white flex items-center justify-center overflow-hidden flex-shrink-0 shadow-2xs">
                        <img id="addPhotoPreview" src="" alt="Pratinjau" class="w-full h-full object-cover hidden">
                        <i id="addPhotoPlaceholder" class="fa-solid fa-camera text-slate-400 text-base"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <input type="file" 
                               name="photo" 
                               id="addPhotoInput" 
                               accept="image/jpeg,image/png,image/jpg,image/webp" 
                               onchange="previewVehicleImage(this, 'addPhotoPreview', 'addPhotoPlaceholder')"
                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-[#1b5e20] hover:file:bg-emerald-100 cursor-pointer">
                        <p class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, WebP (Maks. 10MB). Resolusi & file dikompresi otomatis.</p>
                    </div>
                </div>
            </div>

            <!-- Baris 1: Kelompok Operasional (Kategori Excel) & Tipe Alat Berat (Type Excel) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">
                        Kelompok Operasional (Excel) <span class="text-rose-500">*</span>
                    </label>
                    <select name="operational_group" id="addOperationalGroup" required 
                            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-bold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        @foreach($availableGroups as $grp)
                            <option value="{{ $grp }}" {{ (isset($selectedGroup) && $selectedGroup === $grp) ? 'selected' : '' }}>
                                {{ $grp }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">
                        Tipe Alat Berat (Excel) <span class="text-rose-500">*</span>
                    </label>
                    <div class="space-y-1.5">
                        <select id="addUnitTypeSelect" required
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-bold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                            <!-- Diisi via JavaScript berdasarkan kelompok yang dipilih -->
                        </select>
                        <input type="text" id="addCustomUnitType" placeholder="Ketik nama tipe alat berat baru..." 
                               class="hidden w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition uppercase font-semibold">
                    </div>
                </div>
            </div>

            <!-- Baris 2: Kode Unit Fisik & Nomor Polisi -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">
                        Kode Unit Fisik <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="code" id="addCodeInput" required placeholder="Contoh: DT 105, EX 60, BL 11" value="{{ old('code') }}"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border @error('code') border-rose-500 @else border-slate-200 @enderror bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono uppercase font-bold">
                    
                    <p id="codeError" class="text-xs text-rose-600 font-semibold mt-1 flex items-center space-x-1.5 @if(!$errors->has('code')) hidden @endif">
                        <i class="fa-solid fa-circle-exclamation text-rose-500 text-xs"></i>
                        <span id="codeErrorText">{{ $errors->first('code') ?? 'Kode unit sudah ada, silakan gunakan kode lain.' }}</span>
                    </p>
                    <p id="codeHint" class="text-[11px] text-slate-400 mt-0.5 @if($errors->has('code')) hidden @endif">
                        * Kode unik identifikasi fisik unit alat berat di lapangan.
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Nomor Polisi (Plat No)</label>
                    <input type="text" name="license_plate" placeholder="Contoh: BH 8920 BM (opsional)" value="{{ old('license_plate') }}"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono uppercase font-medium">
                </div>
            </div>

            <!-- Baris 3: Merek & Model / Spesifikasi -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Merek (Brand)</label>
                    <input type="text" name="brand" list="brandsList" placeholder="Contoh: MITSUBISHI, KOMATSU, HOWO" value="{{ old('brand') }}"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Model / Tipe Model</label>
                    <input type="text" name="model_number" placeholder="Contoh: FE 84 SHDX, PC200-8, SINOTRUK 336" value="{{ old('model_number') }}"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
            </div>

            <!-- Baris 4: No. Seri / Rangka & Tahun Pembuatan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">No. Seri / Rangka (Serial Number)</label>
                    <input type="text" name="serial_number" placeholder="Nomor seri rangka / mesin unit" value="{{ old('serial_number') }}"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Tahun Pembuatan</label>
                    <input type="number" name="production_year" min="1980" max="{{ date('Y') + 1 }}" placeholder="Contoh: 2021" value="{{ old('production_year') }}"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
            </div>

            <!-- Baris 5: Lokasi Saat Ini & Status Area -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Lokasi Saat Ini</label>
                    <input type="text" name="location" list="locationsList" placeholder="Contoh: ADIT 3, POWER HOUSE, BENDUNGAN" value="{{ old('location') }}"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Area Lokasi</label>
                    <select name="site_status" 
                            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-medium">
                        <option value="inside_site" {{ old('site_status', 'inside_site') === 'inside_site' ? 'selected' : '' }}>
                            Di Dalam Area Proyek (Site KMH)
                        </option>
                        <option value="outside_site" {{ old('site_status') === 'outside_site' ? 'selected' : '' }}>
                            Di Luar Proyek (Workshop / Vendor Luar)
                        </option>
                    </select>
                </div>
            </div>

            <!-- Baris 6: Status Operasional & KM / HM Awal -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Status Operasional <span class="text-rose-500">*</span></label>
                    <select name="status" required
                            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        <option value="Operation" {{ old('status', 'Operation') === 'Operation' ? 'selected' : '' }}>Operation (Beroperasi Aktif)</option>
                        <option value="Standby" {{ old('status') === 'Standby' ? 'selected' : '' }}>Standby (Siap Operasi)</option>
                        <option value="Breakdown" {{ old('status') === 'Breakdown' ? 'selected' : '' }}>Breakdown (Rusak / Perbaikan)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Nilai KM / HM Awal</label>
                    <input type="number" step="0.1" name="current_km" value="{{ old('current_km', 0) }}" min="0" placeholder="0"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <!-- Baris 7: Divisi & Kepemilikan Aset -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Divisi Penanggung Jawab</label>
                    <select name="division" 
                            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-medium">
                        <option value="">-- Pilih Divisi Penanggung Jawab --</option>
                        @foreach($availableDivisions as $div)
                            <option value="{{ $div }}" {{ old('division') === $div ? 'selected' : '' }}>{{ $div }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Kepemilikan Aset (Owner)</label>
                    <input type="text" name="ownership" placeholder="Contoh: PT Kerinci Merangin Hidro / PT BMS" value="{{ old('ownership') }}"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <!-- Baris 8: Estimasi Konsumsi BBM -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Estimasi Konsumsi BBM (Liter/Jam)</label>
                    <input type="number" step="0.01" name="fuel_consumption" placeholder="Contoh: 18.5" value="{{ old('fuel_consumption') }}"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
            </div>

            <!-- Footer Tombol -->
            <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-100">
                <button type="button" onclick="closeAddModal()" 
                        class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs transition shadow-sm cursor-pointer flex items-center space-x-1.5">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    <span>Simpan Unit Alat Berat</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT UNIT ALAT BERAT (HEAVY EQUIPMENT)                             -->
<!-- ========================================================================= -->
<div id="editVehicleModal" 
     data-backdrop="static"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 sm:p-7 border border-slate-100 max-h-[92vh] overflow-y-auto no-scrollbar">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-pen-to-square text-base"></i>
                </div>
                <div>
                    <h3 id="editModalTitle" class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">
                        Edit Data Unit Alat Berat
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Memperbarui informasi dan spesifikasi unit alat berat fisik.</p>
                </div>
            </div>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="editVehicleForm" action="" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="category" value="unit_support">

            <!-- Foto Unit -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Foto Unit Alat Berat</label>
                <div class="flex items-center space-x-3.5 p-3 rounded-2xl border border-slate-200 bg-slate-50/50">
                    <div id="editPhotoPreviewContainer" class="w-14 h-14 rounded-xl border border-slate-200 bg-white flex items-center justify-center overflow-hidden flex-shrink-0 shadow-2xs">
                        <img id="editPhotoPreview" src="" alt="Foto Unit" class="w-full h-full object-cover hidden">
                        <i id="editPhotoPlaceholder" class="fa-solid fa-truck-monster text-slate-400 text-base"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <input type="file" 
                               name="photo" 
                               id="editPhotoInput" 
                               accept="image/jpeg,image/png,image/jpg,image/webp" 
                               onchange="previewVehicleImage(this, 'editPhotoPreview', 'editPhotoPlaceholder')"
                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-[#1b5e20] hover:file:bg-emerald-100 cursor-pointer">
                        <p class="text-[10px] text-slate-400 mt-1">Pilih foto baru jika ingin mengganti. Otomatis dikompresi hemat storage.</p>
                    </div>
                </div>
            </div>

            <!-- Baris 1: Kelompok Operasional & Tipe Alat Berat -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">
                        Kelompok Operasional (Excel) <span class="text-rose-500">*</span>
                    </label>
                    <select name="operational_group" id="editOperationalGroup" required 
                            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-bold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        @foreach($availableGroups as $grp)
                            <option value="{{ $grp }}">{{ $grp }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">
                        Tipe Alat Berat (Excel) <span class="text-rose-500">*</span>
                    </label>
                    <div class="space-y-1.5">
                        <select id="editUnitTypeSelect" required
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-bold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        </select>
                        <input type="text" id="editCustomUnitType" placeholder="Ketik tipe baru..." 
                               class="hidden w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition uppercase font-semibold">
                    </div>
                </div>
            </div>

            <!-- Baris 2: Kode Unit Fisik & Nomor Polisi -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">
                        Kode Unit Fisik <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="code" id="editCode" required 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono uppercase font-bold">
                    
                    <p id="editCodeError" class="text-xs text-rose-600 font-semibold mt-1 hidden flex items-center space-x-1.5">
                        <i class="fa-solid fa-circle-exclamation text-rose-500 text-xs"></i>
                        <span id="editCodeErrorText">Kode unit sudah ada, silakan gunakan kode lain.</span>
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Nomor Polisi (Plat No)</label>
                    <input type="text" name="license_plate" id="editLicensePlate" placeholder="Contoh: BH 8920 BM (opsional)" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono uppercase font-medium">
                </div>
            </div>

            <!-- Baris 3: Merek & Model -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Merek (Brand)</label>
                    <input type="text" name="brand" id="editBrand" list="brandsList" placeholder="Contoh: MITSUBISHI, KOMATSU, HOWO" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Model / Tipe Model</label>
                    <input type="text" name="model_number" id="editModelNumber" placeholder="Contoh: FE 84 SHDX, PC200-8" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
            </div>

            <!-- Baris 4: No. Seri & Tahun -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">No. Seri / Rangka (Serial Number)</label>
                    <input type="text" name="serial_number" id="editSerialNumber" placeholder="Nomor seri rangka / mesin" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Tahun Pembuatan</label>
                    <input type="number" name="production_year" id="editProductionYear" min="1980" max="{{ date('Y') + 1 }}" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
            </div>

            <!-- Baris 5: Lokasi & Status Area -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Lokasi Saat Ini</label>
                    <input type="text" name="location" id="editLocation" list="locationsList" placeholder="Contoh: ADIT 3, POWER HOUSE" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Area Lokasi</label>
                    <select name="site_status" id="editSiteStatus" 
                            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-medium">
                        <option value="inside_site">Di Dalam Area Proyek (Site KMH)</option>
                        <option value="outside_site">Di Luar Proyek (Workshop / Vendor Luar)</option>
                    </select>
                </div>
            </div>

            <!-- Baris 6: Status Operasional & KM / HM -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Status Operasional <span class="text-rose-500">*</span></label>
                    <select name="status" id="editStatus" required
                            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        <option value="Operation">Operation (Beroperasi Aktif)</option>
                        <option value="Standby">Standby (Siap Operasi)</option>
                        <option value="Breakdown">Breakdown (Rusak / Perbaikan)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Nilai KM / HM</label>
                    <input type="number" step="0.1" name="current_km" id="editCurrentKm" min="0" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <!-- Baris 7: Divisi & Kepemilikan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Divisi Penanggung Jawab</label>
                    <select name="division" id="editDivision" 
                            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-medium">
                        <option value="">-- Pilih Divisi Penanggung Jawab --</option>
                        @foreach($availableDivisions as $div)
                            <option value="{{ $div }}">{{ $div }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Kepemilikan Aset (Owner)</label>
                    <input type="text" name="ownership" id="editOwnership" placeholder="Contoh: PT Kerinci Merangin Hidro / PT BMS" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <!-- Baris 8: Estimasi Konsumsi BBM -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Estimasi Konsumsi BBM (Liter/Jam)</label>
                    <input type="number" step="0.01" name="fuel_consumption" id="editFuelConsumption" placeholder="Contoh: 18.5" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
            </div>

            <!-- Footer Tombol -->
            <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-100">
                <button type="button" onclick="closeEditModal()" 
                        class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition shadow-sm cursor-pointer flex items-center space-x-1.5">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    <span>Perbarui Unit Alat Berat</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: DETAIL SPESIFIKASI UNIT ALAT BERAT (HEAVY EQUIPMENT)               -->
<!-- ========================================================================= -->
<div id="detailVehicleModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full p-6 sm:p-7 border border-slate-100 max-h-[92vh] overflow-y-auto no-scrollbar">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-circle-info text-base"></i>
                </div>
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">
                        Detail Spesifikasi Alat Berat
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Informasi lengkap spesifikasi dan status unit fisik.</p>
                </div>
            </div>
            <button type="button" onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="space-y-4">
            <!-- Foto Unit di Detail Modal -->
            <div id="detailPhotoContainer" class="w-full h-48 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden relative hidden">
                <img id="detailPhoto" src="" alt="Foto Unit Alat Berat" class="w-full h-full object-cover">
            </div>

            <!-- Banner Header Identitas Unit -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span id="detailCodeBadge" class="bg-blue-600 text-white font-mono text-sm font-extrabold px-3 py-1 rounded-xl shadow-2xs">
                            -
                        </span>
                        <span id="detailGroupBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase">
                            -
                        </span>
                    </div>
                    <h4 id="detailTypeTitle" class="text-base font-extrabold text-slate-900 mt-1.5">
                        -
                    </h4>
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <span id="detailStatusBadge" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Operation
                    </span>
                    <span id="detailAreaBadge" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                        Di Dalam Site
                    </span>
                </div>
            </div>

            <!-- Grid Rincian Spesifikasi Unit -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Merek & Model</span>
                    <span id="detailBrandModel" class="font-bold text-slate-800 mt-0.5 block">-</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">No. Seri / Rangka</span>
                    <span id="detailSerialNumber" class="font-mono font-semibold text-slate-800 mt-0.5 block">-</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Tahun Pembuatan</span>
                    <span id="detailProductionYear" class="font-bold text-slate-800 mt-0.5 block">-</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Nomor Polisi (Plat No)</span>
                    <span id="detailLicensePlate" class="font-mono font-bold text-slate-800 mt-0.5 block">-</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Lokasi Terkini</span>
                    <div class="flex items-center space-x-1.5 mt-0.5">
                        <i class="fa-solid fa-location-dot text-emerald-600 text-xs"></i>
                        <span id="detailLocation" class="font-bold text-slate-800">-</span>
                    </div>
                </div>
                <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">KM / HM Terkini</span>
                    <span id="detailCurrentKm" class="font-mono font-bold text-slate-800 mt-0.5 block">-</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Divisi Penanggung Jawab</span>
                    <span id="detailDivision" class="font-semibold text-slate-800 mt-0.5 block">-</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Kepemilikan Aset (Owner)</span>
                    <span id="detailOwnership" class="font-semibold text-slate-800 mt-0.5 block">-</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100 sm:col-span-2">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Konsumsi BBM Standar</span>
                    <span id="detailFuel" class="font-medium text-slate-800 mt-0.5 block">-</span>
                </div>
            </div>

            <!-- Footer Tombol Tutup -->
            <div class="pt-3 flex items-center justify-end border-t border-slate-100">
                <button type="button" onclick="closeDetailModal()" 
                        class="px-6 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs transition shadow-sm cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: KONFIRMASI HAPUS HEAVY EQUIPMENT (KENDARAAN BERAT) -->
<!-- ========================================================================= -->
<div id="deleteVehicleModal" data-backdrop="static" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
    <div class="bg-white rounded-3xl shadow-2xl max-w-sm sm:max-w-md w-full p-6 sm:p-7 border border-slate-100 transform transition-all text-center">
        <!-- Icon Peringatan Elegan -->
        <div class="w-14 h-14 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4 text-2xl shadow-sm">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h3 class="text-lg font-bold text-slate-900 tracking-tight">
            Konfirmasi Hapus Heavy Equipment
        </h3>

        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
            Apakah Anda yakin ingin menghapus data Heavy Equipment <span id="deleteVehicleCode" class="font-bold font-mono text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200"></span>?
        </p>

        <p class="text-[11px] text-rose-600 mt-3 bg-rose-50/70 border border-rose-100 py-1.5 px-3 rounded-xl inline-flex items-center">
            <i class="fa-solid fa-circle-info mr-1.5 flex-shrink-0 text-xs"></i>
            <span>Data yang dihapus tidak dapat dipulihkan kembali.</span>
        </p>

        <!-- Tombol Aksi -->
        <div class="flex items-center space-x-3 mt-6">
            <button type="button" onclick="closeDeleteModal()" 
                    class="flex-1 py-2.5 px-4 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                Batal
            </button>
            <form id="deleteVehicleForm" action="" method="POST" class="flex-1">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition shadow-sm flex items-center justify-center space-x-1.5">
                    <i class="fa-regular fa-trash-can text-xs"></i>
                    <span>Ya, Hapus Data</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // =========================================================================
    // REAL-TIME CLIENT-SIDE SEARCH
    // =========================================================================
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('supportSearchInput');
        const filterForm = document.getElementById('supportFilterForm');
        let searchDebounce = null;

        if (searchInput && searchInput.value) {
            searchInput.focus();
            const len = searchInput.value.length;
            searchInput.setSelectionRange(len, len);
        }

        searchInput?.addEventListener('input', function() {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(function() {
                filterForm?.submit();
            }, 500);
        });
    });

    // Daftar semua kode kendaraan heavy equipment yang sudah terdaftar di sistem
    const existingVehicleCodes = @json($existingCodes ?? \App\Models\Vehicle::unitSupports()->pluck('code')->map(fn($c) => strtoupper(trim($c))));

    // Peta Tipe Alat Berat berdasarkan Kelompok Operasional
    const groupTypesMap = @json($groupTypesMap ?? []);

    // Helper untuk mengisi dropdown tipe alat berat dinamis berdasarkan kelompok
    function populateTypeSelect(groupSelectId, typeSelectId, customInputId, defaultType = '') {
        const groupSelect = document.getElementById(groupSelectId);
        const typeSelect = document.getElementById(typeSelectId);
        const customInput = document.getElementById(customInputId);
        if (!groupSelect || !typeSelect) return;

        const selectedGroup = groupSelect.value;
        const types = groupTypesMap[selectedGroup] || [];

        typeSelect.innerHTML = '';
        
        let matched = false;
        types.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t;
            opt.textContent = t;
            if (defaultType && t.toUpperCase() === defaultType.toUpperCase()) {
                opt.selected = true;
                matched = true;
            }
            typeSelect.appendChild(opt);
        });

        const customOpt = document.createElement('option');
        customOpt.value = '__custom__';
        customOpt.textContent = '+ Ketik Tipe Baru...';
        typeSelect.appendChild(customOpt);

        if (defaultType && !matched) {
            customOpt.selected = true;
            if (customInput) {
                customInput.value = defaultType;
                customInput.classList.remove('hidden');
            }
        } else if (customInput) {
            customInput.classList.add('hidden');
            customInput.value = '';
        }
    }

    // Event listener pergantian kelompok di modal tambah & edit
    const addOperationalGroup = document.getElementById('addOperationalGroup');
    const addUnitTypeSelect = document.getElementById('addUnitTypeSelect');
    const addCustomUnitType = document.getElementById('addCustomUnitType');
    if (addOperationalGroup) {
        addOperationalGroup.addEventListener('change', function() {
            populateTypeSelect('addOperationalGroup', 'addUnitTypeSelect', 'addCustomUnitType');
        });
    }
    if (addUnitTypeSelect && addCustomUnitType) {
        addUnitTypeSelect.addEventListener('change', function() {
            if (this.value === '__custom__') {
                addCustomUnitType.classList.remove('hidden');
                addCustomUnitType.focus();
            } else {
                addCustomUnitType.classList.add('hidden');
                addCustomUnitType.value = '';
            }
        });
    }

    const editOperationalGroup = document.getElementById('editOperationalGroup');
    const editUnitTypeSelect = document.getElementById('editUnitTypeSelect');
    const editCustomUnitType = document.getElementById('editCustomUnitType');
    if (editOperationalGroup) {
        editOperationalGroup.addEventListener('change', function() {
            populateTypeSelect('editOperationalGroup', 'editUnitTypeSelect', 'editCustomUnitType');
        });
    }
    if (editUnitTypeSelect && editCustomUnitType) {
        editUnitTypeSelect.addEventListener('change', function() {
            if (this.value === '__custom__') {
                editCustomUnitType.classList.remove('hidden');
                editCustomUnitType.focus();
            } else {
                editCustomUnitType.classList.add('hidden');
                editCustomUnitType.value = '';
            }
        });
    }

    const addCodeInput = document.getElementById('addCodeInput');
    const codeError = document.getElementById('codeError');
    const codeErrorText = document.getElementById('codeErrorText');
    const codeHint = document.getElementById('codeHint');
    const addVehicleForm = document.getElementById('addVehicleForm');

    function checkDuplicateCode() {
        if (!addCodeInput) return false;
        const val = addCodeInput.value.trim().toUpperCase();
        if (!val) {
            hideCodeError();
            return false;
        }

        if (existingVehicleCodes.includes(val)) {
            showCodeError('Kode unit sudah ada, silakan gunakan kode lain.');
            return true;
        } else {
            hideCodeError();
            return false;
        }
    }

    function showCodeError(message) {
        if (codeErrorText) codeErrorText.innerText = message;
        if (codeError) codeError.classList.remove('hidden');
        if (codeHint) codeHint.classList.add('hidden');
        if (addCodeInput) {
            addCodeInput.classList.add('border-rose-500', 'ring-2', 'ring-rose-500/10');
            addCodeInput.classList.remove('border-slate-200');
        }
    }

    function hideCodeError() {
        if (codeError) codeError.classList.add('hidden');
        if (codeHint) codeHint.classList.remove('hidden');
        if (addCodeInput) {
            addCodeInput.classList.remove('border-rose-500', 'ring-2', 'ring-rose-500/10');
            addCodeInput.classList.add('border-slate-200');
        }
    }

    if (addCodeInput) {
        addCodeInput.addEventListener('input', function() {
            if (codeError && !codeError.classList.contains('hidden')) {
                checkDuplicateCode();
            }
        });
        addCodeInput.addEventListener('blur', function() {
            checkDuplicateCode();
        });
    }

    if (addVehicleForm) {
        addVehicleForm.addEventListener('submit', function(e) {
            if (checkDuplicateCode()) {
                e.preventDefault();
                addCodeInput.focus();
                return false;
            }
            if (addUnitTypeSelect && addUnitTypeSelect.value === '__custom__') {
                const val = addCustomUnitType.value.trim().toUpperCase();
                if (!val) {
                    e.preventDefault();
                    alert('Silakan ketik nama tipe alat berat baru.');
                    addCustomUnitType.focus();
                    return false;
                }
                addUnitTypeSelect.removeAttribute('name');
                addCustomUnitType.setAttribute('name', 'unit_type');
            } else if (addUnitTypeSelect) {
                addUnitTypeSelect.setAttribute('name', 'unit_type');
                if (addCustomUnitType) addCustomUnitType.removeAttribute('name');
            }
        });
    }

    // Elemen modal Edit Heavy Equipment
    const editCodeInput = document.getElementById('editCode');
    const editCodeError = document.getElementById('editCodeError');
    const editCodeErrorText = document.getElementById('editCodeErrorText');
    const editVehicleForm = document.getElementById('editVehicleForm');
    let currentEditVehicleCode = '';

    function checkEditDuplicateCode() {
        if (!editCodeInput) return false;
        const val = editCodeInput.value.trim().toUpperCase();
        if (!val) {
            hideEditCodeError();
            return false;
        }

        // Jika kode sama dengan kode kendaraan yang sedang diedit, bukan duplikat
        if (val === currentEditVehicleCode) {
            hideEditCodeError();
            return false;
        }

        if (existingVehicleCodes.includes(val)) {
            showEditCodeError('Kode unit sudah ada, silakan gunakan kode lain.');
            return true;
        } else {
            hideEditCodeError();
            return false;
        }
    }

    function showEditCodeError(message) {
        if (editCodeErrorText) editCodeErrorText.innerText = message;
        if (editCodeError) editCodeError.classList.remove('hidden');
        if (editCodeInput) {
            editCodeInput.classList.add('border-rose-500', 'ring-2', 'ring-rose-500/10');
            editCodeInput.classList.remove('border-slate-200');
        }
    }

    function hideEditCodeError() {
        if (editCodeError) editCodeError.classList.add('hidden');
        if (editCodeInput) {
            editCodeInput.classList.remove('border-rose-500', 'ring-2', 'ring-rose-500/10');
            editCodeInput.classList.add('border-slate-200');
        }
    }

    if (editCodeInput) {
        editCodeInput.addEventListener('input', function() {
            if (editCodeError && !editCodeError.classList.contains('hidden')) {
                checkEditDuplicateCode();
            }
        });
        editCodeInput.addEventListener('blur', function() {
            checkEditDuplicateCode();
        });
    }

    if (editVehicleForm) {
        editVehicleForm.addEventListener('submit', function(e) {
            if (checkEditDuplicateCode()) {
                e.preventDefault();
                editCodeInput.focus();
                return false;
            }
            if (editUnitTypeSelect && editUnitTypeSelect.value === '__custom__') {
                const val = editCustomUnitType.value.trim().toUpperCase();
                if (!val) {
                    e.preventDefault();
                    alert('Silakan ketik nama tipe alat berat baru.');
                    editCustomUnitType.focus();
                    return false;
                }
                editUnitTypeSelect.removeAttribute('name');
                editCustomUnitType.setAttribute('name', 'unit_type');
            } else if (editUnitTypeSelect) {
                editUnitTypeSelect.setAttribute('name', 'unit_type');
                if (editCustomUnitType) editCustomUnitType.removeAttribute('name');
            }
        });
    }

    function previewVehicleImage(input, imgId, placeholderId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(imgId);
                const placeholder = document.getElementById(placeholderId);
                if (img) {
                    img.src = e.target.result;
                    img.classList.remove('hidden');
                }
                if (placeholder) {
                    placeholder.classList.add('hidden');
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function openAddModal() {
        document.getElementById('addVehicleModal').classList.remove('hidden');
        const addPhotoPreview = document.getElementById('addPhotoPreview');
        const addPhotoPlaceholder = document.getElementById('addPhotoPlaceholder');
        const addPhotoInput = document.getElementById('addPhotoInput');
        if (addPhotoInput) addPhotoInput.value = '';
        if (addPhotoPreview) {
            addPhotoPreview.src = '';
            addPhotoPreview.classList.add('hidden');
        }
        if (addPhotoPlaceholder) {
            addPhotoPlaceholder.classList.remove('hidden');
        }

        // Auto-set initial group & type based on current view
        const currentGroup = "{{ $selectedGroup ?? '' }}";
        const currentType = "{{ $selectedType ?? '' }}";
        const groupSelect = document.getElementById('addOperationalGroup');
        if (groupSelect) {
            if (currentGroup) {
                groupSelect.value = currentGroup;
            } else if (!groupSelect.value) {
                groupSelect.value = 'HAULING';
            }
            populateTypeSelect('addOperationalGroup', 'addUnitTypeSelect', 'addCustomUnitType', currentType);
        }

        const titleEl = document.getElementById('addModalTitle');
        if (titleEl) {
            if (currentType) {
                titleEl.innerText = `Tambah Unit Fisik (${currentType})`;
            } else if (currentGroup) {
                titleEl.innerText = `Tambah Unit Alat Berat (${currentGroup})`;
            } else {
                titleEl.innerText = 'Tambah Unit Alat Berat';
            }
        }

        if (addCodeInput) addCodeInput.focus();
    }

    function closeAddModal() {
        document.getElementById('addVehicleModal').classList.add('hidden');
        hideCodeError();
    }

    function openEditModal(v) {
        document.getElementById('editVehicleModal').classList.remove('hidden');
        document.getElementById('editVehicleForm').action = `/qma/vehicles/${v.id}`;
        document.getElementById('editCode').value = v.code || '';
        currentEditVehicleCode = (v.code || '').trim().toUpperCase();
        hideEditCodeError();

        const editGroupSelect = document.getElementById('editOperationalGroup');
        if (editGroupSelect) {
            editGroupSelect.value = v.operational_group || 'SUPPORT';
        }
        populateTypeSelect('editOperationalGroup', 'editUnitTypeSelect', 'editCustomUnitType', v.unit_type || v.unit_category || '');

        if (document.getElementById('editLicensePlate')) {
            document.getElementById('editLicensePlate').value = v.license_plate || '';
        }
        if (document.getElementById('editBrand')) {
            document.getElementById('editBrand').value = v.brand || '';
        }
        if (document.getElementById('editModelNumber')) {
            document.getElementById('editModelNumber').value = v.model_number || '';
        }
        if (document.getElementById('editSerialNumber')) {
            document.getElementById('editSerialNumber').value = v.serial_number || '';
        }
        if (document.getElementById('editProductionYear')) {
            document.getElementById('editProductionYear').value = v.production_year || '';
        }
        if (document.getElementById('editLocation')) {
            document.getElementById('editLocation').value = v.location || '';
        }
        if (document.getElementById('editSiteStatus')) {
            document.getElementById('editSiteStatus').value = v.site_status || 'inside_site';
        }
        if (document.getElementById('editStatus')) {
            document.getElementById('editStatus').value = v.status || 'Operation';
        }
        if (document.getElementById('editCurrentKm')) {
            document.getElementById('editCurrentKm').value = v.current_km !== null ? v.current_km : 0;
        }
        if (document.getElementById('editOwnership')) {
            document.getElementById('editOwnership').value = v.ownership || '';
        }
        if (document.getElementById('editFuelConsumption')) {
            document.getElementById('editFuelConsumption').value = v.fuel_consumption || '';
        }

        const divSelect = document.getElementById('editDivision');
        if (divSelect) {
            if (v.division) {
                let found = false;
                for (let i = 0; i < divSelect.options.length; i++) {
                    if (divSelect.options[i].value === v.division) {
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    const newOpt = document.createElement('option');
                    newOpt.value = v.division;
                    newOpt.textContent = v.division;
                    divSelect.appendChild(newOpt);
                }
                divSelect.value = v.division;
            } else {
                divSelect.value = '';
            }
        }

        const editPhotoPreview = document.getElementById('editPhotoPreview');
        const editPhotoPlaceholder = document.getElementById('editPhotoPlaceholder');
        const editPhotoInput = document.getElementById('editPhotoInput');
        if (editPhotoInput) editPhotoInput.value = '';
        if (v.photo_url) {
            if (editPhotoPreview) {
                editPhotoPreview.src = v.photo_url;
                editPhotoPreview.classList.remove('hidden');
            }
            if (editPhotoPlaceholder) editPhotoPlaceholder.classList.add('hidden');
        } else {
            if (editPhotoPreview) {
                editPhotoPreview.src = '';
                editPhotoPreview.classList.add('hidden');
            }
            if (editPhotoPlaceholder) editPhotoPlaceholder.classList.remove('hidden');
        }
    }

    function closeEditModal() {
        document.getElementById('editVehicleModal').classList.add('hidden');
        hideEditCodeError();
    }

    function openDetailModal(v) {
        document.getElementById('detailVehicleModal').classList.remove('hidden');
        document.getElementById('detailCodeBadge').innerText = v.code || '-';
        document.getElementById('detailGroupBadge').innerText = v.operational_group || 'HEAVY EQUIPMENT';
        document.getElementById('detailTypeTitle').innerText = v.unit_type || v.unit_category || (v.brand_type || '-');
        
        // Status Badge
        const statusBadge = document.getElementById('detailStatusBadge');
        if (statusBadge) {
            const st = v.status || 'Operation';
            statusBadge.innerText = st;
            if (st === 'Operation') {
                statusBadge.className = 'px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200';
            } else if (st === 'Standby') {
                statusBadge.className = 'px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200';
            } else if (st === 'Breakdown') {
                statusBadge.className = 'px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200';
            } else {
                statusBadge.className = 'px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200';
            }
        }

        // Area Badge
        const areaBadge = document.getElementById('detailAreaBadge');
        if (areaBadge) {
            const isOut = v.site_status === 'outside_site';
            areaBadge.innerText = isOut ? 'Di Luar Site' : 'Di Dalam Site';
            areaBadge.className = isOut 
                ? 'px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200' 
                : 'px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200';
        }

        // Brand & Model
        const brandModel = [v.brand, v.model_number].filter(Boolean).join(' ') || (v.brand_type || '-');
        document.getElementById('detailBrandModel').innerText = brandModel;
        document.getElementById('detailSerialNumber').innerText = v.serial_number || '-';
        document.getElementById('detailProductionYear').innerText = v.production_year ? `Tahun ${v.production_year}` : '-';
        document.getElementById('detailLicensePlate').innerText = v.license_plate || '-';
        document.getElementById('detailLocation').innerText = v.location || 'Site Kerinci';
        document.getElementById('detailCurrentKm').innerText = v.current_km !== null ? `${v.current_km} HM / KM` : '-';
        document.getElementById('detailDivision').innerText = v.division || '-';
        document.getElementById('detailOwnership').innerText = v.ownership || 'PT Kerinci Merangin Hidro';
        document.getElementById('detailFuel').innerText = v.fuel_consumption ? `${v.fuel_consumption} Liter / Jam` : 'Tidak tercatat';

        const detailPhotoContainer = document.getElementById('detailPhotoContainer');
        const detailPhoto = document.getElementById('detailPhoto');
        if (v.photo_url) {
            if (detailPhoto) detailPhoto.src = v.photo_url;
            if (detailPhotoContainer) detailPhotoContainer.classList.remove('hidden');
        } else {
            if (detailPhoto) detailPhoto.src = '';
            if (detailPhotoContainer) detailPhotoContainer.classList.add('hidden');
        }
    }

    function closeDetailModal() {
        document.getElementById('detailVehicleModal').classList.add('hidden');
    }

    function openDeleteModal(id, code, brand) {
        document.getElementById('deleteVehicleForm').action = `/qma/vehicles/${id}`;
        document.getElementById('deleteVehicleCode').innerText = code + (brand ? ` (${brand})` : '');
        document.getElementById('deleteVehicleModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteVehicleModal').classList.add('hidden');
    }

    @if($errors->any())
        document.addEventListener('DOMContentLoaded', function() {
            openAddModal();
        });
    @endif

    // Buka & Tutup Modal Panduan Halaman
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

    // Dropdown Menu Ekspor Heavy Equipment
    function toggleSupportExportMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('exportMenuSupport');
        if (menu) {
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }
    }

    document.addEventListener('click', function(event) {
        const container = document.getElementById('exportSupportDropdownContainer');
        const menu = document.getElementById('exportMenuSupport');
        if (container && !container.contains(event.target)) {
            if (menu) {
                menu.style.display = 'none';
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGuideModal();
            const exportMenu = document.getElementById('exportMenuSupport');
            if (exportMenu) exportMenu.style.display = 'none';
        }
    });
</script>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (HEAVY EQUIPMENT)                            -->
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
                    <p class="text-xs text-slate-500">Master Data Heavy Equipment</p>
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
                Selamat datang di menu <strong>Heavy Equipment</strong>! Halaman ini adalah pusat pengelolaan master armada alat berat dan kendaraan pendukung operasional PT Kerinci Merangin Hidro. Di halaman ini Anda dapat:
            </p>

            <div class="space-y-3">
                <!-- Fitur 1 (Hijau) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-emerald-200/50">
                        <i class="fa-solid fa-tractor"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Master Data Alat Berat & Support</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Mendaftarkan armada alat berat, excavator, crane, loader, atau forklift dengan data nomor unit, nomor seri, dan merek.
                        </p>
                    </div>
                </div>

                <!-- Fitur 2 (Biru) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-blue-200/50">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Kepatuhan Izin SILO & KIR</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Memantau tanggal kedaluwarsa Surat Izin Laik Operasi (SILO) dan uji KIR untuk menjamin keselamatan kerja operasional lapangan.
                        </p>
                    </div>
                </div>

                <!-- Fitur 3 (Amber) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-amber-200/50">
                        <i class="fa-solid fa-wrench"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Pengawasan Penanggung Jawab & Status</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Menghubungkan unit dengan PIC pengawas lapangan dan memperbarui status kesiapan (Operasional / Perbaikan / Standby).
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

<style>
    /* Hilangkan scrollbar default pada modal tapi tetap bisa di-scroll secara halus */
    .no-scrollbar {
        -ms-overflow-style: none; /* IE and Edge */
        scrollbar-width: none; /* Firefox */
    }
    .no-scrollbar::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
        width: 0px;
        height: 0px;
    }
</style>
@endsection
