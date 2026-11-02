@extends('layouts.app')

@section('title', 'Form Pemindahan Unit Alat Berat - Admin QMA | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full max-w-5xl mx-auto space-y-6 pb-12">

    <!-- Header Breadcrumb & Judul -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-bold text-slate-400 mb-1">
                <a href="{{ route('qma.unit_transfers.index') }}" class="text-[#1b5e20] hover:underline">
                    TRANSFER UNIT
                </a>
                <span>/</span>
                <span class="text-slate-600">FORM PEMINDAHAN</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Form Pemindahan Unit Alat Berat
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Pilih lokasi asal untuk memfilter unit yang tersedia, tentukan tujuan baru, armada truk pengangkut, dan terbitkan BAST.
            </p>
        </div>

        <a href="{{ route('qma.unit_transfers.index') }}" 
           class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-2xs">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            <span>Kembali ke Riwayat</span>
        </a>
    </div>

    <!-- Error Validation Alert -->
    @if($errors->any())
    <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs font-semibold space-y-1">
        <div class="flex items-center space-x-2 font-bold mb-1 text-rose-900">
            <i class="fa-solid fa-circle-exclamation text-rose-600 text-sm"></i>
            <span>Mohon periksa kembali isian form Anda:</span>
        </div>
        <ul class="list-disc pl-5 space-y-0.5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('qma.unit_transfers.store') }}" enctype="multipart/form-data" class="space-y-6" id="transferForm">
        @csrf

        <!-- ============================================================= -->
        <!-- BAGIAN 1: LOKASI AWAL & DATA ALAT BERAT -->
        <!-- ============================================================= -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 space-y-5">
            <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-[#1b5e20] flex items-center justify-center font-bold text-base shrink-0">
                    <i class="fa-solid fa-truck-monster"></i>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">1. Lokasi Awal & Unit Alat Berat yang Dipindahkan</h3>
                    <p class="text-xs text-slate-500">Pilih titik lokasi awal untuk melihat alat berat yang siap dipindahkan dari lokasi tersebut.</p>
                </div>
            </div>

            <!-- Grid 2 Kolom: Lokasi Awal & Pilih Alat Berat -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- PILIH LOKASI AWAL (ORIGIN LOCATION) -->
                <div>
                    <label for="origin_location" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                        <span>Lokasi Awal (Asal Unit) <span class="text-rose-500">*</span></span>
                        <span class="text-[10px] text-emerald-700 font-semibold">Bisa ketik / filter</span>
                    </label>
                    <div class="space-y-1.5">
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" 
                                   id="originLocationSearch" 
                                   placeholder="Ketik untuk memfilter pilihan lokasi asal..." 
                                   oninput="filterOriginLocationSelect(this.value)"
                                   autocomplete="off"
                                   class="w-full pl-9 pr-7 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition font-medium">
                            <button type="button" onclick="clearOriginLocationFilter()" id="clearOriginLocationFilterBtn" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>
                        <div class="relative">
                            <i class="fa-solid fa-map-pin absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <select name="from_location" 
                                    id="origin_location" 
                                    required
                                    onchange="onOriginLocationChanged(this.value)"
                                    class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800 font-bold">
                                <option value="" disabled {{ empty($selectedOriginLocation) && !old('from_location') ? 'selected' : '' }}>
                                    -- Pilih Lokasi Awal Alat Berat --
                                </option>
                                <option value="ALL" {{ old('from_location', $selectedOriginLocation) === 'ALL' ? 'selected' : '' }}>
                                    [Tampilkan Semua Lokasi / Semua Alat Berat]
                                </option>
                                @foreach($originLocations as $locName => $count)
                                    <option value="{{ $locName }}" {{ old('from_location', $selectedOriginLocation) === $locName ? 'selected' : '' }}>
                                        {{ $locName }} ({{ $count }} Unit Tersedia)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Ketik nama lokasi atau pilih dari daftar untuk memfilter unit.</p>
                </div>

                <!-- PILIH ALAT BERAT YANG AKAN DIPINDAHKAN -->
                <div>
                    <label for="vehicle_id" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                        <span>Unit Alat Berat yang Dipindahkan <span class="text-rose-500">*</span></span>
                        <span id="unitCounterBadge" class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 hidden"></span>
                    </label>
                    <div class="space-y-1.5">
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" 
                                   id="heavyUnitSearch" 
                                   placeholder="Ketik kode / merek untuk filter unit..." 
                                   oninput="filterHeavyUnitOptions(this.value)"
                                   autocomplete="off"
                                   class="w-full pl-9 pr-7 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition font-medium">
                            <button type="button" onclick="clearHeavyUnitFilter()" id="clearHeavyUnitFilterBtn" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>
                        <select name="vehicle_id" 
                                id="vehicle_id" 
                                required 
                                onchange="onHeavyUnitSelected(this)"
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800 font-semibold">
                            <option value="" disabled {{ empty($selectedVehicleId) && !old('vehicle_id') ? 'selected' : '' }}>
                                -- Pilih Lokasi Awal Terlebih Dahulu --
                            </option>
                            @foreach($heavyUnits as $unit)
                                <option value="{{ $unit->id }}" 
                                        data-code="{{ $unit->code }}"
                                        data-brand="{{ $unit->brand_type }}"
                                        data-category="{{ $unit->unit_category ?? ($unit->parent?->brand_type ?? 'Heavy Equipment') }}"
                                        data-location="{{ $unit->location ?: 'Site Kerinci' }}"
                                        data-site-status="{{ $unit->site_status ?? 'inside_site' }}"
                                        data-photo="{{ $unit->photo_url }}"
                                        {{ old('vehicle_id', $selectedVehicleId) == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->code }} - {{ $unit->brand_type }} [Posisi: {{ $unit->location ?: 'Site Kerinci' }}]
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('vehicle_id')
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Info Preview Card Unit Terpilih (Muncul Otomatis via JS) -->
            <div id="unitPreviewCard" class="hidden p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                <div class="flex items-center space-x-3.5">
                    <div id="unitPreviewPhotoContainer" class="w-14 h-14 rounded-xl bg-white border border-slate-200 flex items-center justify-center shrink-0 overflow-hidden">
                        <i class="fa-solid fa-truck-monster text-slate-400 text-xl" id="unitPreviewDefaultIcon"></i>
                        <img id="unitPreviewPhoto" src="" alt="Foto Unit" class="hidden w-full h-full object-cover">
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2">
                            <span id="unitPreviewCode" class="font-extrabold text-sm text-slate-900 font-mono"></span>
                            <span id="unitPreviewCategory" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700"></span>
                        </div>
                        <p id="unitPreviewBrand" class="text-xs text-slate-600 font-medium mt-0.5 truncate"></p>
                        <div class="mt-1 flex items-center space-x-2 text-[11px]">
                            <span class="text-slate-400">Titik Keberangkatan (Asal):</span>
                            <span id="unitPreviewCurrentLocation" class="font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid 2 Kolom: Tanggal Transfer & Lokasi Tujuan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Tanggal Pemindahan -->
                <div>
                    <label for="transfer_date" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Tanggal Pemindahan <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" 
                           name="transfer_date" 
                           id="transfer_date" 
                           value="{{ old('transfer_date', \Carbon\Carbon::now()->format('Y-m-d')) }}" 
                           required 
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800">
                    @error('transfer_date')
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Lokasi Tujuan -->
                <div>
                    <label for="to_location" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                        <span>Lokasi Tujuan Baru <span class="text-rose-500">*</span></span>
                        <span class="text-[10px] text-slate-400 font-normal">Pilih / Ketik Lokasi</span>
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-location-dot absolute left-3.5 top-1/2 -translate-y-1/2 text-emerald-700 text-xs"></i>
                        <input type="text" 
                               name="to_location" 
                               id="to_location" 
                               list="locationSuggestionList" 
                               value="{{ old('to_location') }}" 
                               required 
                               oninput="updateRouteBanner()"
                               placeholder="Contoh: Main Dam, Adit 2, Workshop GA..." 
                               class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800 font-semibold">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Pilih dari rekomendasi lokasi atau ketik lokasi baru secara bebas.</p>
                    @error('to_location')
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror

                    <!-- Datalist Rekomendasi Lokasi -->
                    <datalist id="locationSuggestionList">
                        @foreach($locationSuggestions as $loc)
                            <option value="{{ $loc }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>

            <!-- Grid 2 Kolom: Status Area Tujuan & Reported By -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Area Tujuan (Inside/Outside Site) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Area Tujuan Pemindahan <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2.5">
                        <label class="flex items-center space-x-2.5 p-2.5 rounded-xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/30 transition cursor-pointer has-checked:border-emerald-600 has-checked:bg-emerald-50/60 has-checked:text-emerald-900">
                            <input type="radio" 
                                   name="to_site_status" 
                                   value="inside_site" 
                                   id="radio_inside_site"
                                   onchange="onSiteStatusChanged(this.value)"
                                   {{ old('to_site_status', 'inside_site') === 'inside_site' ? 'checked' : '' }}
                                   class="text-[#1b5e20] focus:ring-[#1b5e20]">
                            <div class="text-left">
                                <span class="block text-xs font-bold">Di Dalam Site</span>
                                <span class="text-[10px] text-slate-500 block">Area Proyek KMH</span>
                            </div>
                        </label>

                        <label class="flex items-center space-x-2.5 p-2.5 rounded-xl border border-slate-200 hover:border-amber-300 hover:bg-amber-50/30 transition cursor-pointer has-checked:border-amber-600 has-checked:bg-amber-50/60 has-checked:text-amber-900">
                            <input type="radio" 
                                   name="to_site_status" 
                                   value="outside_site" 
                                   id="radio_outside_site"
                                   onchange="onSiteStatusChanged(this.value)"
                                   {{ old('to_site_status') === 'outside_site' ? 'checked' : '' }}
                                   class="text-amber-600 focus:ring-amber-500">
                            <div class="text-left">
                                <span class="block text-xs font-bold">Di Luar Site</span>
                                <span class="text-[10px] text-slate-500 block">Workshop/Luar</span>
                            </div>
                        </label>
                    </div>
                    @error('to_site_status')
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Dilaporkan Oleh (Reported By) -->
                <div>
                    <label for="reported_by" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Dilaporkan Oleh (QMA) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-user-check absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" 
                               name="reported_by" 
                               id="reported_by" 
                               value="{{ old('reported_by', auth()->user()->name) }}" 
                               required 
                               placeholder="Nama Petugas QMA" 
                               class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800 font-semibold">
                    </div>
                    @error('reported_by')
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Deskripsi / Keperluan Pemindahan -->
            <div>
                <label for="description" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Deskripsi / Keperluan Pemindahan (Opsional)
                </label>
                <textarea name="description" 
                          id="description" 
                          rows="2" 
                          placeholder="Contoh: Relokasi excavator untuk percepatan galian bendungan intake Adit 2." 
                          class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800">{{ old('description') }}</textarea>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- BAGIAN 2: ARMADA & SUPIR PENGANGKUT (TRANSPORTER) -->
        <!-- ============================================================= -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 space-y-5">
            <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-base shrink-0">
                    <i class="fa-solid fa-truck-moving"></i>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">2. Armada & Supir Pengangkut (Transporter)</h3>
                    <p class="text-xs text-slate-500">Pilih kendaraan pengangkut dari armada internal proyek KMH atau input armada dari vendor/rekanan luar.</p>
                </div>
            </div>

            <!-- Pilihan Jenis Pengangkut: Dari Dalam KMH (Internal) vs Dari Luar KMH (Eksternal) -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">
                    Asal Armada Pengangkut <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-xl">
                    <!-- Opsi 1: Dari Dalam KMH -->
                    <label id="card_transporter_internal" class="flex items-center space-x-3 p-3 rounded-xl border-2 border-blue-600 bg-blue-50/50 text-blue-950 transition cursor-pointer shadow-xs">
                        <input type="radio" 
                               name="transporter_type" 
                               value="internal" 
                               id="radio_transporter_internal" 
                               {{ old('transporter_type', 'internal') === 'internal' ? 'checked' : '' }}
                               onchange="onTransporterTypeChanged(this.value)"
                               class="text-blue-600 focus:ring-blue-500">
                        <div class="text-left">
                            <span class="block text-xs font-bold flex items-center space-x-1.5">
                                <i class="fa-solid fa-building-shield text-blue-600"></i>
                                <span>Dari Dalam KMH (Armada Internal)</span>
                            </span>
                            <span class="text-[10px] text-slate-500 block mt-0.5">Pilih lokasi asal armada & unit pengangkut proyek</span>
                        </div>
                    </label>

                    <!-- Opsi 2: Dari Luar KMH -->
                    <label id="card_transporter_external" class="flex items-center space-x-3 p-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-slate-700 transition cursor-pointer">
                        <input type="radio" 
                               name="transporter_type" 
                               value="external" 
                               id="radio_transporter_external" 
                               {{ old('transporter_type') === 'external' ? 'checked' : '' }}
                               onchange="onTransporterTypeChanged(this.value)"
                               class="text-blue-600 focus:ring-blue-500">
                        <div class="text-left">
                            <span class="block text-xs font-bold flex items-center space-x-1.5">
                                <i class="fa-solid fa-truck-ramp-box text-slate-500"></i>
                                <span>Dari Luar KMH (Rental / Vendor)</span>
                            </span>
                            <span class="text-[10px] text-slate-500 block mt-0.5">Input manual lokasi asal & nama unit rekanan luar</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Banner Info Logistik Rute (Muncul saat Truk dipilih / terisi) -->
            <div id="routeLogisticsBanner" class="hidden p-3.5 rounded-xl bg-blue-50/80 border border-blue-200 text-xs text-blue-900 flex items-start space-x-3">
                <i class="fa-solid fa-route text-blue-700 text-sm mt-0.5"></i>
                <div class="flex-1">
                    <span class="font-bold block mb-0.5">Rute Logistik Pengangkutan:</span>
                    <p id="routeLogisticsText" class="text-[11px] text-blue-800 leading-relaxed"></p>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- KONTAINER 1: PENGANGKUT DARI DALAM KMH -->
            <!-- ============================================== -->
            <div id="transporterInternalContainer" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- KIRI: PILIH LOKASI ASAL PENGANGKUT (INTERNAL) -->
                    <div>
                        <label for="transporter_origin_location_select" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                            <span>Lokasi Asal Kendaraan Pengangkut</span>
                            <span class="text-[10px] text-blue-700 font-semibold">Filter Berdasarkan Posisi</span>
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-map-pin absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <select id="transporter_origin_location_select" 
                                    onchange="onTransporterOriginChanged(this.value)"
                                    class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white text-slate-800 font-bold">
                                <option value="" selected>-- Pilih Lokasi Asal Kendaraan Pengangkut --</option>
                                <option value="ALL">[Tampilkan Semua Lokasi / Semua Armada]</option>
                                @foreach($transporterLocations as $tLoc => $tCount)
                                    <option value="{{ $tLoc }}" {{ old('transporter_origin_location') === $tLoc ? 'selected' : '' }}>
                                        {{ $tLoc }} ({{ $tCount }} Unit Tersedia)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Pilih lokasi asal untuk memfilter unit pengangkut yang standby di titik tersebut.</p>
                    </div>

                    <!-- KANAN: PILIH KENDARAAN PENGANGKUT (INPUT KETIKAN TERFILTER) -->
                    <div>
                        <label for="internal_transporter_search" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                            <span>Kendaraan Pengangkut (Armada Kita)</span>
                            <span id="transporterCounterBadge" class="text-[10px] font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200 hidden"></span>
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-truck-moving absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" 
                                   id="internal_transporter_search" 
                                   list="internalTransporterSuggestionList" 
                                   placeholder="Ketik kode/tipe truk (cth: LB 01, DT 05, Lowbed...)" 
                                   oninput="onInternalTransporterInput(this.value)"
                                   autocomplete="off"
                                   class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white text-slate-800 font-semibold">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Ketik atau pilih dari rekomendasi truk pengangkut internal (Lowbed, Flatbed, Dump Truck, Trailer, dll.).</p>

                        <!-- Datalist Autocomplete Khusus Truk Pengangkut Internal -->
                        <datalist id="internalTransporterSuggestionList">
                            @foreach($transporterUnits as $tu)
                                <option value="{{ $tu->code }} - {{ $tu->brand_type }}{{ $tu->license_plate ? ' (' . $tu->license_plate . ')' : '' }} [Posisi: {{ $tu->location ?: 'Site Kerinci' }}]" data-id="{{ $tu->id }}">
                            @endforeach
                        </datalist>

                        <!-- Hidden ID untuk backend -->
                        <input type="hidden" name="transporter_vehicle_id" id="transporter_vehicle_id" value="{{ old('transporter_vehicle_id') }}">
                    </div>
                </div>

                <!-- Opsi Sinkronisasi Lokasi Truk Pengangkut Internal -->
                <div id="transporterUpdateCheckboxWrapper" class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center space-x-3">
                    <input type="checkbox" 
                           name="update_transporter_location" 
                           id="update_transporter_location" 
                           value="1" 
                           checked 
                           class="w-4 h-4 rounded text-[#1b5e20] focus:ring-[#1b5e20] border-slate-300 cursor-pointer">
                    <label for="update_transporter_location" class="text-xs text-slate-700 font-semibold cursor-pointer select-none">
                        Perbarui juga posisi terkini truk pengangkut ke lokasi tujuan setelah proses pemindahan selesai
                    </label>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- KONTAINER 2: PENGANGKUT DARI LUAR KMH -->
            <!-- ============================================== -->
            <div id="transporterExternalContainer" class="hidden space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- KIRI: ASAL KENDARAAN PENGANGKUT (ISIAN TEKS MANUAL) -->
                    <div>
                        <label for="transporter_origin_location_input" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                            <span>Asal Kendaraan Pengangkut (Luar)</span>
                            <span class="text-[10px] text-slate-400 font-normal">Ketik Bebas / Rekomendasi</span>
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-map-location-dot absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" 
                                   id="transporter_origin_location_input" 
                                   list="locationSuggestionList" 
                                   placeholder="Contoh: Pool Jambi, Workshop Padang, PT Bhara Seva..." 
                                   oninput="onExternalOriginInput(this.value)"
                                   value="{{ old('transporter_type') === 'external' ? old('transporter_origin_location') : '' }}"
                                   class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white text-slate-800 font-semibold">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Ketik kota asal, pool rekanan, atau nama subkon penyedia armada.</p>
                    </div>

                    <!-- KANAN: IDENTITAS / NAMA KENDARAAN PENGANGKUT (ISIAN TEKS MANUAL) -->
                    <div>
                        <label for="transporter_unit_name_input" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                            <span>Identitas / Nama Kendaraan Pengangkut</span>
                            <span class="text-[10px] text-slate-400 font-normal">Isian Teks Bebas</span>
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-truck absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" 
                                   id="transporter_unit_name_input" 
                                   placeholder="Contoh: Truk Tronton Hino BA 1234 XY / Lowbed Rental PT Surya" 
                                   oninput="onExternalUnitNameInput(this.value)"
                                   value="{{ old('transporter_type') === 'external' ? old('transporter_unit_name') : '' }}"
                                   class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white text-slate-800 font-semibold">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Ketik jenis kendaraan, nomor plat polisi, atau identitas unit rekanan luar.</p>
                    </div>
                </div>
            </div>

            <!-- Field Hidden Final yang Dikirim ke Server -->
            <input type="hidden" name="transporter_origin_location" id="final_transporter_origin_location" value="{{ old('transporter_origin_location') }}">
            <input type="hidden" name="transporter_unit_name" id="final_transporter_unit_name" value="{{ old('transporter_unit_name') }}">

            <!-- Grid 2 Kolom: Supir Pengangkut & Catatan Angkutan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                <!-- Supir Pengangkut -->
                <div>
                    <label for="transporter_driver_name" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                        <span>Nama Driver / Supir Pengangkut</span>
                        <span class="text-[10px] text-slate-400 font-normal">Pilih / Ketik</span>
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-id-card absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" 
                               name="transporter_driver_name" 
                               id="transporter_driver_name" 
                               list="driverSuggestionList" 
                               value="{{ old('transporter_driver_name') }}" 
                               placeholder="Nama Pengemudi / Driver" 
                               class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800 font-semibold">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Pilih dari daftar operator terdaftar atau ketik nama driver luar.</p>

                    <datalist id="driverSuggestionList">
                        @foreach($drivers as $drv)
                            <option value="{{ $drv->name }}">NIK: {{ $drv->nik }} ({{ $drv->position ?: 'Operator' }})</option>
                        @endforeach
                    </datalist>
                </div>

                <!-- Deskripsi / Catatan Angkutan -->
                <div>
                    <label for="transporter_description" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Catatan Pengangkutan & Pengamanan Muatan (Opsional)
                    </label>
                    <textarea name="transporter_description" 
                              id="transporter_description" 
                              rows="2" 
                              placeholder="Contoh: Unit telah diikat rantai lashing 4 titik, bucket terkunci, dikawal mobil patroli." 
                              class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800">{{ old('transporter_description') }}</textarea>
                </div>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- BAGIAN 3: BERITA ACARA SERAH TERIMA (BAST) -->
        <!-- Hanya muncul untuk Pemindahan ke Luar Site (outside_site) -->
        <!-- ============================================================= -->
        <div id="bastSectionCard" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 space-y-5 transition-all">
            <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-base shrink-0">
                    <i class="fa-solid fa-file-contract"></i>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">3. Berita Acara Serah Terima (BAST)</h3>
                    <p class="text-xs text-slate-500">Nomor surat, perihal, dan berkas lampiran berita acara resmi pemindahan alat berat ke luar site.</p>
                </div>
            </div>

            <!-- Grid 2 Kolom: Nomor Surat & Judul Surat -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Nomor Surat Berita Acara -->
                <div>
                    <label for="document_number" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Nomor Surat / Berita Acara <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-hashtag absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" 
                               name="document_number" 
                               id="document_number" 
                               value="{{ old('document_number', $defaultDocNumber) }}" 
                               placeholder="Contoh: 014/BAST-AB/QMA-KMH/X/2026" 
                               class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800 font-mono font-bold">
                    </div>
                    @error('document_number')
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Judul Surat / Perihal -->
                <div>
                    <label for="document_title" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Judul Surat / Perihal <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-heading absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" 
                               name="document_title" 
                               id="document_title" 
                               value="{{ old('document_title', 'Berita Acara Pemindahan & Relokasi Unit Alat Berat') }}" 
                               placeholder="Contoh: Berita Acara Pemindahan Excavator ke Workshop Luar" 
                               class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800 font-semibold">
                    </div>
                    @error('document_title')
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Upload Berkas Lampiran Scan Surat / Dokumen -->
            <div>
                <label for="document_file" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                    <span>Unggah Berkas / Scan Surat Berita Acara (Opsional)</span>
                    <span class="text-[10px] text-slate-400">PDF, JPG, PNG (Maks 5MB)</span>
                </label>
                <input type="file" 
                       name="document_file" 
                       id="document_file" 
                       accept=".pdf,.jpg,.jpeg,.png"
                       class="block w-full text-xs text-slate-500 file:mr-3.5 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer border border-slate-200 rounded-xl bg-white p-1">
                @error('document_file')
                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Tombol Aksi Simpan -->
        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('qma.unit_transfers.index') }}" 
               class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                Batal
            </a>
            <button type="submit" 
                    id="submitBtn"
                    class="px-6 py-2.5 text-xs font-bold text-white bg-[#1b5e20] hover:bg-[#144919] rounded-xl transition shadow-sm cursor-pointer inline-flex items-center space-x-2">
                <i class="fa-solid fa-check"></i>
                <span id="submitBtnText">Simpan Pemindahan Unit</span>
            </button>
        </div>
    </form>
</div>

<script>
    // Inisialisasi daftar semua unit alat berat & armada pengangkut
    let allHeavyUnits = [];
    let allTransporterUnits = [
        @foreach($transporterUnits as $tu)
        {
            id: {{ $tu->id }},
            code: @json($tu->code),
            name: @json($tu->code . ' - ' . $tu->brand_type . ($tu->license_plate ? ' (' . $tu->license_plate . ')' : '')),
            location: @json($tu->location ?: 'Site Kerinci'),
            category: @json($tu->unit_category ?? 'Truk'),
            text: @json($tu->code . ' - ' . $tu->brand_type . ($tu->license_plate ? ' (' . $tu->license_plate . ')' : '') . ' [Posisi: ' . ($tu->location ?: 'Site Kerinci') . ']')
        },
        @endforeach
    ];

    document.addEventListener('DOMContentLoaded', function() {
        const unitSelect = document.getElementById('vehicle_id');
        if (unitSelect) {
            Array.from(unitSelect.options).forEach(opt => {
                if (opt.value) {
                    allHeavyUnits.push({
                        id: opt.value,
                        code: opt.getAttribute('data-code'),
                        brand: opt.getAttribute('data-brand'),
                        category: opt.getAttribute('data-category'),
                        location: opt.getAttribute('data-location'),
                        siteStatus: opt.getAttribute('data-site-status'),
                        photo: opt.getAttribute('data-photo'),
                        text: opt.textContent.trim()
                    });
                }
            });
        }

        // Cek jika sudah ada lokasi awal alat berat (dari old value atau selectedOriginLocation)
        const originSelect = document.getElementById('origin_location');
        if (originSelect && originSelect.value) {
            onOriginLocationChanged(originSelect.value, "{{ old('vehicle_id', $selectedVehicleId ?? '') }}");
        }

        // Inisialisasi tampilan jenis pengangkut (internal vs external)
        const currentTransporterType = document.querySelector('input[name="transporter_type"]:checked')?.value || 'internal';
        onTransporterTypeChanged(currentTransporterType);

        // Inisialisasi datalist saran truk internal
        const oldTransporterOrigin = "{{ old('transporter_origin_location') }}";
        const oldTransporterId = "{{ old('transporter_vehicle_id') }}";
        if (currentTransporterType === 'internal') {
            if (oldTransporterOrigin) {
                const tLocSelect = document.getElementById('transporter_origin_location_select');
                if (tLocSelect) tLocSelect.value = oldTransporterOrigin;
                onTransporterOriginChanged(oldTransporterOrigin, oldTransporterId);
            } else if (oldTransporterId) {
                const matched = allTransporterUnits.find(u => String(u.id) === String(oldTransporterId));
                if (matched) {
                    const tLocSelect = document.getElementById('transporter_origin_location_select');
                    if (tLocSelect) tLocSelect.value = matched.location;
                    onTransporterOriginChanged(matched.location, oldTransporterId);
                } else {
                    onTransporterOriginChanged('');
                }
            } else {
                onTransporterOriginChanged('');
            }
        }

        // Inisialisasi tampilan BAST berdasarkan status site awal
        const currentSiteStatus = document.querySelector('input[name="to_site_status"]:checked')?.value || 'inside_site';
        onSiteStatusChanged(currentSiteStatus);
    });

    /**
     * Handler untuk Jenis Pengangkut (Dari Dalam KMH vs Dari Luar KMH)
     */
    function onTransporterTypeChanged(type) {
        const cardInternal = document.getElementById('card_transporter_internal');
        const cardExternal = document.getElementById('card_transporter_external');
        const containerInternal = document.getElementById('transporterInternalContainer');
        const containerExternal = document.getElementById('transporterExternalContainer');
        const updateCheckboxWrapper = document.getElementById('transporterUpdateCheckboxWrapper');
        const updateCheckbox = document.getElementById('update_transporter_location');

        if (type === 'external') {
            // Style Card External Aktif
            cardExternal.className = 'flex items-center space-x-3 p-3 rounded-xl border-2 border-blue-600 bg-blue-50/50 text-blue-950 transition cursor-pointer shadow-xs';
            cardInternal.className = 'flex items-center space-x-3 p-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-slate-700 transition cursor-pointer';

            containerInternal.classList.add('hidden');
            containerExternal.classList.remove('hidden');
            if (updateCheckboxWrapper) updateCheckboxWrapper.classList.add('hidden');
            if (updateCheckbox) updateCheckbox.checked = false;

            // Kosongkan seleksi unit internal
            const vId = document.getElementById('transporter_vehicle_id');
            if (vId) vId.value = '';

            // Sinkronkan data dari input teks external
            const extOrigin = document.getElementById('transporter_origin_location_input')?.value.trim() || '';
            const extName = document.getElementById('transporter_unit_name_input')?.value.trim() || '';
            document.getElementById('final_transporter_origin_location').value = extOrigin;
            document.getElementById('final_transporter_unit_name').value = extName;
        } else {
            // Style Card Internal Aktif
            cardInternal.className = 'flex items-center space-x-3 p-3 rounded-xl border-2 border-blue-600 bg-blue-50/50 text-blue-950 transition cursor-pointer shadow-xs';
            cardExternal.className = 'flex items-center space-x-3 p-3 rounded-xl border border-slate-200 hover:border-slate-300 bg-white text-slate-700 transition cursor-pointer';

            containerInternal.classList.remove('hidden');
            containerExternal.classList.add('hidden');
            if (updateCheckboxWrapper) updateCheckboxWrapper.classList.remove('hidden');
            if (updateCheckbox) updateCheckbox.checked = true;

            // Sinkronkan data dari input pencarian internal
            const searchInput = document.getElementById('internal_transporter_search');
            if (searchInput) onInternalTransporterInput(searchInput.value);
        }

        updateRouteBanner();
    }

    /**
     * Handler ketika Lokasi Asal Armada Pengangkut Internal Berubah
     */
    function onTransporterOriginChanged(selectedLocation, preselectId = null) {
        const datalist = document.getElementById('internalTransporterSuggestionList');
        const badge = document.getElementById('transporterCounterBadge');
        const searchInput = document.getElementById('internal_transporter_search');
        if (!datalist) return;

        datalist.innerHTML = '';

        const filtered = (!selectedLocation || selectedLocation === 'ALL')
            ? allTransporterUnits
            : allTransporterUnits.filter(u => u.location === selectedLocation);

        if (badge) {
            badge.classList.remove('hidden');
            badge.textContent = `${filtered.length} Truk Tersedia`;
        }

        if (searchInput) {
            if (selectedLocation && selectedLocation !== 'ALL') {
                searchInput.placeholder = `Ketik kode truk di ${selectedLocation} (${filtered.length} truk)...`;
            } else {
                searchInput.placeholder = `Ketik kode/tipe truk (${filtered.length} truk tersedia)...`;
            }
        }

        filtered.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.text;
            opt.setAttribute('data-id', u.id);
            datalist.appendChild(opt);
        });

        if (preselectId) {
            const matched = allTransporterUnits.find(u => String(u.id) === String(preselectId));
            if (matched && searchInput) {
                searchInput.value = matched.text;
                onInternalTransporterInput(matched.text);
            }
        }
    }

    /**
     * Handler ketika input ketikan truk internal berubah (Autocomplete / Pencarian)
     */
    function onInternalTransporterInput(val) {
        val = (val || '').trim();
        const finalOrigin = document.getElementById('final_transporter_origin_location');
        const finalName = document.getElementById('final_transporter_unit_name');
        const vehicleIdInput = document.getElementById('transporter_vehicle_id');

        if (!val) {
            if (vehicleIdInput) vehicleIdInput.value = '';
            if (finalName) finalName.value = '';
            if (finalOrigin) finalOrigin.value = '';
            updateRouteBanner();
            return;
        }

        // Cari unit yang cocok di allTransporterUnits berdasarkan kode atau nama lengkap
        const valUpper = val.toUpperCase();
        const matched = allTransporterUnits.find(u => 
            valUpper === u.code.toUpperCase() ||
            valUpper === u.text.toUpperCase() ||
            valUpper === u.name.toUpperCase() ||
            valUpper.startsWith(u.code.toUpperCase() + ' ') ||
            valUpper.startsWith(u.code.toUpperCase() + '-')
        );

        if (matched) {
            if (vehicleIdInput) vehicleIdInput.value = matched.id;
            if (finalName) finalName.value = matched.name;
            if (finalOrigin) finalOrigin.value = matched.location;
        } else {
            // Teks ketikan bebas
            if (vehicleIdInput) vehicleIdInput.value = '';
            if (finalName) finalName.value = val;
        }

        updateRouteBanner();
    }

    /**
     * Handler input Asal Kendaraan Luar
     */
    function onExternalOriginInput(val) {
        const finalOrigin = document.getElementById('final_transporter_origin_location');
        if (finalOrigin) finalOrigin.value = (val || '').trim();
        updateRouteBanner();
    }

    /**
     * Handler input Nama / Identitas Kendaraan Luar
     */
    function onExternalUnitNameInput(val) {
        const finalName = document.getElementById('final_transporter_unit_name');
        if (finalName) finalName.value = (val || '').trim();
        updateRouteBanner();
    }

    /**
     * Handler untuk Area Tujuan (Di Dalam Site vs Di Luar Site)
     * Nomor BAST dan Dokumen hanya muncul saat transfer ke LUAR site.
     */
    function onSiteStatusChanged(status) {
        const bastSection = document.getElementById('bastSectionCard');
        const docNumberInput = document.getElementById('document_number');
        const docTitleInput = document.getElementById('document_title');
        const docFileInput = document.getElementById('document_file');
        const submitBtnText = document.getElementById('submitBtnText');

        if (status === 'outside_site') {
            if (bastSection) {
                bastSection.classList.remove('hidden');
            }
            if (docNumberInput) {
                docNumberInput.removeAttribute('disabled');
                docNumberInput.setAttribute('required', 'required');
            }
            if (docTitleInput) {
                docTitleInput.removeAttribute('disabled');
                docTitleInput.setAttribute('required', 'required');
            }
            if (docFileInput) {
                docFileInput.removeAttribute('disabled');
            }
            if (submitBtnText) {
                submitBtnText.textContent = 'Simpan & Terbitkan BAST';
            }
        } else {
            if (bastSection) {
                bastSection.classList.add('hidden');
            }
            if (docNumberInput) {
                docNumberInput.removeAttribute('required');
                docNumberInput.setAttribute('disabled', 'disabled');
            }
            if (docTitleInput) {
                docTitleInput.removeAttribute('required');
                docTitleInput.setAttribute('disabled', 'disabled');
            }
            if (docFileInput) {
                docFileInput.setAttribute('disabled', 'disabled');
            }
            if (submitBtnText) {
                submitBtnText.textContent = 'Simpan Pemindahan Unit';
            }
        }
    }

    /**
     * Handler ketika Lokasi Awal (Asal) berubah
     * Memfilter unit alat berat yang posisinya berada di lokasi tersebut
     */
    function onOriginLocationChanged(selectedLocation, preselectUnitId = null) {
        const unitSelect = document.getElementById('vehicle_id');
        const badge = document.getElementById('unitCounterBadge');
        if (!unitSelect) return;

        // Kosongkan options
        unitSelect.innerHTML = '';

        const searchInput = document.getElementById('heavyUnitSearch');
        if (searchInput) searchInput.value = '';
        const searchClear = document.getElementById('clearHeavyUnitFilterBtn');
        if (searchClear) searchClear.classList.add('hidden');

        if (!selectedLocation) {
            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.disabled = true;
            defaultOpt.selected = true;
            defaultOpt.textContent = '-- Pilih Lokasi Awal Terlebih Dahulu --';
            unitSelect.appendChild(defaultOpt);
            badge.classList.add('hidden');
            document.getElementById('unitPreviewCard').classList.add('hidden');
            return;
        }

        // Filter unit
        const filteredUnits = (selectedLocation === 'ALL')
            ? allHeavyUnits
            : allHeavyUnits.filter(u => u.location === selectedLocation);

        // Update badge jumlah unit
        badge.classList.remove('hidden');
        badge.textContent = `${filteredUnits.length} Unit Tersedia`;

        // Tambah opsi default
        const promptOpt = document.createElement('option');
        promptOpt.value = '';
        promptOpt.disabled = true;
        promptOpt.textContent = filteredUnits.length > 0 ? '-- Pilih Unit Alat Berat --' : '-- Tidak ada unit di lokasi ini --';
        if (!preselectUnitId) {
            promptOpt.selected = true;
        }
        unitSelect.appendChild(promptOpt);

        // Tambah options hasil filter
        let hasPreselected = false;
        filteredUnits.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.setAttribute('data-code', u.code);
            opt.setAttribute('data-brand', u.brand);
            opt.setAttribute('data-category', u.category);
            opt.setAttribute('data-location', u.location);
            opt.setAttribute('data-site-status', u.siteStatus);
            opt.setAttribute('data-photo', u.photo || '');
            opt.textContent = `${u.code} - ${u.brand} [Posisi: ${u.location}]`;

            if (preselectUnitId && String(u.id) === String(preselectUnitId)) {
                opt.selected = true;
                hasPreselected = true;
            }
            unitSelect.appendChild(opt);
        });

        if (hasPreselected) {
            onHeavyUnitSelected(unitSelect);
        } else {
            document.getElementById('unitPreviewCard').classList.add('hidden');
        }

        updateRouteBanner();
    }

    /**
     * Handler saat Alat Berat Terpilih
     */
    function onHeavyUnitSelected(selectElement) {
        const option = selectElement.options[selectElement.selectedIndex];
        if (!option || !option.value) return;

        const code = option.getAttribute('data-code');
        const brand = option.getAttribute('data-brand');
        const category = option.getAttribute('data-category');
        const location = option.getAttribute('data-location');
        const photo = option.getAttribute('data-photo');

        // Buka preview card
        const card = document.getElementById('unitPreviewCard');
        card.classList.remove('hidden');
        document.getElementById('unitPreviewCode').textContent = code;
        document.getElementById('unitPreviewBrand').textContent = brand;
        document.getElementById('unitPreviewCategory').textContent = category;
        document.getElementById('unitPreviewCurrentLocation').textContent = location;

        const photoImg = document.getElementById('unitPreviewPhoto');
        const defaultIcon = document.getElementById('unitPreviewDefaultIcon');
        if (photo) {
            photoImg.src = photo;
            photoImg.classList.remove('hidden');
            defaultIcon.classList.add('hidden');
        } else {
            photoImg.classList.add('hidden');
            defaultIcon.classList.remove('hidden');
        }

        // Rekomendasi perihal surat otomatis jika masih default
        const docTitle = document.getElementById('document_title');
        if (docTitle && (!docTitle.value || docTitle.value.includes('Pemindahan') || docTitle.value.includes('Relokasi'))) {
            docTitle.value = `Berita Acara Pemindahan Unit ${code} (${brand})`;
        }

        updateRouteBanner();
    }

    /**
     * Update Banner Logistik Rute Perjalanan (Mendukung Internal & Eksternal)
     */
    function updateRouteBanner() {
        const banner = document.getElementById('routeLogisticsBanner');
        const text = document.getElementById('routeLogisticsText');
        const originSelect = document.getElementById('origin_location');
        const destinationInput = document.getElementById('to_location');
        const transporterType = document.querySelector('input[name="transporter_type"]:checked')?.value || 'internal';

        if (!banner || !text) return;

        const originLoc = originSelect && originSelect.value && originSelect.value !== 'ALL' ? originSelect.value : 'Lokasi Asal Alat Berat';
        const destLoc = destinationInput && destinationInput.value ? destinationInput.value : '[Lokasi Tujuan Baru]';

        if (transporterType === 'internal') {
            const truckName = document.getElementById('final_transporter_unit_name')?.value || document.getElementById('internal_transporter_search')?.value.trim();
            const truckLoc = document.getElementById('final_transporter_origin_location')?.value || 'Site Kerinci';

            if (!truckName) {
                banner.classList.add('hidden');
                return;
            }

            banner.classList.remove('hidden');
            if (truckLoc === originLoc) {
                text.innerHTML = `Truk <strong>${truckName}</strong> saat ini standby di <strong>${truckLoc}</strong> (sama dengan lokasi alat berat) &rarr; Langsung mengangkut unit menuju tujuan <strong>${destLoc}</strong>.`;
            } else {
                text.innerHTML = `Truk <strong>${truckName}</strong> diberangkatkan dari <strong>${truckLoc}</strong> &rarr; Menjemput alat berat di <strong>${originLoc}</strong> &rarr; Mengantarkannya ke tujuan <strong>${destLoc}</strong>.`;
            }
        } else {
            const extOrigin = document.getElementById('transporter_origin_location_input')?.value.trim();
            const extName = document.getElementById('transporter_unit_name_input')?.value.trim();

            if (!extName && !extOrigin) {
                banner.classList.add('hidden');
                return;
            }

            const displayOrigin = extOrigin ? extOrigin : 'Luar Proyek';
            const displayName = extName ? extName : 'Armada Eksternal';

            banner.classList.remove('hidden');
            text.innerHTML = `Armada luar (<strong>${displayName}</strong>) diberangkatkan dari <strong>${displayOrigin}</strong> &rarr; Menjemput alat berat di <strong>${originLoc}</strong> &rarr; Mengantarkannya ke tujuan <strong>${destLoc}</strong>.`;
        }
    }

    /**
     * Filter Realtime Dropdown Lokasi Awal berdasarkan Ketikan
     */
    function filterOriginLocationSelect(query) {
        const select = document.getElementById('origin_location');
        const clearBtn = document.getElementById('clearOriginLocationFilterBtn');
        if (!select) return;

        const q = (query || '').trim().toLowerCase();
        if (clearBtn) {
            clearBtn.classList.toggle('hidden', !q);
        }

        let visibleCount = 0;
        let firstMatch = null;

        Array.from(select.options).forEach(opt => {
            if (!opt.value) {
                opt.hidden = false;
                return;
            }
            if (opt.value === 'ALL') {
                opt.hidden = q.length > 0 && !opt.textContent.toLowerCase().includes(q);
                if (!opt.hidden && !firstMatch) firstMatch = opt;
                return;
            }
            const match = opt.textContent.toLowerCase().includes(q) || opt.value.toLowerCase().includes(q);
            opt.hidden = !match;
            if (match) {
                visibleCount++;
                if (!firstMatch) firstMatch = opt;
            }
        });

        if (q && visibleCount === 1 && firstMatch) {
            select.value = firstMatch.value;
            onOriginLocationChanged(firstMatch.value);
        }
    }

    function clearOriginLocationFilter() {
        const input = document.getElementById('originLocationSearch');
        if (input) input.value = '';
        filterOriginLocationSelect('');
    }

    /**
     * Filter Realtime Dropdown Unit Alat Berat berdasarkan Ketikan Kode / Merek
     */
    function filterHeavyUnitOptions(query) {
        const select = document.getElementById('vehicle_id');
        const clearBtn = document.getElementById('clearHeavyUnitFilterBtn');
        const badge = document.getElementById('unitCounterBadge');
        if (!select) return;

        const q = (query || '').trim().toLowerCase();
        if (clearBtn) {
            clearBtn.classList.toggle('hidden', !q);
        }

        let visibleCount = 0;
        let firstMatch = null;

        Array.from(select.options).forEach(opt => {
            if (!opt.value) {
                opt.hidden = false;
                return;
            }
            const code = (opt.getAttribute('data-code') || '').toLowerCase();
            const brand = (opt.getAttribute('data-brand') || '').toLowerCase();
            const cat = (opt.getAttribute('data-category') || '').toLowerCase();
            const text = opt.textContent.toLowerCase();

            const match = text.includes(q) || code.includes(q) || brand.includes(q) || cat.includes(q);
            opt.hidden = !match;
            if (match) {
                visibleCount++;
                if (!firstMatch) firstMatch = opt;
            }
        });

        if (badge) {
            badge.classList.remove('hidden');
            badge.textContent = q ? `${visibleCount} Unit Cocok` : `${visibleCount} Unit Tersedia`;
        }

        if (q && visibleCount === 1 && firstMatch) {
            select.value = firstMatch.value;
            onHeavyUnitSelected(select);
        }
    }

    function clearHeavyUnitFilter() {
        const input = document.getElementById('heavyUnitSearch');
        if (input) input.value = '';
        filterHeavyUnitOptions('');
    }
</script>
@endsection
