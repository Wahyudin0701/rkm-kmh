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
                Lengkapi rincian pemindahan alat berat, armada pengangkut, dan data Berita Acara Serah Terima (BAST).
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
        <!-- BAGIAN 1: DATA PEMINDAHAN ALAT BERAT -->
        <!-- ============================================================= -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 space-y-5">
            <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-[#1b5e20] flex items-center justify-center font-bold text-base shrink-0">
                    <i class="fa-solid fa-truck-monster"></i>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">1. Data Alat Berat yang Dipindahkan</h3>
                    <p class="text-xs text-slate-500">Pilih unit alat berat dan tentukan lokasi penempatan barunya.</p>
                </div>
            </div>

            <!-- Pilih Alat Berat -->
            <div>
                <label for="vehicle_id" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Pilih Unit Alat Berat <span class="text-rose-500">*</span>
                </label>
                <select name="vehicle_id" 
                        id="vehicle_id" 
                        required 
                        onchange="onHeavyUnitSelected(this)"
                        class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800 font-semibold">
                    <option value="" disabled {{ old('vehicle_id') ? '' : 'selected' }}>-- Pilih Unit Alat Berat / Support Unit --</option>
                    @foreach($heavyUnits as $unit)
                        <option value="{{ $unit->id }}" 
                                data-code="{{ $unit->code }}"
                                data-brand="{{ $unit->brand_type }}"
                                data-category="{{ $unit->unit_category ?? ($unit->parent?->brand_type ?? 'Unit Support') }}"
                                data-location="{{ $unit->location ?: 'Site Kerinci' }}"
                                data-site-status="{{ $unit->site_status ?? 'inside_site' }}"
                                data-photo="{{ $unit->photo_url }}"
                                {{ old('vehicle_id') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->code }} - {{ $unit->brand_type }} (Lokasi Saat Ini: {{ $unit->location ?: 'Site Kerinci' }})
                        </option>
                    @endforeach
                </select>
                @error('vehicle_id')
                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                @enderror
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
                            <span class="text-slate-400">Lokasi saat ini:</span>
                            <span id="unitPreviewCurrentLocation" class="font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid 2 Kolom: Tanggal Transfer & Lokasi Awal -->
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

                <!-- Lokasi Awal (Read-only / Terisi Otomatis) -->
                <div>
                    <label for="from_location_display" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                        <span>Lokasi Awal (Terkini)</span>
                        <span class="text-[10px] text-emerald-700 font-semibold">Otomatis dari Sistem</span>
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-map-pin absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" 
                               id="from_location_display" 
                               readonly 
                               placeholder="Pilih unit alat berat terlebih dahulu" 
                               class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-100 text-slate-700 font-bold cursor-not-allowed">
                    </div>
                </div>
            </div>

            <!-- Grid 2 Kolom: Lokasi Tujuan & Area Tujuan (Inside/Outside Site) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Lokasi Tujuan -->
                <div>
                    <label for="to_location" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Lokasi Tujuan Baru <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-location-dot absolute left-3.5 top-1/2 -translate-y-1/2 text-emerald-700 text-xs"></i>
                        <input type="text" 
                               name="to_location" 
                               id="to_location" 
                               list="locationSuggestionList" 
                               value="{{ old('to_location') }}" 
                               required 
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

                <!-- Status Area Tujuan: Di Dalam / Di Luar Site -->
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
            </div>

            <!-- Deskripsi & Reported By -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Deskripsi / Keperluan Pemindahan -->
                <div class="sm:col-span-2">
                    <label for="description" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Deskripsi / Keperluan Pemindahan (Opsional)
                    </label>
                    <textarea name="description" 
                              id="description" 
                              rows="2" 
                              placeholder="Contoh: Relokasi excavator untuk percepatan galian bendungan intake Adit 2." 
                              class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800">{{ old('description') }}</textarea>
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
                    <p class="text-xs text-slate-500">Alat berat diangkut menggunakan truk/mobil pengangkut dari unit internal atau unit rekanan.</p>
                </div>
            </div>

            <!-- Grid 2 Kolom: Pilih Mobil Pengangkut & Nama Pengangkut -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Pilih Mobil dari Unit Kita -->
                <div>
                    <label for="transporter_vehicle_id" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                        <span>Pilih Mobil Pengangkut (Dari Unit Kita)</span>
                        <span class="text-[10px] text-slate-400 font-normal">Opsional</span>
                    </label>
                    <select name="transporter_vehicle_id" 
                            id="transporter_vehicle_id" 
                            onchange="onTransporterSelected(this)"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800">
                        <option value="">-- Tanpa Mobil Pengangkut / Unit Mandiri / Rental Luar --</option>
                        @foreach($transporterUnits as $tu)
                            <option value="{{ $tu->id }}" 
                                    data-name="{{ $tu->code }} - {{ $tu->brand_type }}{{ $tu->license_plate ? ' (' . $tu->license_plate . ')' : '' }}"
                                    {{ old('transporter_vehicle_id') == $tu->id ? 'selected' : '' }}>
                                {{ $tu->code }} - {{ $tu->brand_type }} {{ $tu->license_plate ? '[' . $tu->license_plate . ']' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Input / Snapshot Nama Armada Pengangkut -->
                <div>
                    <label for="transporter_unit_name" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Nama / Identitas Kendaraan Pengangkut
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-truck absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" 
                               name="transporter_unit_name" 
                               id="transporter_unit_name" 
                               value="{{ old('transporter_unit_name') }}" 
                               placeholder="Contoh: DT-05 Hino 500 / Truk Towing Rekanan" 
                               class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800 font-semibold">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Otomatis terisi jika memilih mobil dari unit kita, atau isi manual jika memakai vendor luar.</p>
                </div>
            </div>

            <!-- Grid 2 Kolom: Supir Pengangkut & Deskripsi Angkutan -->
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
                        Deskripsi / Catatan Angkutan (Opsional)
                    </label>
                    <textarea name="transporter_description" 
                              id="transporter_description" 
                              rows="2" 
                              placeholder="Contoh: Unit telah diikat dengan rantai lashing 4 titik, lampu rotary aktif, kondisi aman." 
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
    function onHeavyUnitSelected(selectElement) {
        const option = selectElement.options[selectElement.selectedIndex];
        if (!option || !option.value) return;

        const code = option.getAttribute('data-code');
        const brand = option.getAttribute('data-brand');
        const category = option.getAttribute('data-category');
        const location = option.getAttribute('data-location');
        const siteStatus = option.getAttribute('data-site-status');
        const photo = option.getAttribute('data-photo');

        // Isi display lokasi awal
        document.getElementById('from_location_display').value = location;

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
    }

    function onTransporterSelected(selectElement) {
        const option = selectElement.options[selectElement.selectedIndex];
        const nameInput = document.getElementById('transporter_unit_name');
        if (option && option.value) {
            const dataName = option.getAttribute('data-name');
            if (dataName) {
                nameInput.value = dataName;
            }
        }
    }

    // Trigger preview on initial load if vehicle_id is already selected (e.g. from validation error back)
    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('vehicle_id');
        if (select && select.value) {
            onHeavyUnitSelected(select);
        }
    });
</script>
@endsection
