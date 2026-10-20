@extends('layouts.app')

@section('title', 'Manajemen Unit - Admin QMA | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Judul & Tombol Aksi -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-1.5 mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                    MANAJEMEN QMA
                </span>
                <!-- Tombol Info Panduan Halaman -->
                <button type="button" 
                        onclick="openGuideModal()" 
                        class="w-4 h-4 rounded-full bg-slate-200/90 hover:bg-slate-300 text-slate-500 hover:text-slate-800 text-[9px] inline-flex items-center justify-center font-bold transition shadow-2xs cursor-pointer focus:outline-none" 
                        title="Panduan Manajemen Unit">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Manajemen Unit
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Monitoring posisi terkini, tracking pergerakan, dan audit log riwayat lokasi armada LV & Unit Support.
            </p>
        </div>

        <!-- Tombol Aksi Kanan -->
        <div class="flex items-center space-x-2.5 w-full sm:w-auto">
            <!-- Dropdown Menu Ekspor -->
            <div class="relative inline-block w-full sm:w-auto" id="exportDropdownContainer">
                <button type="button" 
                        onclick="toggleExportMenu(event)" 
                        id="btnExportTrigger"
                        class="w-full sm:w-auto flex items-center justify-center space-x-2 border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3.5 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                    <i class="fa-solid fa-file-export text-emerald-700 text-sm"></i>
                    <span class="truncate">Ekspor Posisi</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
                </button>
                <div id="exportMenu" style="display: none;" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 z-50 overflow-hidden">
                    <a href="{{ route('qma.unit_management.export_pdf', request()->query()) }}" target="_blank" class="flex items-center px-4 py-3 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#1b5e20] font-bold transition">
                        <i class="fa-regular fa-file-pdf text-rose-600 text-sm mr-2.5"></i>
                        <span>Format PDF (Cetak)</span>
                    </a>
                    <div class="h-px bg-slate-100 mx-2"></div>
                    <a href="{{ route('qma.unit_management.export_excel', request()->query()) }}" class="flex items-center px-4 py-3 text-xs text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i class="fa-regular fa-file-excel text-emerald-600 text-sm mr-2.5"></i>
                        <span>Format Excel (.xlsx)</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Ringkasan Posisi & Kesiapan Unit -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
        <!-- 1. Total Unit -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Unit</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($totalUnits, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-slate-400 font-medium">Armada Aktif Terdata</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 text-lg">
                <i class="fa-solid fa-car-side"></i>
            </div>
        </div>

        <!-- 2. Di Dalam Site -->
        <div class="bg-white p-4 rounded-2xl border border-emerald-200/80 shadow-xs flex items-center justify-between bg-gradient-to-br from-white to-emerald-50/40">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Di Dalam Site</p>
                <h3 class="text-2xl font-black text-emerald-800 mt-1">{{ number_format($insideCount, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-emerald-600 font-semibold flex items-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span>
                    Area Proyek KMH
                </span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-100/80 flex items-center justify-center text-[#1b5e20] text-lg">
                <i class="fa-solid fa-building-shield"></i>
            </div>
        </div>

        <!-- 3. Di Luar Site -->
        <div class="bg-white p-4 rounded-2xl border border-amber-200/80 shadow-xs flex items-center justify-between bg-gradient-to-br from-white to-amber-50/40">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Di Luar Site</p>
                <h3 class="text-2xl font-black text-amber-800 mt-1">{{ number_format($outsideCount, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-amber-600 font-semibold flex items-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1"></span>
                    Dinas / Luar Area
                </span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-100/80 flex items-center justify-center text-amber-700 text-lg">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>
        </div>

        <!-- 4. Siap Operasi -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Siap Operasi</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($operationCount, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-emerald-600 font-medium">Status Operation</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-lg">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <!-- 5. Breakdown -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between col-span-2 sm:col-span-1">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Breakdown</p>
                <h3 class="text-2xl font-black text-rose-600 mt-1">{{ number_format($breakdownCount, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-rose-500 font-medium">Butuh Perbaikan</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 text-lg">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs space-y-3.5">
        <!-- Tab Kategori Unit -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-1.5 p-1 bg-slate-100 rounded-xl text-xs font-semibold">
                <a href="{{ route('qma.unit_management.index', array_merge(request()->except('category', 'page'), ['category' => 'all'])) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ !request('category') || request('category') === 'all' ? 'bg-white text-slate-900 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Semua Unit
                </a>
                <a href="{{ route('qma.unit_management.index', array_merge(request()->except('category', 'page'), ['category' => 'light_vehicle'])) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ request('category') === 'light_vehicle' ? 'bg-white text-slate-900 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Light Vehicle (LV)
                </a>
                <a href="{{ route('qma.unit_management.index', array_merge(request()->except('category', 'page'), ['category' => 'unit_support'])) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ request('category') === 'unit_support' ? 'bg-white text-slate-900 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Unit Support
                </a>
            </div>

            <!-- Total Terfilter -->
            <div class="text-xs text-slate-500 font-medium">
                Menampilkan <span class="font-bold text-slate-800">{{ $units->total() }}</span> unit terdaftar
            </div>
        </div>

        <!-- Filter Dropdown & Search Bar -->
        <form method="GET" action="{{ route('qma.unit_management.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2.5 items-center">
            @if(request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif

            <!-- Search Input -->
            <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Cari kode, nopol, merek, lokasi..." 
                       class="w-full text-xs pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
            </div>

            <!-- Filter Status Posisi Site -->
            <div>
                <select name="site_status" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-medium focus:outline-none focus:border-[#1b5e20]">
                    <option value="all" {{ !request('site_status') || request('site_status') === 'all' ? 'selected' : '' }}>Semua Posisi Site</option>
                    <option value="inside_site" {{ request('site_status') === 'inside_site' ? 'selected' : '' }}>🟢 Di Dalam Site</option>
                    <option value="outside_site" {{ request('site_status') === 'outside_site' ? 'selected' : '' }}>🟠 Di Luar Site</option>
                </select>
            </div>

            <!-- Filter Status Operasional -->
            <div>
                <select name="status" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-medium focus:outline-none focus:border-[#1b5e20]">
                    <option value="all" {{ !request('status') || request('status') === 'all' ? 'selected' : '' }}>Semua Status Operasi</option>
                    <option value="Operation" {{ request('status') === 'Operation' ? 'selected' : '' }}>Operation (Siap Jalan)</option>
                    <option value="Standby" {{ request('status') === 'Standby' ? 'selected' : '' }}>Standby</option>
                    <option value="Breakdown" {{ request('status') === 'Breakdown' ? 'selected' : '' }}>Breakdown</option>
                    <option value="Maintenance" {{ request('status') === 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
            </div>

            <!-- Tombol Submit & Reset -->
            <div class="flex items-center space-x-2">
                <button type="submit" class="flex-1 bg-[#1b5e20] hover:bg-[#144919] text-white px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-filter text-[11px]"></i>
                    <span>Terapkan</span>
                </button>

                @if(request('search') || (request('site_status') && request('site_status') !== 'all') || (request('status') && request('status') !== 'all') || (request('category') && request('category') !== 'all'))
                    <a href="{{ route('qma.unit_management.index') }}" class="px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition whitespace-nowrap" title="Reset Semua Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Alert Notifikasi -->
    @if(session('success'))
    <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm"></i>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    @endif

    <!-- Tabel Data Monitoring Posisi Unit -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-[10px] font-bold">
                        <th class="py-3 px-3.5 text-center w-10">NO</th>
                        <th class="py-3 px-3 text-center w-14">FOTO</th>
                        <th class="py-3 px-4">KODE & TIPE UNIT</th>
                        <th class="py-3 px-4">PLAT / NO SERI</th>
                        <th class="py-3 px-4 text-center">POSISI SITE</th>
                        <th class="py-3 px-4">DETAIL LOKASI</th>
                        <th class="py-3 px-4">UPDATE TERAKHIR</th>
                        <th class="py-3 px-3.5 text-center">STATUS OPR</th>
                        <th class="py-3 px-4 text-center w-28">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($units as $idx => $u)
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- No -->
                            <td class="py-3.5 px-3.5 text-center font-semibold text-slate-400">
                                {{ $units->firstItem() + $idx }}
                            </td>

                            <!-- Foto Unit -->
                            <td class="py-3.5 px-3 text-center">
                                @if($u->photo_url)
                                    <button type="button" 
                                            onclick="openPhotoPreview('{{ $u->photo_url }}', '{{ $u->code }} - {{ addslashes($u->brand_type ?? '') }}')"
                                            class="w-10 h-10 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 hover:opacity-90 transition shadow-2xs inline-block cursor-pointer"
                                            title="Klik untuk memperbesar foto unit">
                                        <img src="{{ $u->photo_url }}" 
                                             alt="Foto {{ $u->code }}" 
                                             class="w-full h-full object-cover">
                                    </button>
                                @else
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 mx-auto"
                                         title="Belum ada foto unit">
                                        <i class="fa-solid fa-car text-sm"></i>
                                    </div>
                                @endif
                            </td>

                            <!-- Kode & Tipe Unit -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono font-extrabold text-slate-900 bg-slate-100 px-2 py-0.5 rounded-lg border border-slate-200 text-xs">
                                        {{ $u->code }}
                                    </span>
                                    @if($u->isLightVehicle())
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            LV
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                            Support
                                        </span>
                                    @endif
                                </div>
                                <div class="font-bold text-slate-800 mt-1 line-clamp-1" title="{{ $u->brand_type }}">
                                    {{ $u->brand_type ?: 'Unit Tanpa Merek' }}
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $u->division ?: ($u->ownership ?: 'Operasional KMH') }}
                                </div>
                            </td>

                            <!-- Plat / No Seri -->
                            <td class="py-3.5 px-4 font-mono">
                                @if($u->license_plate)
                                    <span class="font-bold text-slate-800">{{ $u->license_plate }}</span>
                                @elseif($u->serial_number)
                                    <span class="text-slate-600 text-[11px]" title="Nomor Seri">SN: {{ $u->serial_number }}</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Posisi Site -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($u->site_status === 'outside_site')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs">
                                        <i class="fa-solid fa-arrow-right-from-bracket mr-1.5 text-amber-600 text-[10px]"></i>
                                        Di Luar Site
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                                        Di Dalam Site
                                    </span>
                                @endif
                            </td>

                            <!-- Detail Lokasi -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-1.5">
                                    <i class="fa-solid fa-location-dot text-[#1b5e20] text-xs flex-shrink-0"></i>
                                    <span class="font-bold text-slate-900 line-clamp-1" title="{{ $u->location }}">
                                        {{ $u->location ?: 'Belum ditentukan' }}
                                    </span>
                                </div>
                                @if($u->location_note)
                                    <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1 italic" title="{{ $u->location_note }}">
                                        "{{ $u->location_note }}"
                                    </p>
                                @endif
                            </td>

                            <!-- Update Terakhir -->
                            <td class="py-3.5 px-4">
                                @php
                                    $time = $u->location_updated_at ?? $u->updated_at;
                                @endphp
                                @if($time)
                                    <div class="font-semibold text-slate-800 text-[11px]">
                                        {{ $time->diffForHumans() }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ $time->translatedFormat('d M Y, H:i') }}
                                    </div>
                                    @if($u->location_updated_by)
                                        <div class="text-[10px] text-slate-500 flex items-center space-x-1 mt-0.5">
                                            <i class="fa-solid fa-user-check text-[9px] text-slate-400"></i>
                                            <span class="truncate max-w-[120px]">{{ $u->location_updated_by }}</span>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-slate-400 text-[11px]">-</span>
                                @endif
                            </td>

                            <!-- Status Operasional -->
                            <td class="py-3.5 px-3.5 text-center whitespace-nowrap">
                                @if($u->status === 'Operation')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-circle-check mr-1 text-[9px]"></i> Operation
                                    </span>
                                @elseif($u->status === 'Standby')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fa-solid fa-pause mr-1 text-[9px]"></i> Standby
                                    </span>
                                @elseif($u->status === 'Breakdown')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fa-solid fa-triangle-exclamation mr-1 text-[9px]"></i> Breakdown
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                                        {{ $u->status }}
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <!-- Tombol Update Posisi -->
                                    @can('qma.vehicles.edit')
                                    <button type="button" 
                                            data-unit="{{ json_encode([
                                                'id' => $u->id,
                                                'code' => $u->code,
                                                'brand_type' => $u->brand_type,
                                                'license_plate' => $u->license_plate,
                                                'serial_number' => $u->serial_number,
                                                'photo_url' => $u->photo_url,
                                                'site_status' => $u->site_status ?? 'inside_site',
                                                'location' => $u->location ?? '',
                                                'location_note' => $u->location_note ?? '',
                                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                            onclick="openUpdateLocationModalFromBtn(this)"
                                            class="inline-flex items-center space-x-1 text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1.5 rounded-lg transition font-bold text-[11px] cursor-pointer"
                                            title="Update Posisi Unit">
                                        <i class="fa-solid fa-location-pen text-xs"></i>
                                        <span>Update</span>
                                    </button>
                                    @endcan

                                    <!-- Tombol Riwayat Posisi -->
                                    <button type="button" 
                                            data-unit-id="{{ $u->id }}"
                                            onclick="openHistoryModal('{{ $u->id }}')"
                                            class="inline-flex items-center space-x-1 text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-2.5 py-1.5 rounded-lg transition font-medium text-[11px] cursor-pointer"
                                            title="Lihat Riwayat Pergerakan Posisi">
                                        <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                                        <span>Riwayat</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-map-location-dot text-4xl mb-3 block text-slate-300"></i>
                                <p class="text-sm font-semibold text-slate-700">Tidak ada data unit yang sesuai kriteria</p>
                                <p class="text-xs text-slate-400 mt-1">Coba sesuaikan filter pencarian atau reset filter di atas.</p>
                                @if(request()->anyFilled(['search', 'site_status', 'status', 'category']))
                                <div class="mt-4">
                                    <a href="{{ route('qma.unit_management.index') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                        <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                        <span>Hapus Semua Filter</span>
                                    </a>
                                </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($units->hasPages())
        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div>
                Menampilkan data <span class="font-bold text-slate-800">{{ $units->firstItem() }}</span> sampai <span class="font-bold text-slate-800">{{ $units->lastItem() }}</span> dari total <span class="font-bold text-slate-800">{{ $units->total() }}</span> unit
            </div>
            <div>
                {{ $units->links() }}
            </div>
        </div>
        @endif
    </div>

</div>

<!-- ========================================== -->
<!-- 1. MODAL UPDATE POSISI UNIT               -->
<!-- ========================================== -->
<div id="updateLocationModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeUpdateLocationModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/70">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/80 text-[#1b5e20] flex items-center justify-center text-sm">
                        <i class="fa-solid fa-location-pen"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Update Posisi Unit</h3>
                        <p class="text-[11px] text-slate-500">Perbarui posisi site dan detail lokasi penempatan terkini.</p>
                    </div>
                </div>
                <button type="button" onclick="closeUpdateLocationModal()" class="text-slate-400 hover:text-slate-600 text-sm p-1 rounded-lg hover:bg-slate-100 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Form Update Posisi -->
            <form id="updateLocationForm" method="POST" action="" class="p-6 space-y-4">
                @csrf

                <!-- Unit Info Card Summary -->
                <div class="flex items-center space-x-3.5 p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div id="modalUnitPhotoWrapper" class="w-12 h-12 rounded-xl bg-white border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center text-slate-400">
                        <img id="modalUnitPhoto" src="" alt="Unit" class="w-full h-full object-cover hidden">
                        <i id="modalUnitPhotoPlaceholder" class="fa-solid fa-car text-base"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2">
                            <span id="modalUnitCode" class="font-mono font-extrabold text-slate-900 bg-white px-2 py-0.5 rounded border border-slate-200 text-xs">
                                -
                            </span>
                            <span id="modalUnitPlate" class="font-mono text-xs text-slate-600 font-semibold">
                                -
                            </span>
                        </div>
                        <p id="modalUnitBrand" class="text-xs font-bold text-slate-800 truncate mt-1">-</p>
                        <p id="modalCurrentLoc" class="text-[11px] text-slate-500 truncate flex items-center mt-0.5">
                            <i class="fa-solid fa-location-dot text-slate-400 mr-1 text-[10px]"></i>
                            <span>Posisi saat ini: -</span>
                        </p>
                    </div>
                </div>

                <!-- Pilihan Posisi Site (Di Dalam / Di Luar Site) -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-2">
                        Status Posisi Site <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative flex items-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-emerald-50/40 transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50 has-[:checked]:ring-2 has-[:checked]:ring-emerald-600/10">
                            <input type="radio" 
                                   name="site_status" 
                                   value="inside_site" 
                                   id="radioInsideSite" 
                                   class="text-[#1b5e20] focus:ring-[#1b5e20]"
                                   onchange="handleSiteStatusChange('inside_site')">
                            <div class="ml-2.5">
                                <span class="block text-xs font-bold text-slate-800 flex items-center">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5"></span>
                                    Di Dalam Site
                                </span>
                                <span class="block text-[10px] text-slate-500">Area operasional proyek</span>
                            </div>
                        </label>

                        <label class="relative flex items-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-amber-50/40 transition has-[:checked]:border-amber-600 has-[:checked]:bg-amber-50/50 has-[:checked]:ring-2 has-[:checked]:ring-amber-600/10">
                            <input type="radio" 
                                   name="site_status" 
                                   value="outside_site" 
                                   id="radioOutsideSite" 
                                   class="text-amber-600 focus:ring-amber-500"
                                   onchange="handleSiteStatusChange('outside_site')">
                            <div class="ml-2.5">
                                <span class="block text-xs font-bold text-slate-800 flex items-center">
                                    <span class="w-2 h-2 rounded-full bg-amber-500 mr-1.5"></span>
                                    Di Luar Site
                                </span>
                                <span class="block text-[10px] text-slate-500">Dinas, mess, atau bengkel luar</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Input Detail Lokasi (Kombinasi Pilihan Rekomendasi & Isian Bebas Bebas) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-800">
                            Detail Lokasi Penempatan <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[10px] text-slate-400">Pilih rekomendasi atau ketik bebas</span>
                    </div>

                    <!-- Input dengan Datalist -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-map-pin text-xs"></i>
                        </span>
                        <input type="text" 
                               name="location" 
                               id="modalLocationInput"
                               list="locationPresetList"
                               required
                               placeholder="Contoh: Main Dam, Powerhouse, Kota Bangko..." 
                               class="w-full text-xs pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-medium">
                    </div>

                    <!-- Rekomendasi Cepat (Quick Chips) -->
                    <div class="mt-2.5 space-y-1.5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Rekomendasi Cepat:</p>
                        
                        <!-- Chips Di Dalam Site -->
                        <div id="chipsInsideWrapper" class="flex flex-wrap gap-1.5">
                            @foreach($insidePresets as $preset)
                                <button type="button" 
                                        onclick="applyLocationChip('{{ $preset }}', 'inside_site')"
                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold bg-slate-100 hover:bg-emerald-100 hover:text-emerald-800 text-slate-600 transition cursor-pointer">
                                    {{ $preset }}
                                </button>
                            @endforeach
                        </div>

                        <!-- Chips Di Luar Site -->
                        <div id="chipsOutsideWrapper" class="flex flex-wrap gap-1.5">
                            @foreach($outsidePresets as $preset)
                                <button type="button" 
                                        onclick="applyLocationChip('{{ $preset }}', 'outside_site')"
                                        class="px-2 py-1 rounded-lg text-[10px] font-semibold bg-slate-100 hover:bg-amber-100 hover:text-amber-800 text-slate-600 transition cursor-pointer">
                                    {{ $preset }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Catatan / Keterangan Tambahan -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">
                        Catatan / Keperluan <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <textarea name="note" 
                              id="modalNoteInput"
                              rows="2" 
                              placeholder="Keterangan pergerakan, tugas, driver pembawa, atau perbaikan..." 
                              class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition resize-none"></textarea>
                </div>

                <!-- Tombol Aksi Modal -->
                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeUpdateLocationModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="bg-[#1b5e20] hover:bg-[#144919] text-white px-5 py-2 rounded-xl text-xs font-bold transition shadow-xs flex items-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Datalist HTML untuk Rekomendasi Lokasi Global -->
<datalist id="locationPresetList">
    @foreach($locationSuggestions as $loc)
        <option value="{{ $loc }}">
    @endforeach
</datalist>


<!-- ========================================== -->
<!-- 2. MODAL RIWAYAT PERGERAKAN POSISI (LOG)   -->
<!-- ========================================== -->
<div id="historyModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeHistoryModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/70">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100/80 text-blue-700 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Riwayat Pergerakan Posisi</h3>
                        <p class="text-[11px] text-slate-500">Log audit riwayat posisi otomatis (gerbang) & manual QMA.</p>
                    </div>
                </div>
                <button type="button" onclick="closeHistoryModal()" class="text-slate-400 hover:text-slate-600 text-sm p-1 rounded-lg hover:bg-slate-100 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <!-- Unit Header Card -->
                <div id="historyUnitHeader" class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div class="flex items-center space-x-3">
                        <div id="histPhotoWrapper" class="w-11 h-11 rounded-xl bg-white border border-slate-200 overflow-hidden flex items-center justify-center text-slate-400 flex-shrink-0">
                            <img id="histPhoto" src="" alt="Unit" class="w-full h-full object-cover hidden">
                            <i id="histPhotoPlaceholder" class="fa-solid fa-car text-base"></i>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <span id="histCode" class="font-mono font-extrabold text-slate-900 text-xs bg-white px-2 py-0.5 rounded border border-slate-200">
                                    -
                                </span>
                                <span id="histPlate" class="font-mono text-xs text-slate-600 font-semibold">
                                    -
                                </span>
                            </div>
                            <p id="histBrand" class="text-xs font-bold text-slate-800 mt-0.5">-</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span id="histCurrentStatusBadge" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            -
                        </span>
                        <p id="histCurrentLoc" class="text-[11px] font-bold text-slate-700 mt-1">-</p>
                    </div>
                </div>

                <!-- Timeline Riwayat -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                        Catatan Riwayat Lokasi:
                    </h4>

                    <!-- Loading State -->
                    <div id="historyLoading" class="py-8 text-center text-slate-400 space-y-2">
                        <i class="fa-solid fa-circle-notch fa-spin text-2xl text-emerald-600"></i>
                        <p class="text-xs font-medium">Memuat riwayat posisi unit...</p>
                    </div>

                    <!-- Empty State -->
                    <div id="historyEmpty" class="hidden py-8 text-center text-slate-400 space-y-2">
                        <i class="fa-regular fa-folder-open text-3xl text-slate-300"></i>
                        <p class="text-xs font-semibold text-slate-700">Belum ada riwayat tercatat untuk unit ini.</p>
                        <p class="text-[11px] text-slate-400">Riwayat akan tersimpan otomatis saat unit melintasi gerbang atau diperbarui secara manual.</p>
                    </div>

                    <!-- Timeline Content Container -->
                    <div id="historyList" class="hidden space-y-3">
                        <!-- Disuntikkan via JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between text-xs text-slate-500">
                <span id="historyCountText">Total 0 riwayat pergerakan</span>
                <button type="button" onclick="closeHistoryModal()" class="px-4 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 font-bold text-slate-700 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ========================================== -->
<!-- 3. MODAL PREVIEW FOTO UNIT                 -->
<!-- ========================================== -->
<div id="photoPreviewModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-xs transition-opacity" onclick="closePhotoPreview()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-100">
            <div class="flex items-center justify-between px-4 py-3 bg-slate-900 text-white">
                <span id="photoPreviewTitle" class="text-xs font-bold truncate pr-4">-</span>
                <button type="button" onclick="closePhotoPreview()" class="text-slate-400 hover:text-white text-sm">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-2 bg-slate-950 flex items-center justify-center max-h-[75vh] overflow-hidden">
                <img id="photoPreviewImage" src="" alt="Foto Unit" class="max-h-[70vh] w-auto object-contain rounded-lg">
            </div>
        </div>
    </div>
</div>


<!-- ========================================== -->
<!-- 4. MODAL PANDUAN MANAJEMEN UNIT            -->
<!-- ========================================== -->
<div id="guideModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeGuideModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/70">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/80 text-[#1b5e20] flex items-center justify-center text-sm">
                        <i class="fa-solid fa-circle-question"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Panduan Manajemen Unit</h3>
                        <p class="text-[11px] text-slate-500">Cara kerja sistem tracking posisi dan audit pergerakan.</p>
                    </div>
                </div>
                <button type="button" onclick="closeGuideModal()" class="text-slate-400 hover:text-slate-600 text-sm">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6 space-y-4 text-xs text-slate-600 leading-relaxed">
                <div class="space-y-1.5">
                    <h4 class="font-bold text-slate-800 flex items-center text-xs">
                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-[#1b5e20] inline-flex items-center justify-center text-[10px] font-extrabold mr-2">1</span>
                        Status Posisi: Di Dalam vs Di Luar Site
                    </h4>
                    <p class="pl-7 text-slate-500">
                        Setiap unit (LV dan Unit Support) dikelompokkan ke dalam dua status posisi site: 
                        <span class="font-semibold text-emerald-700">Di Dalam Site</span> (sedang berada di area kerja proyek KMH) dan 
                        <span class="font-semibold text-amber-700">Di Luar Site</span> (dinas luar, mess kumun, atau bengkel luar).
                    </p>
                </div>

                <div class="space-y-1.5">
                    <h4 class="font-bold text-slate-800 flex items-center text-xs">
                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-[#1b5e20] inline-flex items-center justify-center text-[10px] font-extrabold mr-2">2</span>
                        Integrasi Otomatis Pos Gerbang (Timesheet)
                    </h4>
                    <p class="pl-7 text-slate-500">
                        Ketika unit melewati pos gerbang dan disetujui oleh Satpam:
                        <br>&bull; Arah <strong>Masuk (IN)</strong>: Posisi otomatis berubah menjadi <span class="font-semibold text-emerald-700">Di Dalam Site</span> dengan lokasi pos gerbang terkait.
                        <br>&bull; Arah <strong>Keluar (OUT)</strong>: Posisi otomatis berubah menjadi <span class="font-semibold text-amber-700">Di Luar Site</span> sesuai keperluan perjalanan pengemudi.
                    </p>
                </div>

                <div class="space-y-1.5">
                    <h4 class="font-bold text-slate-800 flex items-center text-xs">
                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-[#1b5e20] inline-flex items-center justify-center text-[10px] font-extrabold mr-2">3</span>
                        Input Pilihan & Isian Bebas
                    </h4>
                    <p class="pl-7 text-slate-500">
                        Admin QMA dapat memperbarui posisi secara manual kapan saja. Formulir menyediakan rekomendasi cepat (Main Dam, Powerhouse, Workshop, dll) sekaligus mendukung <strong>pengetikan lokasi bebas</strong> sesuai kondisi aktual di lapangan.
                    </p>
                </div>

                <div class="space-y-1.5">
                    <h4 class="font-bold text-slate-800 flex items-center text-xs">
                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-[#1b5e20] inline-flex items-center justify-center text-[10px] font-extrabold mr-2">4</span>
                        Audit Log Riwayat Posisi
                    </h4>
                    <p class="pl-7 text-slate-500">
                        Setiap perubahan posisi (baik manual maupun otomatis dari gerbang) tersimpan rapi dalam log riwayat lengkap dengan tanggal, jam, petugas yang mengupdate, dan catatan perjalanannya.
                    </p>
                </div>
            </div>

            <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/70 text-right">
                <button type="button" onclick="closeGuideModal()" class="px-4 py-2 rounded-xl bg-[#1b5e20] hover:bg-[#144919] font-bold text-xs text-white transition">
                    Saya Mengerti
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // 1. Dropdown Ekspor
    function toggleExportMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('exportMenu');
        menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
    }

    document.addEventListener('click', function(e) {
        const menu = document.getElementById('exportMenu');
        const trigger = document.getElementById('btnExportTrigger');
        if (menu && menu.style.display === 'block' && trigger && !trigger.contains(e.target)) {
            menu.style.display = 'none';
        }
    });

    // 2. Modal Panduan
    function openGuideModal() {
        const modal = document.getElementById('guideModal');
        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }
    function closeGuideModal() {
        const modal = document.getElementById('guideModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    // 3. Modal Preview Foto
    function openPhotoPreview(url, title) {
        document.getElementById('photoPreviewImage').src = url;
        document.getElementById('photoPreviewTitle').textContent = title || 'Foto Unit';
        const modal = document.getElementById('photoPreviewModal');
        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }
    function closePhotoPreview() {
        const modal = document.getElementById('photoPreviewModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    // 4. Modal Update Posisi Unit
    function openUpdateLocationModalFromBtn(btn) {
        try {
            const raw = btn.getAttribute('data-unit');
            const unit = JSON.parse(raw);
            openUpdateLocationModal(unit);
        } catch (err) {
            console.error('Error parsing unit data:', err);
        }
    }

    function openUpdateLocationModal(unit) {
        const modal = document.getElementById('updateLocationModal');
        const form = document.getElementById('updateLocationForm');
        form.action = `/qma/unit-management/${unit.id}/location`;

        // Update info kartu ringkasan
        document.getElementById('modalUnitCode').textContent = unit.code || '-';
        document.getElementById('modalUnitPlate').textContent = unit.license_plate || unit.serial_number || '-';
        document.getElementById('modalUnitBrand').textContent = unit.brand_type || 'Unit Tanpa Merek';
        document.getElementById('modalCurrentLoc').innerHTML = `<i class="fa-solid fa-location-dot text-slate-400 mr-1 text-[10px]"></i><span>Posisi saat ini: <strong>${unit.location || 'Belum diisi'}</strong> (${unit.site_status === 'outside_site' ? 'Di Luar Site' : 'Di Dalam Site'})</span>`;

        // Foto preview
        const photoEl = document.getElementById('modalUnitPhoto');
        const placeholderEl = document.getElementById('modalUnitPhotoPlaceholder');
        if (unit.photo_url) {
            photoEl.src = unit.photo_url;
            photoEl.classList.remove('hidden');
            placeholderEl.classList.add('hidden');
        } else {
            photoEl.src = '';
            photoEl.classList.add('hidden');
            placeholderEl.classList.remove('hidden');
        }

        // Set radio status site
        const isOutside = unit.site_status === 'outside_site';
        document.getElementById('radioInsideSite').checked = !isOutside;
        document.getElementById('radioOutsideSite').checked = isOutside;
        handleSiteStatusChange(isOutside ? 'outside_site' : 'inside_site');

        // Set lokasi input
        document.getElementById('modalLocationInput').value = unit.location || '';
        document.getElementById('modalNoteInput').value = unit.location_note || '';

        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }

    function closeUpdateLocationModal() {
        const modal = document.getElementById('updateLocationModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    function handleSiteStatusChange(status) {
        const insideChips = document.getElementById('chipsInsideWrapper');
        const outsideChips = document.getElementById('chipsOutsideWrapper');
        if (status === 'inside_site') {
            insideChips.classList.remove('opacity-40');
            outsideChips.classList.add('opacity-40');
        } else {
            insideChips.classList.add('opacity-40');
            outsideChips.classList.remove('opacity-40');
        }
    }

    function applyLocationChip(locationText, siteStatus) {
        document.getElementById('modalLocationInput').value = locationText;
        if (siteStatus === 'inside_site') {
            document.getElementById('radioInsideSite').checked = true;
            handleSiteStatusChange('inside_site');
        } else if (siteStatus === 'outside_site') {
            document.getElementById('radioOutsideSite').checked = true;
            handleSiteStatusChange('outside_site');
        }
    }

    // 5. Modal Riwayat Posisi Unit (Audit Trail)
    function openHistoryModal(unitId) {
        const modal = document.getElementById('historyModal');
        const loading = document.getElementById('historyLoading');
        const empty = document.getElementById('historyEmpty');
        const list = document.getElementById('historyList');

        modal.classList.remove('hidden');
        modal.style.display = 'block';
        loading.classList.remove('hidden');
        empty.classList.add('hidden');
        list.classList.add('hidden');
        list.innerHTML = '';

        fetch(`/qma/unit-management/${unitId}/history`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(res => {
                if (!res.ok) throw new Error('Gagal memuat riwayat');
                return res.json();
            })
            .then(data => {
                loading.classList.add('hidden');

                // Render Unit Header Info
                const v = data.vehicle;
                document.getElementById('histCode').textContent = v.code;
                document.getElementById('histPlate').textContent = v.license_plate;
                document.getElementById('histBrand').textContent = v.brand_type;
                document.getElementById('histCurrentLoc').textContent = v.location;

                const photoEl = document.getElementById('histPhoto');
                const photoPlaceholder = document.getElementById('histPhotoPlaceholder');
                if (v.photo_url) {
                    photoEl.src = v.photo_url;
                    photoEl.classList.remove('hidden');
                    photoPlaceholder.classList.add('hidden');
                } else {
                    photoEl.src = '';
                    photoEl.classList.add('hidden');
                    photoPlaceholder.classList.remove('hidden');
                }

                const badge = document.getElementById('histCurrentStatusBadge');
                if (v.site_status === 'outside_site') {
                    badge.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200';
                    badge.innerHTML = '<i class="fa-solid fa-arrow-right-from-bracket mr-1 text-[9px]"></i> Di Luar Site';
                } else {
                    badge.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
                    badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span> Di Dalam Site';
                }

                document.getElementById('historyCountText').textContent = `Total ${data.count} riwayat pergerakan tercatat`;

                if (!data.histories || data.histories.length === 0) {
                    empty.classList.remove('hidden');
                    return;
                }

                list.classList.remove('hidden');
                
                // Render Timeline Items
                let html = '<div class="relative border-l-2 border-slate-200 ml-3.5 space-y-4 py-1">';
                data.histories.forEach((item, i) => {
                    const isOutside = item.site_status === 'outside_site';
                    const isGate = item.source === 'gate_crossing';

                    const dotBg = isOutside ? 'bg-amber-500 ring-amber-100' : 'bg-emerald-500 ring-emerald-100';
                    const badgeClass = isOutside 
                        ? 'bg-amber-50 text-amber-700 border-amber-200' 
                        : 'bg-emerald-50 text-emerald-700 border-emerald-200';

                    html += `
                        <div class="relative pl-6">
                            <!-- Bullet Marker -->
                            <div class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full ${dotBg} ring-4 flex items-center justify-center text-white text-[8px]">
                                <i class="fa-solid ${isOutside ? 'fa-arrow-right' : 'fa-arrow-left'}"></i>
                            </div>

                            <!-- Card Timeline Item -->
                            <div class="p-3.5 rounded-xl border border-slate-200/90 bg-white shadow-2xs hover:border-slate-300 transition">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold border ${badgeClass}">
                                            ${item.site_status_label}
                                        </span>
                                        <span class="text-xs font-bold text-slate-900">
                                            ${item.location}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 font-medium">
                                        <i class="fa-regular fa-clock mr-1 text-[10px]"></i>${item.recorded_at} (${item.time_ago})
                                    </div>
                                </div>

                                ${item.note && item.note !== '-' ? `
                                    <p class="text-xs text-slate-600 mt-2 bg-slate-50 p-2 rounded-lg border border-slate-100">
                                        ${item.note}
                                    </p>
                                ` : ''}

                                <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                                    <div class="flex items-center space-x-1.5">
                                        <i class="fa-solid ${isGate ? 'fa-shield-halved text-emerald-600' : 'fa-user-pen text-blue-600'} text-[10px]"></i>
                                        <span>Sumber: <strong>${item.source_label}</strong></span>
                                        ${item.trx_no ? `<span class="font-mono text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-600 font-bold">${item.trx_no}</span>` : ''}
                                    </div>
                                    <div>
                                        Petugas: <strong class="text-slate-700">${item.updater}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';

                list.innerHTML = html;
            })
            .catch(err => {
                loading.classList.add('hidden');
                empty.classList.remove('hidden');
                empty.innerHTML = `
                    <i class="fa-solid fa-triangle-exclamation text-3xl text-rose-500"></i>
                    <p class="text-xs font-semibold text-rose-700">Terjadi kesalahan saat memuat riwayat.</p>
                    <p class="text-[11px] text-slate-400">${err.message}</p>
                `;
            });
    }

    function closeHistoryModal() {
        const modal = document.getElementById('historyModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
</script>
@endsection
