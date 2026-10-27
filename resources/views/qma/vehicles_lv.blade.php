@extends('layouts.app')

@section('title', 'Light Vehicle - Admin QMA | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

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
                        title="Panduan Halaman Light Vehicle">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                Light Vehicle
            </h2>
        </div>

        <!-- Tombol Aksi Kanan -->
        <div class="grid grid-cols-2 gap-2.5 w-full sm:flex sm:items-center sm:gap-3 sm:w-auto">
            <!-- Dropdown Menu Ekspor Format it.allrole.web.id -->
            <div class="relative inline-block w-full sm:w-auto" id="exportLvDropdownContainer">
                <button type="button" 
                        onclick="toggleLvExportMenu(event)" 
                        id="btnLvExportTrigger"
                        class="w-full sm:w-auto flex items-center justify-center space-x-2 border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                    <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                    <span class="truncate">Ekspor LV</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
                </button>
                <div id="exportMenuLv" style="display: none;" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 z-50 overflow-hidden">
                    <a href="{{ route('qma.vehicles.lv.export_pdf', request()->query()) }}" target="_blank" class="flex items-center px-4 py-3 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#1b5e20] font-bold transition">
                        <i class="fa-regular fa-file-pdf text-rose-600 text-sm mr-2.5"></i>
                        <span>Format PDF (Cetak)</span>
                    </a>
                    <div class="h-px bg-slate-100 mx-2"></div>
                    <a href="{{ route('qma.vehicles.lv.export_excel', request()->query()) }}" class="flex items-center px-4 py-3 text-xs text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i class="fa-regular fa-file-excel text-emerald-600 text-sm mr-2.5"></i>
                        <span>Format Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            @can('qma.vehicles.create')
            <button onclick="openAddModal()" 
                    class="w-full sm:w-auto flex items-center justify-center space-x-2 bg-[#1b5e20] hover:bg-[#144919] text-white px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span class="truncate">Tambah LV</span>
            </button>
            @endcan
        </div>
    </div>


    <!-- Kotak Pencarian & Filter Status -->
    <form method="GET" action="{{ route('qma.vehicles.lv') }}" id="lvFilterForm" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <div class="relative w-full max-w-sm sm:max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" 
                   id="lvSearchInput"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari kode LV, nopol, merek, tipe, atau pemilik..." 
                   class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 shadow-sm transition">
        </div>

        <div class="flex items-center space-x-2">
            <select name="status" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20]">
                <option value="">Semua Status Operasi</option>
                <option value="Operation" {{ request('status') === 'Operation' ? 'selected' : '' }}>Operation (Siap Jalan)</option>
                <option value="Standby" {{ request('status') === 'Standby' ? 'selected' : '' }}>Standby</option>
                <option value="Breakdown" {{ request('status') === 'Breakdown' ? 'selected' : '' }}>Breakdown</option>
                <option value="Maintenance" {{ request('status') === 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
            </select>

            <select name="per_page" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20]">
                <option value="15" {{ request('per_page', '15') == '15' ? 'selected' : '' }}>15 / Hal</option>
                <option value="50" {{ request('per_page', '50') == '50' ? 'selected' : '' }}>50 / Hal</option>
                <option value="100" {{ request('per_page', '100') == '100' ? 'selected' : '' }}>Semua (100)</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition shadow-sm cursor-pointer whitespace-nowrap">
                Cari
            </button>

            <div id="lvResetWrapper" class="flex items-center">
                @if(request()->anyFilled(['search', 'status', 'per_page']))
                    <a id="lvResetBtn" href="{{ route('qma.vehicles.lv') }}" class="text-xs text-rose-600 hover:text-rose-800 font-semibold flex items-center space-x-1 whitespace-nowrap ml-2">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>Reset Filter</span>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Tabel Data Light Vehicle (Header Navy Solid #06213f) -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4">KODE LV</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">QR CODE</th>
                        <th class="py-3.5 px-4">MEREK & TIPE</th>
                        <th class="py-3.5 px-4 text-center">NO. POLISI</th>
                        <th class="py-3.5 px-4">DIVISI</th>
                        <th class="py-3.5 px-4">KEPEMILIKAN</th>
                        <th class="py-3.5 px-4 text-right">ODOMETER</th>
                        <th class="py-3.5 px-4 text-center">STATUS</th>
                        <th class="py-3.5 px-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody id="lvTableBody" class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($vehicles as $index => $v)
                        <tr class="lv-row hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                {{ $vehicles->firstItem() + $index }}
                            </td>

                            <td class="py-3.5 px-4 font-bold text-slate-900 font-mono text-xs whitespace-nowrap">
                                <span class="bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded border border-emerald-200">
                                    {{ $v->code }}
                                </span>
                            </td>

                            <!-- QR Code Stiker Unit -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <button type="button" 
                                        onclick="openQrModal('{{ $v->code }}', '{{ addslashes($v->brand_type ?? '') }}', '{{ addslashes($v->license_plate ?? '-') }}', '{{ addslashes($v->division ?? '-') }}', '{{ url('/gate-activity/' . $v->code) }}', this)"
                                        data-qr-base64="{{ base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::size(240)->margin(1)->generate(url('/gate-activity/' . $v->code))) }}"
                                        class="group inline-flex items-center justify-center p-1 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 transition shadow-2xs cursor-pointer"
                                        title="Klik untuk Lihat, Unduh & Cetak Stiker QR {{ $v->code }}">
                                    <div class="w-7 h-7 bg-white rounded-lg border border-slate-200 group-hover:border-emerald-300 flex items-center justify-center p-0.5 overflow-hidden [&>svg]:w-full [&>svg]:h-full shadow-2xs">
                                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(24)->margin(0)->generate(url('/gate-activity/' . $v->code)) !!}
                                    </div>
                                </button>
                            </td>

                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                <div class="flex items-center space-x-3">
                                    @if($v->photo_path)
                                        <img src="{{ $v->photo_url }}" alt="{{ $v->code }}" class="w-9 h-9 rounded-lg object-cover border border-slate-200 shadow-2xs flex-shrink-0 cursor-pointer" onclick='openDetailModal(@json($v))'>
                                    @else
                                        <div class="w-9 h-9 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 flex-shrink-0">
                                            <i class="fa-solid fa-car text-xs"></i>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="truncate">{{ $v->brand_type }}</div>
                                        @if($v->serial_number)
                                            <div class="text-[10px] text-slate-400 font-mono font-normal truncate">SN: {{ $v->serial_number }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($v->license_plate)
                                    <span class="text-[11px] font-mono font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                        {{ $v->license_plate }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">-</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-slate-600 font-medium">
                                {{ $v->division ?? '-' }}
                            </td>

                            <td class="py-3.5 px-4 text-slate-600">
                                {{ $v->ownership ?? '-' }}
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-800 whitespace-nowrap">
                                {{ number_format($v->current_km, 0, ',', '.') }}
                                <span class="text-[10px] text-slate-400 font-sans font-normal">KM</span>
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($v->status === 'Operation')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-circle-check mr-1 text-[9px]"></i> Operation
                                    </span>
                                @elseif($v->status === 'Standby')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fa-solid fa-pause mr-1 text-[9px]"></i> Standby
                                    </span>
                                @elseif($v->status === 'Breakdown')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fa-solid fa-triangle-exclamation mr-1 text-[9px]"></i> Breakdown
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                                        {{ $v->status }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <button onclick='openDetailModal(@json($v))' 
                                            class="inline-flex items-center space-x-1 text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-2 py-1 rounded-lg transition"
                                            title="Lihat Detail Kendaraan">
                                        <i class="fa-regular fa-eye text-sm"></i>
                                    </button>
                                    @can('qma.vehicles.edit')
                                    <button onclick='openEditModal(@json($v))' 
                                            class="inline-flex items-center space-x-1 text-amber-600 hover:text-amber-800 hover:bg-amber-50 px-2 py-1 rounded-lg transition"
                                            title="Edit Data Light Vehicle">
                                        <i class="fa-regular fa-pen-to-square text-sm"></i>
                                    </button>
                                    @endcan
                                    @can('qma.vehicles.delete')
                                    <button type="button" 
                                            onclick="openDeleteModal('{{ $v->id }}', '{{ $v->code }}', '{{ addslashes($v->brand_type ?? '') }}')"
                                            class="inline-flex items-center space-x-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-2 py-1 rounded-lg transition"
                                            title="Hapus Kendaraan">
                                        <i class="fa-regular fa-trash-can text-sm"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="serverLvEmptyRow">
                            <td colspan="10" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
                                @if(request('search'))
                                    <p class="text-sm font-semibold text-slate-700">
                                        Data "<span class="text-rose-600 font-bold">{{ request('search') }}</span>" tidak ditemukan di tabel
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        Pastikan kata kunci yang dicari sudah benar.
                                    </p>
                                    <div class="mt-4">
                                        <a href="{{ route('qma.vehicles.lv') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                            <span>Hapus Pencarian</span>
                                        </a>
                                    </div>
                                @else
                                    <p class="text-sm font-semibold text-slate-700">Belum ada armada Light Vehicle</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Belum ada data armada Light Vehicle yang sesuai dengan kriteria filter.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="lvPaginationContainer" class="p-4 bg-slate-50 border-t border-slate-200">
            {{ $vehicles->links() }}
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: TAMBAH LIGHT VEHICLE -->
<!-- ========================================================================= -->
<div id="addVehicleModal" 
     onclick="if(event.target === this) closeAddModal()"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full p-6 sm:p-8 border border-slate-100">
        <!-- Header Modal Bersih Tanpa Icon Box Sesuai Referensi Tambah Aset -->
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                Tambah Light Vehicle
            </h3>
            <button type="button" onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="addVehicleForm" action="{{ route('qma.vehicles.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="category" value="light_vehicle">

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Foto Unit Kendaraan (Opsional)</label>
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
                        <p class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, WebP (Maks. 10MB). Otomatis dikompresi agar hemat storage.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kode LV <span class="text-rose-500">*</span></label>
                    <input type="text" 
                           name="code" 
                           id="addCodeInput"
                           required 
                           value="{{ old('code') }}"
                           placeholder="MISAL: LV01, LV62" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border {{ $errors->has('code') ? 'border-rose-500 ring-2 ring-rose-500/10' : 'border-slate-200' }} bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono uppercase">
                    
                    <p id="codeHint" class="text-[11px] text-slate-400 mt-1 {{ $errors->has('code') ? 'hidden' : '' }}">* Kode LV tidak boleh sama dengan kode aset yang lain.</p>
                    
                    <p id="codeError" class="text-xs text-rose-600 font-semibold mt-1.5 {{ $errors->has('code') ? '' : 'hidden' }} flex items-center">
                        <i class="fa-solid fa-circle-exclamation mr-1.5 flex-shrink-0"></i>
                        <span id="codeErrorText">{{ $errors->first('code') ?? 'Kode LV sudah ada, silakan gunakan kode lain.' }}</span>
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Nomor Polisi (Nopol)</label>
                    <input type="text" name="license_plate" value="{{ old('license_plate') }}" placeholder="Misal: BH 1024 AB" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition uppercase font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Merek & Tipe Kendaraan <span class="text-rose-500">*</span></label>
                <input type="text" name="brand_type" required placeholder="Misal: Toyota Hilux 4x4 Double Cabin" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Divisi Operasional</label>
                    <input type="text" name="division" placeholder="Misal: HSE, Civil, Bendungan, GA" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kepemilikan Aset</label>
                    <input type="text" name="ownership" placeholder="Misal: PT Kerinci Merangin Hidro" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Status Operasi <span class="text-rose-500">*</span></label>
                    <select name="status" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        <option value="Operation">Operation (Siap Jalan)</option>
                        <option value="Standby">Standby</option>
                        <option value="Breakdown">Breakdown</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Odometer Awal (KM) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" name="current_km" required value="0" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
            </div>

            <!-- Footer Tombol Bersih Sesuai Referensi Tambah Aset -->
            <div class="pt-4 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeAddModal()" 
                        class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs transition shadow-sm">
                    Simpan Light Vehicle
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT LIGHT VEHICLE -->
<!-- ========================================================================= -->
<div id="editVehicleModal" 
     onclick="if(event.target === this) closeEditModal()"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full p-6 sm:p-8 border border-slate-100">
        <!-- Header Modal Bersih Tanpa Icon Box Sesuai Referensi Tambah Aset -->
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                Edit Light Vehicle
            </h3>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="editVehicleForm" action="" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="category" value="light_vehicle">

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Foto Unit Kendaraan</label>
                <div class="flex items-center space-x-3.5 p-3 rounded-2xl border border-slate-200 bg-slate-50/50">
                    <div id="editPhotoPreviewContainer" class="w-14 h-14 rounded-xl border border-slate-200 bg-white flex items-center justify-center overflow-hidden flex-shrink-0 shadow-2xs">
                        <img id="editPhotoPreview" src="" alt="Foto Unit" class="w-full h-full object-cover hidden">
                        <i id="editPhotoPlaceholder" class="fa-solid fa-car text-slate-400 text-base"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <input type="file" 
                               name="photo" 
                               id="editPhotoInput" 
                               accept="image/jpeg,image/png,image/jpg,image/webp" 
                               onchange="previewVehicleImage(this, 'editPhotoPreview', 'editPhotoPlaceholder')"
                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-[#1b5e20] hover:file:bg-emerald-100 cursor-pointer">
                        <p class="text-[10px] text-slate-400 mt-1">Pilih foto baru jika ingin mengganti. Otomatis dikompresi hemat storage server.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kode LV <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" id="editCode" required 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono uppercase">
                    
                    <p id="editCodeError" class="text-xs text-rose-600 font-semibold mt-1.5 hidden flex items-center">
                        <i class="fa-solid fa-circle-exclamation mr-1.5 flex-shrink-0"></i>
                        <span id="editCodeErrorText">Kode LV sudah ada, silakan gunakan kode lain.</span>
                    </p>
                    <p id="editCodeHint" class="text-[11px] text-slate-400 mt-1">* Kode LV tidak boleh sama dengan kendaraan lain.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Nomor Polisi (Nopol)</label>
                    <input type="text" name="license_plate" id="editLicensePlate" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition uppercase font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Merek & Tipe Kendaraan <span class="text-rose-500">*</span></label>
                <input type="text" name="brand_type" id="editBrandType" required 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Divisi Operasional</label>
                    <input type="text" name="division" id="editDivision" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kepemilikan Aset</label>
                    <input type="text" name="ownership" id="editOwnership" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Status Operasi <span class="text-rose-500">*</span></label>
                    <select name="status" id="editStatus" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        <option value="Operation">Operation (Siap Jalan)</option>
                        <option value="Standby">Standby</option>
                        <option value="Breakdown">Breakdown</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Odometer Saat Ini (KM) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" name="current_km" id="editCurrentKm" required 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
            </div>

            <!-- Footer Tombol Bersih Sesuai Referensi Tambah Aset -->
            <div class="pt-4 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeEditModal()" 
                        class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs transition shadow-sm">
                    Perbarui Data
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: DETAIL LIGHT VEHICLE -->
<!-- ========================================================================= -->
<div id="detailVehicleModal" 
     onclick="if(event.target === this) closeDetailModal()"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full p-6 sm:p-8 border border-slate-100">
        <!-- Header Modal Bersih Tanpa Icon Box Sesuai Referensi Card Tambah Data -->
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                Detail Light Vehicle
            </h3>
            <button type="button" onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="space-y-4">
            <!-- Foto Kendaraan di Detail Modal -->
            <div id="detailPhotoContainer" class="w-full h-48 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden relative hidden">
                <img id="detailPhoto" src="" alt="Foto Unit LV" class="w-full h-full object-cover">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kode LV</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold flex items-center">
                        <span id="detailCode" class="bg-blue-50 text-blue-800 px-2 py-0.5 rounded border border-blue-200">-</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Nomor Polisi (Nopol)</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold flex items-center uppercase">
                        <span id="detailNopol">-</span>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Merek & Tipe Kendaraan</label>
                <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-bold flex items-center">
                    <span id="detailBrand">-</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Divisi Operasional</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-medium flex items-center">
                        <span id="detailDivision">-</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kepemilikan Aset</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-medium flex items-center">
                        <span id="detailOwnership">-</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Status Operasi</label>
                    <div class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white flex items-center">
                        <span id="detailStatusBadge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span id="detailStatus">Operation</span>
                        </span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Odometer Saat Ini</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-emerald-700 font-mono font-bold flex items-center">
                        <span id="detailKm">0 KM</span>
                    </div>
                </div>
            </div>

            <!-- Footer Tombol Bersih Sesuai Referensi Tambah Aset -->
            <div class="pt-4 flex items-center justify-end">
                <button type="button" onclick="closeDetailModal()" 
                        class="px-6 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs transition shadow-sm">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: KONFIRMASI HAPUS KENDARAAN (LIGHT VEHICLE) -->
<!-- ========================================================================= -->
<div id="deleteVehicleModal" 
     onclick="if(event.target === this) closeDeleteModal()"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
    <div class="bg-white rounded-3xl shadow-2xl max-w-sm sm:max-w-md w-full p-6 sm:p-7 border border-slate-100 transform transition-all text-center">
        <!-- Icon Peringatan Elegan -->
        <div class="w-14 h-14 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4 text-2xl shadow-sm">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h3 class="text-lg font-bold text-slate-900 tracking-tight">
            Konfirmasi Hapus Kendaraan
        </h3>

        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
            Apakah Anda yakin ingin menghapus data kendaraan <span id="deleteVehicleCode" class="font-bold font-mono text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200"></span>?
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

<!-- ========================================================================= -->
<!-- MODAL: PREVIEW & CETAK STIKER QR CODE KENDARAAN                            -->
<!-- ========================================================================= -->
<div id="qrVehicleModal" 
     onclick="if(event.target === this) closeQrModal()"
     class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-slate-100 transform transition-all my-8">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#1b5e20] flex items-center justify-center font-bold text-base border border-emerald-200/60 shadow-2xs">
                    <i class="fa-solid fa-qrcode"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight leading-tight">Stiker QR Code Kendaraan</h3>
                    <p class="text-xs text-slate-500">Siap dicetak dan ditempelkan pada fisik armada unit</p>
                </div>
            </div>
            <button type="button" 
                    onclick="closeQrModal()" 
                    class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Area Kartu Stiker Fisik (Bisa Dicetak Langsung) -->
        <div class="mt-5">
            <div id="printableStickerArea" class="bg-white border-2 border-slate-900 rounded-3xl p-5 text-center shadow-sm max-w-[360px] mx-auto">
                <!-- Kop Stiker Resmi Perusahaan -->
                <div class="border-b-2 border-slate-900 pb-3 mb-4">
                    <div class="text-[10px] font-extrabold uppercase tracking-widest text-[#1b5e20]">PT KERINCI MERANGIN HIDRO</div>
                    <h4 class="text-xs font-black uppercase tracking-tight text-slate-900 mt-0.5">TIMESHEET KENDARAAN OPERASIONAL</h4>
                    <div class="text-[9px] font-bold text-slate-500 tracking-wider">LIGHT VEHICLE (LV) • RKM SYSTEM</div>
                </div>

                <!-- Kontainer QR Code Vektor -->
                <div class="bg-white p-3.5 rounded-2xl border-2 border-slate-200 inline-block shadow-2xs mb-4">
                    <div id="modalQrCodeContainer" class="w-52 h-52 flex items-center justify-center [&>svg]:w-full [&>svg]:h-full">
                        <!-- SVG QR Code di-inject secara dinamis di sini -->
                    </div>
                </div>

                <!-- Rincian Identitas Unit Kendaraan -->
                <div class="space-y-1.5 mb-3 text-left bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200">
                    <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">KODE UNIT:</span>
                        <span id="modalQrCodeText" class="font-mono font-extrabold text-sm text-[#1b5e20] bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">LV01</span>
                    </div>
                    <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">NO. POLISI:</span>
                        <span id="modalQrPlateText" class="font-mono font-bold text-xs text-amber-900 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">BH 1024 AB</span>
                    </div>
                    <div class="flex items-start justify-between pb-1.5 border-b border-slate-200/60">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">MEREK & TIPE:</span>
                        <span id="modalQrBrandText" class="font-bold text-xs text-slate-900 text-right max-w-[200px]">TOYOTA HILUX 4X4 DC</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">DIVISI:</span>
                        <span id="modalQrDivisionText" class="font-bold text-xs text-slate-700">HSE</span>
                    </div>
                </div>

                <!-- Panduan Driver -->
                <div class="text-[10px] text-slate-700 bg-emerald-50/90 p-2.5 rounded-xl border border-emerald-200/60 flex items-center space-x-2.5 text-left">
                    <div class="w-6 h-6 rounded-lg bg-[#1b5e20] text-white flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="w-3.5 h-3.5 fill-current">
                            <path d="M149.1 64l-21.7 48L64 112c-35.3 0-64 28.7-64 64L0 400c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-224c0-35.3-28.7-64-64-64l-63.4 0-21.7-48L149.1 64zM256 208a96 96 0 1 1 0 192 96 96 0 1 1 0-192zm0 48a48 48 0 1 0 0 96 48 48 0 1 0 0-96z"/>
                        </svg>
                    </div>
                    <span class="leading-snug">
                        <strong>Scan QR ini</strong> menggunakan kamera HP untuk membuka formulir pencatatan kilometer & perlintasan gerbang.
                    </span>
                </div>

                <!-- Tautan Langsung -->
                <div class="mt-2.5 text-[9px] font-mono text-slate-400 break-all" id="modalQrUrlDisplay">
                    http://.../gate-activity/LV01
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Cetak & Unduh -->
        <div class="pt-4 mt-5 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
            <div class="flex items-center space-x-2">
                <!-- Tombol Cetak Stiker -->
                <button type="button" 
                        onclick="printQrSticker()" 
                        class="flex-1 sm:flex-none inline-flex items-center justify-center space-x-1.5 px-4 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs transition shadow-sm cursor-pointer">
                    <i class="fa-solid fa-print"></i>
                    <span>Cetak Stiker</span>
                </button>

                <!-- Tombol Download PNG -->
                <button type="button" 
                        id="btnDownloadSticker"
                        onclick="downloadQrCode()" 
                        class="flex-1 sm:flex-none inline-flex items-center justify-center space-x-1.5 px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer">
                    <i class="fa-solid fa-download"></i>
                    <span>Unduh PNG</span>
                </button>
            </div>

            <div class="flex items-center space-x-2 justify-end">
                <!-- Test Form Scan -->
                <a id="modalQrTestLink" 
                   href="#" 
                   target="_blank" 
                   class="inline-flex items-center space-x-1 px-3 py-2.5 rounded-xl text-xs font-semibold text-blue-700 hover:bg-blue-50 transition border border-transparent hover:border-blue-200"
                   title="Buka form input penggunaan kendaraan di tab baru untuk pengetesan">
                    <i class="fa-solid fa-arrow-up-right-from-square text-[11px]"></i>
                    <span>Tes Form</span>
                </a>

                <button type="button" 
                        onclick="closeQrModal()" 
                        class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>



<script>
    // Daftar semua kode kendaraan LV yang sudah terdaftar di sistem
    const existingVehicleCodes = @json($existingCodes ?? \App\Models\Vehicle::lightVehicles()->pluck('code')->map(fn($c) => strtoupper(trim($c))));

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
            showCodeError('Kode LV sudah ada, silakan gunakan kode lain.');
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
        });
    }

    // Elemen modal Edit
    const editCodeInput = document.getElementById('editCode');
    const editCodeError = document.getElementById('editCodeError');
    const editCodeErrorText = document.getElementById('editCodeErrorText');
    const editCodeHint = document.getElementById('editCodeHint');
    const editVehicleForm = document.getElementById('editVehicleForm');
    let currentEditVehicleCode = '';

    function checkEditDuplicateCode() {
        if (!editCodeInput) return false;
        const val = editCodeInput.value.trim().toUpperCase();
        if (!val) {
            hideEditCodeError();
            return false;
        }

        // Jika kode sama dengan kode kendaraan ini saat ini, maka bukan duplikat
        if (val === currentEditVehicleCode) {
            hideEditCodeError();
            return false;
        }

        if (existingVehicleCodes.includes(val)) {
            showEditCodeError('Kode LV sudah ada, silakan gunakan kode lain.');
            return true;
        } else {
            hideEditCodeError();
            return false;
        }
    }

    function showEditCodeError(message) {
        if (editCodeErrorText) editCodeErrorText.innerText = message;
        if (editCodeError) editCodeError.classList.remove('hidden');
        if (editCodeHint) editCodeHint.classList.add('hidden');
        if (editCodeInput) {
            editCodeInput.classList.add('border-rose-500', 'ring-2', 'ring-rose-500/10');
            editCodeInput.classList.remove('border-slate-200');
        }
    }

    function hideEditCodeError() {
        if (editCodeError) editCodeError.classList.add('hidden');
        if (editCodeHint) editCodeHint.classList.remove('hidden');
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
        document.getElementById('editBrandType').value = v.brand_type || '';
        document.getElementById('editLicensePlate').value = v.license_plate || '';
        document.getElementById('editDivision').value = v.division || '';
        document.getElementById('editOwnership').value = v.ownership || '';
        document.getElementById('editStatus').value = v.status || 'Operation';
        document.getElementById('editCurrentKm').value = v.current_km || 0;

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
        document.getElementById('detailCode').innerText = v.code || '-';
        document.getElementById('detailBrand').innerText = v.brand_type || '-';
        document.getElementById('detailNopol').innerText = v.license_plate || '-';
        document.getElementById('detailStatus').innerText = v.status || '-';
        document.getElementById('detailKm').innerText = Number(v.current_km || 0).toLocaleString('id-ID') + ' KM';
        document.getElementById('detailDivision').innerText = v.division || '-';
        document.getElementById('detailOwnership').innerText = v.ownership || '-';

        const detailPhotoContainer = document.getElementById('detailPhotoContainer');
        const detailPhoto = document.getElementById('detailPhoto');
        if (v.photo_url) {
            if (detailPhoto) detailPhoto.src = v.photo_url;
            if (detailPhotoContainer) detailPhotoContainer.classList.remove('hidden');
        } else {
            if (detailPhoto) detailPhoto.src = '';
            if (detailPhotoContainer) detailPhotoContainer.classList.add('hidden');
        }

        const badge = document.getElementById('detailStatusBadge');
        if (badge) {
            badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border ';
            if (v.status === 'Operation') {
                badge.className += 'bg-emerald-50 text-emerald-700 border-emerald-200';
            } else if (v.status === 'Standby') {
                badge.className += 'bg-amber-50 text-amber-700 border-amber-200';
            } else if (v.status === 'Breakdown') {
                badge.className += 'bg-rose-50 text-rose-700 border-rose-200';
            } else {
                badge.className += 'bg-slate-100 text-slate-700 border-slate-200';
            }
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

    // =========================================================================
    // REAL-TIME CLIENT-SIDE SEARCH
    // =========================================================================
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('lvSearchInput');
        const filterForm = document.getElementById('lvFilterForm');
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

    @if($errors->any())
        document.addEventListener('DOMContentLoaded', function() {
            openAddModal();
        });
    @endif

    // Buka & Tutup Modal QR Code Stiker
    let currentQrCode = '';
    let currentQrBase64 = '';

    function openQrModal(code, brand, plate, division, url, btn) {
        currentQrCode = code;
        const modal = document.getElementById('qrVehicleModal');
        if (!modal) return;

        // Ambil SVG QR Code dari data attribute button
        const container = document.getElementById('modalQrCodeContainer');
        const base64Svg = btn ? btn.getAttribute('data-qr-base64') : '';
        currentQrBase64 = base64Svg || '';
        if (container && base64Svg) {
            try {
                container.innerHTML = atob(base64Svg);
            } catch(e) {
                console.error('Error decoding QR SVG:', e);
            }
        }

        const elCode = document.getElementById('modalQrCodeText');
        const elPlate = document.getElementById('modalQrPlateText');
        const elBrand = document.getElementById('modalQrBrandText');
        const elDivision = document.getElementById('modalQrDivisionText');
        const elUrl = document.getElementById('modalQrUrlDisplay');
        const testLink = document.getElementById('modalQrTestLink');

        if (elCode) elCode.textContent = code;
        if (elPlate) elPlate.textContent = plate || '-';
        if (elBrand) elBrand.textContent = brand || '-';
        if (elDivision) elDivision.textContent = division || '-';
        if (elUrl) elUrl.textContent = url;
        if (testLink) testLink.href = url;

        modal.classList.remove('hidden');
        document.documentElement.classList.add('modal-open');
        document.body.classList.add('modal-open');
    }

    function closeQrModal() {
        const modal = document.getElementById('qrVehicleModal');
        if (modal) {
            modal.classList.add('hidden');
            document.documentElement.classList.remove('modal-open');
            document.body.classList.remove('modal-open');
        }
    }

    function printQrSticker() {
        window.print();
    }

    // =========================================================================
    // UNDUH STIKER QR: Pure Canvas API (tanpa html2canvas)
    // Menggambar seluruh kartu stiker secara manual ke Canvas, termasuk QR code.
    // Ini menjamin QR Code PASTI ter-render karena menggunakan img.onload.
    // =========================================================================
    function downloadQrCode() {
        const code = (currentQrCode || 'LV').toUpperCase().replace(/[^A-Z0-9_-]/g, '');
        const btnEl = document.getElementById('btnDownloadSticker');
        const originalBtnHtml = btnEl ? btnEl.innerHTML : '';

        if (btnEl) {
            btnEl.disabled = true;
            btnEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i><span>Memproses...</span>';
        }

        // Baca data teks dari modal
        const plate = document.getElementById('modalQrPlateText')?.textContent?.trim() || '-';
        const brand = document.getElementById('modalQrBrandText')?.textContent?.trim() || '-';
        const division = document.getElementById('modalQrDivisionText')?.textContent?.trim() || '-';
        const urlText = document.getElementById('modalQrUrlDisplay')?.textContent?.trim() || '';

        // Dapatkan SVG source untuk QR Code
        let svgDataUri = '';
        if (currentQrBase64) {
            svgDataUri = 'data:image/svg+xml;base64,' + currentQrBase64;
        } else {
            const svgEl = document.querySelector('#modalQrCodeContainer svg');
            if (svgEl) {
                const raw = new XMLSerializer().serializeToString(svgEl);
                svgDataUri = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(raw)));
            }
        }

        if (!svgDataUri) {
            alert('Data QR Code tidak ditemukan. Silakan buka kembali modal QR.');
            if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = originalBtnHtml; }
            return;
        }

        // Load QR SVG sebagai Image terlebih dahulu, baru gambar semua di onload
        const qrImg = new Image();
        qrImg.onload = function() {
            // ---- Konfigurasi Dimensi Canvas ----
            // Ukuran dihitung secara proporsional agar kartu padat, seimbang, dan tanpa ruang kosong di bawah
            const W = 1200;  // lebar canvas total
            const H = 1500;  // tinggi canvas total (sebelumnya 1900 sehingga ada ruang kosong besar)
            const PAD = 50;  // padding luar putih
            const CW = W - PAD * 2;  // 1100 (lebar kartu)
            const CH = H - PAD * 2;  // 1400 (tinggi kartu)
            const R = 55;    // radius sudut kartu
            const CX = PAD;  // x kartu (50)
            const CY = PAD;  // y kartu (50)

            const canvas = document.createElement('canvas');
            canvas.width = W;
            canvas.height = H;
            const ctx = canvas.getContext('2d');

            // -- Helper: rounded rect --
            function roundRect(x, y, w, h, r) {
                ctx.beginPath();
                if (ctx.roundRect) { ctx.roundRect(x, y, w, h, r); }
                else {
                    ctx.moveTo(x + r, y);
                    ctx.lineTo(x + w - r, y); ctx.quadraticCurveTo(x + w, y, x + w, y + r);
                    ctx.lineTo(x + w, y + h - r); ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
                    ctx.lineTo(x + r, y + h); ctx.quadraticCurveTo(x, y + h, x, y + h - r);
                    ctx.lineTo(x, y + r); ctx.quadraticCurveTo(x, y, x + r, y);
                }
                ctx.closePath();
            }

            // ---- 1. Background Putih Luar ----
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, W, H);

            // ---- 2. Bingkai Kartu Stiker ----
            roundRect(CX, CY, CW, CH, R);
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 5;
            ctx.stroke();

            // ---- 3. Header: Kop Perusahaan ----
            const headerCenterX = W / 2;
            let curY = CY + 52;

            ctx.textAlign = 'center';
            ctx.fillStyle = '#1b5e20';
            ctx.font = '800 26px "Plus Jakarta Sans", Arial, sans-serif';
            ctx.fillText('PT KERINCI MERANGIN HIDRO', headerCenterX, curY);

            curY += 40;
            ctx.fillStyle = '#0f172a';
            ctx.font = '900 32px "Plus Jakarta Sans", Arial, sans-serif';
            ctx.fillText('TIMESHEET KENDARAAN OPERASIONAL', headerCenterX, curY);

            curY += 30;
            ctx.fillStyle = '#64748b';
            ctx.font = '700 22px "Plus Jakarta Sans", Arial, sans-serif';
            ctx.fillText('LIGHT VEHICLE (LV) \u2022 RKM SYSTEM', headerCenterX, curY);

            // Garis pemisah header
            curY += 22;
            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.moveTo(CX + 35, curY);
            ctx.lineTo(CX + CW - 35, curY);
            ctx.stroke();

            // ---- 4. QR Code (DIBESARKAN) ----
            const qrSize = 580; // Dibesarkan dari 480 menjadi 580
            const qrBoxPadding = 22;
            const qrBoxSize = qrSize + qrBoxPadding * 2; // 624
            const qrBoxX = (W - qrBoxSize) / 2;
            curY += 28;
            const qrBoxY = curY;
            const qrX = (W - qrSize) / 2;
            const qrY = qrBoxY + qrBoxPadding;

            // Kotak border QR Code
            roundRect(qrBoxX, qrBoxY, qrBoxSize, qrBoxSize, 32);
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.strokeStyle = '#cbd5e1';
            ctx.lineWidth = 3;
            ctx.stroke();

            // Gambar QR Code tajam & presisi
            ctx.drawImage(qrImg, qrX, qrY, qrSize, qrSize);

            // ---- 5. Info Box Detail Kendaraan ----
            curY = qrBoxY + qrBoxSize + 30;
            const infoX = CX + 40;
            const infoW = CW - 80;
            const infoH = 370;
            const infoR = 28;

            roundRect(infoX, curY, infoW, infoH, infoR);
            ctx.fillStyle = '#f8fafc';
            ctx.fill();
            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 2;
            ctx.stroke();

            // -- Helper baris info --
            const rowLeft = infoX + 30;
            const rowRight = infoX + infoW - 30;
            let rowY = curY + 54;

            function drawInfoRow(label, value, isBadge, badgeColor, badgeBg, badgeBorder) {
                // Label
                ctx.textAlign = 'left';
                ctx.fillStyle = '#64748b';
                ctx.font = '700 24px "Plus Jakarta Sans", Arial, sans-serif';
                ctx.fillText(label, rowLeft, rowY);

                if (isBadge) {
                    ctx.font = '700 26px monospace';
                    const tw = ctx.measureText(value).width;
                    const bx = rowRight - tw - 28;
                    roundRect(bx, rowY - 30, tw + 28, 40, 10);
                    ctx.fillStyle = badgeBg;
                    ctx.fill();
                    ctx.strokeStyle = badgeBorder;
                    ctx.lineWidth = 2;
                    ctx.stroke();
                    ctx.fillStyle = badgeColor;
                    ctx.fillText(value, bx + 14, rowY);
                } else {
                    ctx.textAlign = 'right';
                    ctx.fillStyle = '#0f172a';
                    if (value.length > 25) {
                        ctx.font = '700 22px "Plus Jakarta Sans", Arial, sans-serif';
                    } else {
                        ctx.font = '700 26px "Plus Jakarta Sans", Arial, sans-serif';
                    }
                    ctx.fillText(value, rowRight, rowY);
                }

                // Garis pemisah antar baris
                rowY += 18;
                ctx.strokeStyle = '#e2e8f0';
                ctx.lineWidth = 1.5;
                ctx.beginPath();
                ctx.moveTo(rowLeft, rowY);
                ctx.lineTo(rowRight, rowY);
                ctx.stroke();

                rowY += 64;
            }

            drawInfoRow('KODE UNIT:', code, true, '#1b5e20', '#ecfdf5', '#a7f3d0');
            drawInfoRow('NO. POLISI:', plate, true, '#78350f', '#fffbeb', '#fde68a');
            drawInfoRow('MEREK & TIPE:', brand, false);
            // Divisi (baris terakhir tanpa garis pembatas bawah)
            ctx.textAlign = 'left';
            ctx.fillStyle = '#64748b';
            ctx.font = '700 24px "Plus Jakarta Sans", Arial, sans-serif';
            ctx.fillText('DIVISI:', rowLeft, rowY);
            ctx.textAlign = 'right';
            ctx.fillStyle = '#334155';
            ctx.font = '700 26px "Plus Jakarta Sans", Arial, sans-serif';
            ctx.fillText(division, rowRight, rowY);

            // ---- 6. Banner Instruksi Scan HP ----
            curY = curY + infoH + 22;
            const banH = 104;
            roundRect(infoX, curY, infoW, banH, 22);
            ctx.fillStyle = '#ecfdf5';
            ctx.fill();
            ctx.strokeStyle = '#a7f3d0';
            ctx.lineWidth = 2;
            ctx.stroke();

            // Kotak ikon kamera
            roundRect(infoX + 20, curY + 22, 60, 60, 16);
            ctx.fillStyle = '#1b5e20';
            ctx.fill();
            // Grafis ikon kamera
            ctx.fillStyle = '#ffffff';
            roundRect(infoX + 32, curY + 42, 36, 26, 6);
            ctx.fill();
            ctx.fillStyle = '#1b5e20';
            ctx.beginPath();
            ctx.arc(infoX + 50, curY + 55, 7, 0, Math.PI * 2);
            ctx.fill();
            ctx.fillStyle = '#ffffff';
            roundRect(infoX + 43, curY + 36, 14, 7, 2);
            ctx.fill();

            // Teks instruksi
            ctx.textAlign = 'left';
            ctx.fillStyle = '#1e293b';
            ctx.font = '600 21px "Plus Jakarta Sans", Arial, sans-serif';
            ctx.fillText('Scan QR ini menggunakan kamera HP untuk', infoX + 98, curY + 44);
            ctx.font = '400 21px "Plus Jakarta Sans", Arial, sans-serif';
            ctx.fillText('membuka formulir pencatatan kilometer & perlintasan gerbang.', infoX + 98, curY + 76);

            // ---- 7. URL Footer (Pas & Rapi Menutup Kartu) ----
            curY = curY + banH + 34;
            ctx.textAlign = 'center';
            ctx.fillStyle = '#94a3b8';
            ctx.font = '400 21px monospace';
            ctx.fillText(urlText, headerCenterX, curY);

            // ---- EXPORT PNG ----
            const pngUrl = canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.href = pngUrl;
            link.download = 'Stiker_QR_' + code + '.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Kembalikan tombol ke keadaan semula
            if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = originalBtnHtml; }
        };

        qrImg.onerror = function() {
            alert('Gagal memuat gambar QR Code. Silakan coba lagi.');
            if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = originalBtnHtml; }
        };

        qrImg.src = svgDataUri;
    }

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

    // Dropdown Menu Ekspor LV
    function toggleLvExportMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('exportMenuLv');
        if (menu) {
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }
    }

    document.addEventListener('click', function(event) {
        const container = document.getElementById('exportLvDropdownContainer');
        const menu = document.getElementById('exportMenuLv');
        if (container && !container.contains(event.target)) {
            if (menu) {
                menu.style.display = 'none';
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeQrModal();
            closeGuideModal();
            const exportMenu = document.getElementById('exportMenuLv');
            if (exportMenu) exportMenu.style.display = 'none';
        }
    });
</script>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (LIGHT VEHICLE)                              -->
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
                    <p class="text-xs text-slate-500">Master Data Light Vehicle (LV)</p>
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
                Selamat datang di menu <strong>Light Vehicle</strong>! Halaman ini adalah pusat tata kelola data armada kendaraan ringan dinas PT Kerinci Merangin Hidro. Di halaman ini Anda dapat:
            </p>

            <div class="space-y-3">
                <!-- Fitur 1 (Hijau) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-emerald-200/50">
                        <i class="fa-solid fa-car-side"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Pendaftaran & Master Unit LV</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Mendaftarkan armada baru dengan menetapkan kode lambung LV, plat nomor, merek/model, tipe transmisi, dan tahun perakitan.
                        </p>
                    </div>
                </div>

                <!-- Fitur 2 (Biru) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-blue-200/50">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Alokasi Driver & Departemen</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Menghubungkan unit kendaraan dengan pengemudi penanggung jawab tetap serta divisi/departemen operasional pemilik unit.
                        </p>
                    </div>
                </div>

                <!-- Fitur 3 (Amber) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-amber-200/50">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Pembaruan & Status Kesiapan</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Gunakan tombol aksi pada tabel untuk memperbarui data teknis, mengganti penanggung jawab, atau menonaktifkan unit yang telah ditarik.
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
    /* Hilangkan scrollbar default pada modal */
    .no-scrollbar {
        -ms-overflow-style: none; /* IE and Edge */
        scrollbar-width: none; /* Firefox */
    }
    .no-scrollbar::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
        width: 0px;
        height: 0px;
    }

    /* Format Cetak Stiker Fisik Unit */
    @media print {
        body * {
            visibility: hidden !important;
        }
        #printableStickerArea, #printableStickerArea * {
            visibility: visible !important;
        }
        #printableStickerArea {
            position: fixed !important;
            left: 50% !important;
            top: 50% !important;
            transform: translate(-50%, -50%) !important;
            width: 320px !important;
            max-width: 90% !important;
            box-shadow: none !important;
            border: 2px solid #0f172a !important;
            background: #ffffff !important;
            margin: 0 !important;
            padding: 20px !important;
            border-radius: 1.5rem !important;
            z-index: 99999 !important;
        }
    }
</style>
@endsection
