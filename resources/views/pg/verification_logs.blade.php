@extends('layouts.app')

@section('title', 'Log Riwayat Verifikasi Gerbang - Admin Private Guard | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Judul & Tombol Aksi Cepat -->
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
                        title="Panduan Halaman Log Verifikasi Gerbang">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                Log Riwayat Verifikasi Gerbang
            </h2>
        </div>

        <div class="grid grid-cols-2 gap-2.5 w-full sm:flex sm:items-center sm:gap-3 sm:w-auto">
            <!-- Dropdown Menu Ekspor Format it.allrole.web.id -->
            <div class="relative inline-block w-full sm:w-auto" id="exportLogDropdownContainer">
                <button type="button" 
                        onclick="toggleLogExportMenu(event)" 
                        id="btnLogExportTrigger"
                        class="w-full sm:w-auto flex items-center justify-center space-x-2 border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                    <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                    <span class="truncate">Ekspor Log</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
                </button>
                <div id="exportMenuLog" style="display: none;" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 z-50 overflow-hidden">
                    <a href="{{ route('pg.logs.export_pdf', request()->query()) }}" target="_blank" class="flex items-center px-4 py-3 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#1b5e20] font-bold transition">
                        <i class="fa-regular fa-file-pdf text-rose-600 text-sm mr-2.5"></i>
                        <span>Format PDF (Cetak)</span>
                    </a>
                    <div class="h-px bg-slate-100 mx-2"></div>
                    <a href="{{ route('pg.logs.export_excel', request()->query()) }}" class="flex items-center px-4 py-3 text-xs text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i class="fa-regular fa-file-excel text-emerald-600 text-sm mr-2.5"></i>
                        <span>Format Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            <a href="{{ route('pg.guards.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1b5e20] hover:bg-[#144919] shadow-sm transition group cursor-pointer">
                <i class="fa-solid fa-user-shield mr-2 group-hover:scale-110 transition-transform"></i>
                <span class="truncate">Kelola Data Satpam</span>
            </a>
        </div>
    </div>


    @php
        $filterKeys = ['team', 'security_guard_id', 'gate_id', 'direction', 'status', 'start_date', 'end_date'];
        $activeFilterCount = count(array_filter(request()->only($filterKeys)));
        $isFilterOpen = request()->anyFilled($filterKeys);
    @endphp

    <!-- Form Filter & Pencarian Cepat (Aturan Standar: Separated Search Bar & Collapsible Filter Card) -->
    <form method="GET" action="{{ route('pg.logs.index') }}" id="verificationLogFilterForm" class="space-y-3">
        <!-- Level 1: Quick Search Bar Mandiri di Luar Card Filter & Tombol Toggle -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <!-- Search Bar Utama (Real-Time Server Search) -->
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
                <input type="text" 
                       name="search" 
                       id="verificationLogSearchInput"
                       value="{{ request('search') }}" 
                       placeholder="Cari TRX, Plat, Driver, Satpam, Catatan..." 
                       class="w-full text-xs rounded-xl border border-slate-200 bg-white hover:border-slate-300 focus:bg-white pl-9 pr-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition shadow-2xs">
            </div>

            <!-- Tombol Buka/Tutup Filter Card & Pilihan Per Halaman -->
            <div class="flex items-center gap-2.5 self-end sm:self-auto w-full sm:w-auto justify-between sm:justify-start">
                <!-- Filter Tampilan Per Halaman -->
                <select name="per_page" 
                        onchange="this.form.submit()" 
                        class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] shadow-2xs transition truncate">
                    <option value="20" {{ request('per_page', '20') == '20' ? 'selected' : '' }}>20 / Hal</option>
                    <option value="50" {{ request('per_page', '50') == '50' ? 'selected' : '' }}>50 / Hal</option>
                    <option value="100" {{ request('per_page', '100') == '100' ? 'selected' : '' }}>Semua (100)</option>
                </select>

                <!-- Tombol Submit Pencarian -->
                <button type="submit" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition shadow-2xs cursor-pointer whitespace-nowrap">
                    Cari
                </button>

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
                    @if(request()->anyFilled(['search', 'team', 'security_guard_id', 'gate_id', 'direction', 'status', 'start_date', 'end_date', 'per_page']))
                        <a href="{{ route('pg.logs.index') }}" 
                           id="headerResetFilterBtn" 
                           class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center space-x-1 transition px-3 py-2 rounded-xl hover:bg-rose-50 border border-transparent hover:border-rose-100">
                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                            <span>Reset Filter</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Level 2: Collapsible Filter Card Lanjutan -->
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

            <!-- Baris Input Filter (Grid 12 Kolom) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5">
                <!-- Filter Regu Satpam (Regu 1, 2, 3) -->
                <div class="lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Regu Satpam
                    </label>
                    <select name="team" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Regu</option>
                        <option value="1" {{ request('team') == '1' ? 'selected' : '' }}>Regu 1</option>
                        <option value="2" {{ request('team') == '2' ? 'selected' : '' }}>Regu 2</option>
                        <option value="3" {{ request('team') == '3' ? 'selected' : '' }}>Regu 3</option>
                    </select>
                </div>

                <!-- Filter Petugas Satpam Spesifik -->
                <div class="lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Petugas Satpam
                    </label>
                    <select name="security_guard_id" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Petugas Satpam</option>
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}" {{ request('security_guard_id') == $guard->id ? 'selected' : '' }}>
                                {{ $guard->badge_id }} - {{ $guard->name }} (Regu {{ $guard->team }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Pos Gerbang -->
                <div class="lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Pos Gerbang
                    </label>
                    <select name="gate_id" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Gerbang</option>
                        @foreach($gates as $g)
                            <option value="{{ $g->id }}" {{ request('gate_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Arah Gerbang -->
                <div class="lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Arah Gerbang
                    </label>
                    <select name="direction" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Arah</option>
                        <option value="in" {{ request('direction') === 'in' ? 'selected' : '' }}>Masuk (In)</option>
                        <option value="out" {{ request('direction') === 'out' ? 'selected' : '' }}>Keluar (Out)</option>
                    </select>
                </div>

                <!-- Filter Status Verifikasi -->
                <div class="lg:col-span-4">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Status Verifikasi
                    </label>
                    <select name="status" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Status</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui (Approved)</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu (Pending)</option>
                        <option value="cancelled_system" {{ request('status') === 'cancelled_system' ? 'selected' : '' }}>Batal Sistem (Timeout)</option>
                        <option value="cancelled_driver" {{ request('status') === 'cancelled_driver' ? 'selected' : '' }}>Dibatalkan Pengemudi</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Semua Dibatalkan</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak (Rejected)</option>
                    </select>
                </div>

                <!-- Rentang Mulai Tanggal -->
                <div class="lg:col-span-4">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Mulai Tanggal
                    </label>
                    <input type="date" 
                           name="start_date" 
                           value="{{ request('start_date', $startDate ?? now()->format('Y-m-d')) }}" 
                           onchange="this.form.submit()"
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Rentang Sampai Tanggal -->
                <div class="lg:col-span-4">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Sampai Tanggal
                    </label>
                    <input type="date" 
                           name="end_date" 
                           value="{{ request('end_date', $endDate ?? now()->format('Y-m-d')) }}" 
                           onchange="this.form.submit()"
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>
            </div>
        </div>
    </form>

    <!-- Tabel Lengkap Log Verifikasi Gerbang (Navy Header #06213f) -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <!-- Header Navy Solid #06213f -->
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">WAKTU VERIFIKASI</th>
                        <th class="py-3.5 px-4">NO. TRANSAKSI</th>
                        <th class="py-3.5 px-4">PETUGAS SATPAM</th>
                        <th class="py-3.5 px-4">POS GERBANG</th>
                        <th class="py-3.5 px-4">KENDARAAN</th>
                        <th class="py-3.5 px-4">DRIVER / PENGEMUDI</th>
                        <th class="py-3.5 px-4 text-center">ARAH</th>
                        <th class="py-3.5 px-4 text-right">ODOMETER</th>
                        <th class="py-3.5 px-4 text-center">STATUS & CATATAN</th>
                        @can('pg.logs.delete')
                            <th class="py-3.5 px-4 text-center whitespace-nowrap">AKSI</th>
                        @endcan
                    </tr>
                </thead>
                <tbody id="verificationLogTableBody" class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($activities as $index => $item)
                        @php
                            $rowNum = $activities->firstItem() ? ($activities->firstItem() + $index) : ($index + 1);
                            $validatedTime = $item->validated_at ?? $item->recorded_at;
                        @endphp
                        <tr class="verification-row hover:bg-slate-50/80 transition-colors {{ $loop->even ? 'bg-slate-50/30' : 'bg-white' }}">
                            <!-- No -->
                            <td class="py-3 px-4 text-center text-slate-400 font-bold">
                                {{ $rowNum }}
                            </td>

                            <!-- Waktu Verifikasi -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($validatedTime)
                                    <div class="font-bold text-slate-900">
                                        {{ $validatedTime->locale('id')->translatedFormat('d M Y') }}
                                    </div>
                                    <div class="text-xs text-slate-400 font-medium mt-0.5">
                                        {{ $validatedTime->format('H:i') }} WIB
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- No Transaksi -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-800 font-mono font-bold text-[11px] border border-slate-200">
                                    <i class="fa-solid fa-qrcode text-slate-400 text-[10px]"></i>
                                    {{ $item->transaction_no }}
                                </span>
                            </td>

                            <!-- Petugas Satpam Pemeriksa -->
                            <td class="py-3 px-4">
                                @if($item->securityGuard)
                                    <div class="flex items-center space-x-2">
                                        <div>
                                            <p class="font-bold text-slate-900 leading-tight">
                                                {{ $item->securityGuard->name }}
                                            </p>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[10px] text-slate-500 font-mono">
                                                    {{ $item->securityGuard->badge_id }}
                                                </span>
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold {{ $item->securityGuard->team == 1 ? 'bg-blue-100 text-blue-700' : ($item->securityGuard->team == 2 ? 'bg-amber-100 text-amber-700' : 'bg-purple-100 text-purple-700') }}">
                                                    Regu {{ $item->securityGuard->team }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">- Belum ada -</span>
                                @endif
                            </td>

                            <!-- Pos Gerbang -->
                            <td class="py-3 px-4 whitespace-nowrap font-bold text-slate-800">
                                <span>{{ $item->gate ? $item->gate->name : '-' }}</span>
                            </td>

                            <!-- Kendaraan -->
                            <td class="py-3 px-4">
                                @if($item->vehicle)
                                    <div class="flex items-center space-x-2.5">
                                        @if($item->vehicle->photo_url)
                                            <img src="{{ $item->vehicle->photo_url }}" alt="{{ $item->vehicle->brand_type }}" class="w-8 h-8 rounded-lg object-cover border border-slate-200 shadow-2xs flex-shrink-0">
                                        @else
                                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center text-xs flex-shrink-0">
                                                <i class="fa-solid fa-car"></i>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 truncate">
                                                {{ $item->vehicle->license_plate }}
                                            </div>
                                            <div class="text-[11px] text-slate-500 truncate">
                                                {{ $item->vehicle->brand_type }} ({{ $item->vehicle->code }})
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Driver / Pengemudi -->
                            <td class="py-3 px-4">
                                @if($item->driver)
                                    <div class="flex items-center space-x-2.5">
                                        @if($item->driver->photo_url)
                                            <img src="{{ $item->driver->photo_url }}" alt="{{ $item->driver->name }}" class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-2xs flex-shrink-0">
                                        @else
                                            <div class="w-8 h-8 rounded-full bg-emerald-100 text-[#1b5e20] font-black text-xs flex items-center justify-center flex-shrink-0">
                                                {{ strtoupper(substr($item->driver->name, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 truncate flex items-center gap-1.5 flex-wrap">
                                                <span>{{ $item->driver->name }}</span>
                                                @if($item->driver->simpers && $item->driver->simpers->isNotEmpty())
                                                    @foreach($item->driver->simpers->take(2) as $s)
                                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            {{ $s->simperType?->code ?? 'SIMPER' }}
                                                        </span>
                                                    @endforeach
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-slate-500 font-mono truncate">
                                                {{ $item->driver->nik }}
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Arah Gerbang -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($item->direction === 'in')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-arrow-down-left text-[10px]"></i>
                                        <span>Masuk</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fa-solid fa-arrow-up-right text-[10px]"></i>
                                        <span>Keluar</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Odometer -->
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono">
                                <div class="font-bold text-slate-900">
                                    {{ number_format($item->current_km, 0, ',', '.') }} KM
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    Awal: {{ number_format($item->previous_km, 0, ',', '.') }} KM
                                    @if($item->distance > 0)
                                        <span class="text-emerald-600 font-bold">(+{{ number_format($item->distance, 0, ',', '.') }})</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Status & Catatan -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($item->validation_status === 'approved')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                        <i class="fa-solid fa-check text-[10px]"></i>
                                        <span>Disetujui</span>
                                    </span>
                                @elseif($item->validation_status === 'cancelled')
                                    @if($item->isCancelledBySystem())
                                        <div>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800" title="{{ $item->rejection_note }}">
                                                <i class="fa-solid fa-robot text-[10px] text-amber-600"></i>
                                                <span>Batal Sistem</span>
                                            </span>
                                            <p class="text-[10px] text-amber-700 max-w-xs truncate mx-auto mt-0.5">
                                                Timeout > 5 Jam
                                            </p>
                                        </div>
                                    @else
                                        <div>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700" title="{{ $item->rejection_note }}">
                                                <i class="fa-solid fa-user-xmark text-[10px] text-slate-500"></i>
                                                <span>Batal Pengemudi</span>
                                            </span>
                                            @if($item->rejection_note)
                                                <p class="text-[10px] text-slate-500 max-w-xs truncate mx-auto mt-0.5" title="{{ $item->rejection_note }}">
                                                    {{ $item->rejection_note }}
                                                </p>
                                            @endif
                                        </div>
                                    @endif
                                @elseif($item->validation_status === 'rejected')
                                    <div>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800" title="{{ $item->rejection_note }}">
                                            <i class="fa-solid fa-xmark text-[10px]"></i>
                                            <span>Ditolak</span>
                                        </span>
                                        @if($item->rejection_note)
                                            <p class="text-[10px] text-rose-600 max-w-xs truncate mx-auto mt-0.5" title="{{ $item->rejection_note }}">
                                                {{ $item->rejection_note }}
                                            </p>
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                        <i class="fa-regular fa-clock text-[10px]"></i>
                                        <span>Menunggu</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi Hapus Log (RBAC Protected) -->
                            @can('pg.logs.delete')
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <button type="button"
                                        onclick="confirmDeleteVerificationLog({{ $item->id }}, '{{ $item->transaction_no }}', '{{ $item->vehicle?->code ?? '-' }}')"
                                        class="inline-flex items-center space-x-1 px-2.5 py-1.5 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 hover:border-rose-300 transition cursor-pointer shadow-2xs"
                                        title="Hapus Catatan Log Ini">
                                    <i class="fa-regular fa-trash-can text-rose-600 text-[11px]"></i>
                                    <span>Hapus</span>
                                </button>
                            </td>
                            @endcan
                        </tr>
                    @empty
                        <tr id="serverVerificationEmptyRow">
                            <td colspan="{{ auth()->user()->can('pg.logs.delete') ? 11 : 10 }}" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-lg">
                                        <i class="fa-solid fa-clipboard-question"></i>
                                    </div>
                                    @if(request('search'))
                                        <p class="text-sm font-semibold text-slate-700">
                                            <span class="inline-block mr-1">🔍</span>Data "<span class="text-rose-600 font-bold">{{ request('search') }}</span>" tidak ditemukan di tabel
                                        </p>
                                        <p class="text-xs text-slate-400 mt-1">Pastikan ejaan nomor transaksi, plat kendaraan, nama pengemudi, atau petugas sudah benar.</p>
                                    @elseif(request()->anyFilled($filterKeys))
                                        <p class="text-sm font-semibold text-slate-700">Tidak ada data log verifikasi gerbang yang sesuai kriteria filter.</p>
                                        <p class="text-xs text-slate-400 mt-1">Silakan sesuaikan kembali kriteria filter tanggal, pos gerbang, regu, atau status.</p>
                                    @else
                                        <p class="text-sm font-bold text-slate-600">Belum ada catatan log riwayat verifikasi gerbang</p>
                                        <p class="text-xs text-slate-400">Aktivitas kendaraan yang diverifikasi oleh petugas Satpam akan tercatat otomatis di sini.</p>
                                    @endif

                                    @if(request()->anyFilled(array_merge(['search'], $filterKeys)))
                                        <div class="mt-4">
                                            <a href="{{ route('pg.logs.index') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition">
                                                <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                                <span>Reset Filter</span>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if($activities->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $activities->links() }}
            </div>
        @endif
    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (LOG VERIFIKASI GERBANG)                     -->
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
                    <p class="text-xs text-slate-500">Log Riwayat Verifikasi Gerbang</p>
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
                Selamat datang di halaman <strong>Log Riwayat Verifikasi Gerbang</strong>! Halaman ini adalah rekapitulasi lengkap hasil inspeksi fisik armada oleh petugas Satpam di pos gerbang. Di halaman ini Anda dapat:
            </p>

            <div class="space-y-3">
                <!-- Fitur 1 (Hijau) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-emerald-200/50">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Riwayat Perlintasan Kendaraan</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Melihat log keluar/masuk kendaraan secara kronologis lengkap dengan waktu perlintasan, nomor polisi, dan driver pengemudi.
                        </p>
                    </div>
                </div>

                <!-- Fitur 2 (Biru) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-blue-200/50">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Status Validasi & Catatan Penolakan</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Memeriksa status validasi (Disetujui / Ditolak) beserta alasan penolakan jika kondisi fisik kendaraan atau izin kerja tidak memenuhi standar.
                        </p>
                    </div>
                </div>

                <!-- Fitur 3 (Amber) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-amber-200/50">
                        <i class="fa-solid fa-filter"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Penyaringan Multi-Kriteria</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Saring data log berdasarkan rentang tanggal, regu tugas, petugas satpam pemeriksa, pos gerbang, maupun arah masuk/keluar.
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
    // Dropdown Menu Ekspor Log Verifikasi Gerbang
    function toggleLogExportMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('exportMenuLog');
        if (menu) {
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }
    }

    document.addEventListener('click', function(e) {
        const container = document.getElementById('exportLogDropdownContainer');
        const menu = document.getElementById('exportMenuLog');
        if (menu && container && !container.contains(e.target)) {
            menu.style.display = 'none';
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

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('verificationLogSearchInput');
        const filterForm = document.getElementById('verificationLogFilterForm');
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
    });

    // Panduan Halaman Modal
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
            closeDeleteVerificationLogModal();
        }
    });

    // Modal Konfirmasi Hapus Log Verifikasi Gerbang
    function confirmDeleteVerificationLog(id, trxNo, vehicleCode) {
        const textEl = document.getElementById('deleteLogTrxText');
        const codeEl = document.getElementById('deleteLogCodeText');
        const form = document.getElementById('deleteVerificationLogForm');
        if (textEl) textEl.textContent = trxNo;
        if (codeEl) codeEl.textContent = vehicleCode;
        if (form) form.action = "{{ url('/pg/verification-logs') }}/" + id;

        const modal = document.getElementById('deleteVerificationLogModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeDeleteVerificationLogModal() {
        const modal = document.getElementById('deleteVerificationLogModal');
        if (modal) modal.style.display = 'none';
    }
</script>

@can('pg.logs.delete')
<!-- Modal Konfirmasi Hapus Log Verifikasi Gerbang PG -->
<div id="deleteVerificationLogModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
        <div class="p-6 text-center">
            <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4 border border-rose-100 text-2xl">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900">Konfirmasi Hapus Log Gerbang</h3>
            <p class="text-xs text-slate-500 mt-1.5">
                Apakah Anda yakin ingin menghapus data perlintasan <strong id="deleteLogTrxText" class="text-slate-800 font-mono"></strong> pada kendaraan <strong id="deleteLogCodeText" class="text-emerald-700 font-mono"></strong>?
            </p>
            <p class="text-[11px] text-amber-700 bg-amber-50 p-2.5 rounded-xl border border-amber-200 mt-3 text-left">
                <i class="fa-solid fa-circle-info mr-1 text-amber-600"></i>
                Tindakan ini permanen dan akan dicatat ke dalam riwayat audit log sistem.
            </p>
        </div>
        <form id="deleteVerificationLogForm" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="bg-slate-50 px-6 py-4 flex items-center justify-end space-x-2 border-t border-slate-100">
                <button type="button" onclick="closeDeleteVerificationLogModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-200 transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 transition cursor-pointer shadow-sm">
                    <i class="fa-regular fa-trash-can mr-1.5"></i> Ya, Hapus Log
                </button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection
