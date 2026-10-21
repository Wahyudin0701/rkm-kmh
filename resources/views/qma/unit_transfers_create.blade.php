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
                        <span class="text-[10px] text-emerald-700 font-semibold">Filter Berdasarkan Posisi</span>
                    </label>
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
                    <p class="text-[10px] text-slate-400 mt-1">Memilih lokasi awal akan memfilter unit alat berat yang berada di titik tersebut.</p>
                </div>

                <!-- PILIH ALAT BERAT YANG AKAN DIPINDAHKAN -->
                <div>
                    <label for="vehicle_id" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                        <span>Unit Alat Berat yang Dipindahkan <span class="text-rose-500">*</span></span>
                        <span id="unitCounterBadge" class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 hidden"></span>
                    </label>
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
                                    data-category="{{ $unit->unit_category ?? ($unit->parent?->brand_type ?? 'Unit Support') }}"
                                    data-location="{{ $unit->location ?: 'Site Kerinci' }}"
                                    data-site-status="{{ $unit->site_status ?? 'inside_site' }}"
                                    data-photo="{{ $unit->photo_url }}"
                                    {{ old('vehicle_id', $selectedVehicleId) == $unit->id ? 'selected' : '' }}>
                                {{ $unit->code }} - {{ $unit->brand_type }} [Posisi: {{ $unit->location ?: 'Site Kerinci' }}]
                            </option>
                        @endforeach
                    </select>
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
                    <p class="text-xs text-slate-500">Truk pengangkut dapat dipilih dari lokasi yang sama atau dimobilisasi dari pool/workshop lain.</p>
                </div>
            </div>

            <!-- Banner Info Logistik Rute (Muncul saat Truk dipilih) -->
            <div id="routeLogisticsBanner" class="hidden p-3.5 rounded-xl bg-blue-50/80 border border-blue-200 text-xs text-blue-900 flex items-start space-x-3">
                <i class="fa-solid fa-route text-blue-700 text-sm mt-0.5"></i>
                <div class="flex-1">
                    <span class="font-bold block mb-0.5">Rute Logistik Pengangkutan:</span>
                    <p id="routeLogisticsText" class="text-[11px] text-blue-800 leading-relaxed"></p>
                </div>
            </div>

            <!-- Grid 2 Kolom: Pilih Mobil Pengangkut & Nama Pengangkut -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Pilih Mobil dari Unit Kita -->
                <div>
                    <label for="transporter_vehicle_id" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                        <span>Pilih Kendaraan Pengangkut (Armada Kita)</span>
                        <span id="transporterLocationHint" class="text-[10px] text-slate-400 font-normal">Truk / Lowbed / Towing</span>
                    </label>
                    <select name="transporter_vehicle_id" 
                            id="transporter_vehicle_id" 
                            onchange="onTransporterSelected(this)"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800">
                        <option value="">-- Tanpa Truk Pengangkut / Rental Luar / Mobilisasi Mandiri --</option>
                        @foreach($transporterUnits as $tu)
                            <option value="{{ $tu->id }}" 
                                    data-code="{{ $tu->code }}"
                                    data-name="{{ $tu->code }} - {{ $tu->brand_type }}{{ $tu->license_plate ? ' (' . $tu->license_plate . ')' : '' }}"
                                    data-location="{{ $tu->location ?: 'Pool Workshop GA' }}"
                                    {{ old('transporter_vehicle_id') == $tu->id ? 'selected' : '' }}>
                                {{ $tu->code }} - {{ $tu->brand_type }} [Posisi: {{ $tu->location ?: 'Pool Workshop' }}]
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Input / Snapshot Nama Armada Pengangkut -->
                <div>
                    <label for="transporter_unit_name" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Identitas / Keterangan Kendaraan Pengangkut
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-truck absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" 
                               name="transporter_unit_name" 
                               id="transporter_unit_name" 
                               value="{{ old('transporter_unit_name') }}" 
                               placeholder="Contoh: DT-05 Hino 500 / Truk Towing Rekanan Luar" 
                               class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800 font-semibold">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Otomatis terisi jika memilih armada internal, atau ketik nama vendor/unit rekanan luar.</p>
                </div>
            </div>

            <!-- Opsi Sinkronisasi Lokasi Truk Pengangkut -->
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center space-x-3">
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

            <!-- Grid 2 Kolom: Supir Pengangkut & Catatan Angkutan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
        <!-- ============================================================= -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 space-y-5">
            <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-base shrink-0">
                    <i class="fa-solid fa-file-contract"></i>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">3. Berita Acara Serah Terima (BAST)</h3>
                    <p class="text-xs text-slate-500">Nomor surat, perihal, dan berkas lampiran berita acara resmi pemindahan alat berat.</p>
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
                               required 
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
                               required 
                               placeholder="Contoh: Berita Acara Pemindahan Excavator ke Area Adit 2" 
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
                    class="px-6 py-2.5 text-xs font-bold text-white bg-[#1b5e20] hover:bg-[#144919] rounded-xl transition shadow-sm cursor-pointer inline-flex items-center space-x-2">
                <i class="fa-solid fa-check"></i>
                <span>Simpan & Terbitkan BAST</span>
            </button>
        </div>
    </form>
</div>

<script>
    // Inisialisasi daftar semua unit alat berat dari options
    let allHeavyUnits = [];

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

        // Cek jika sudah ada lokasi awal (dari old value atau selectedOriginLocation)
        const originSelect = document.getElementById('origin_location');
        if (originSelect && originSelect.value) {
            onOriginLocationChanged(originSelect.value, "{{ old('vehicle_id', $selectedVehicleId ?? '') }}");
        }
    });

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
     * Handler saat Armada Pengangkut Terpilih
     */
    function onTransporterSelected(selectElement) {
        const option = selectElement.options[selectElement.selectedIndex];
        const nameInput = document.getElementById('transporter_unit_name');
        if (option && option.value) {
            const dataName = option.getAttribute('data-name');
            if (dataName) {
                nameInput.value = dataName;
            }
        }
        updateRouteBanner();
    }

    /**
     * Update Banner Logistik Rute Perjalanan
     */
    function updateRouteBanner() {
        const banner = document.getElementById('routeLogisticsBanner');
        const text = document.getElementById('routeLogisticsText');
        const transporterSelect = document.getElementById('transporter_vehicle_id');
        const originSelect = document.getElementById('origin_location');
        const destinationInput = document.getElementById('to_location');

        const optTransporter = transporterSelect.options[transporterSelect.selectedIndex];
        if (!optTransporter || !optTransporter.value) {
            banner.classList.add('hidden');
            return;
        }

        const transporterCode = optTransporter.getAttribute('data-code');
        const transporterLoc = optTransporter.getAttribute('data-location') || 'Pool Workshop';
        const originLoc = originSelect.value && originSelect.value !== 'ALL' ? originSelect.value : 'Lokasi Asal Alat Berat';
        const destLoc = destinationInput.value ? destinationInput.value : '[Lokasi Tujuan Baru]';

        banner.classList.remove('hidden');
        if (transporterLoc === originLoc) {
            text.innerHTML = `Truk <strong>${transporterCode}</strong> saat ini standby di <strong>${transporterLoc}</strong> (sama dengan lokasi alat berat) &rarr; Langsung mengangkut unit menuju tujuan <strong>${destLoc}</strong>.`;
        } else {
            text.innerHTML = `Truk <strong>${transporterCode}</strong> diberangkatkan dari <strong>${transporterLoc}</strong> &rarr; Menjemput alat berat di <strong>${originLoc}</strong> &rarr; Mengantarkannya ke tujuan <strong>${destLoc}</strong>.`;
        }
    }
</script>
@endsection
