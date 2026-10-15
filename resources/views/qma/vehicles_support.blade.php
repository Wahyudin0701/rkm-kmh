@extends('layouts.app')

@section('title', 'Unit Support - Admin QMA | PT Kerinci Merangin Hidro')

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
                        title="Panduan Halaman Unit Support">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                Unit Support
            </h2>
        </div>

        <!-- Tombol Aksi Kanan -->
        <div class="grid grid-cols-2 gap-2.5 w-full sm:flex sm:items-center sm:gap-3 sm:w-auto">
            <button type="button" 
                    onclick="alert('Fitur ekspor spreadsheet Unit Support sedang disiapkan')" 
                    class="w-full sm:w-auto flex items-center justify-center space-x-2 border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm">
                <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                <span class="truncate">Ekspor Data</span>
                <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
            </button>

            @can('qma.vehicles.create')
            <button onclick="openAddModal()" 
                    class="w-full sm:w-auto flex items-center justify-center space-x-2 bg-[#1b5e20] hover:bg-[#144919] text-white px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span class="truncate">Tambah Kategori</span>
            </button>
            @endcan
        </div>
    </div>


    <!-- Kotak Pencarian & Filter Kategori Alat Berat -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <div class="relative w-full max-w-sm sm:max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" 
                   id="supportSearchInput"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari kode unit, model, tipe, lokasi, dokumen..." 
                   class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 shadow-sm transition">
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Filter Kategori Alat Berat -->
            <form method="GET" action="{{ route('qma.vehicles.support') }}" class="flex items-center space-x-2">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                <select name="unit_category" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20]">
                    <option value="">Semua Kategori Alat</option>
                    @foreach($availableCategories as $cat)
                        <option value="{{ $cat }}" {{ request('unit_category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </form>

            <div id="supportResetWrapper" class="flex items-center">
                @if(request('search') || request('unit_category'))
                    <a id="supportResetBtn" href="{{ route('qma.vehicles.support') }}" class="text-xs text-rose-600 hover:text-rose-800 font-semibold flex items-center space-x-1 whitespace-nowrap ml-1">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>Reset</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabel Data Kategori Unit Support (Header Navy Solid #06213f) -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4">KODE KATEGORI</th>
                        <th class="py-3.5 px-4">KATEGORI ALAT</th>
                        <th class="py-3.5 px-4 text-center">JUMLAH (QTY)</th>
                        <th class="py-3.5 px-4">MEREK & MODEL</th>
                        <th class="py-3.5 px-4">SPESIFIKASI</th>
                        <th class="py-3.5 px-4">DIVISI & KEPEMILIKAN</th>
                        <th class="py-3.5 px-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody id="supportTableBody" class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($vehicles as $index => $v)
                        <tr class="support-row hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                {{ $vehicles->firstItem() + $index }}
                            </td>

                            <td class="py-3.5 px-4 font-bold text-slate-900 font-mono text-xs whitespace-nowrap">
                                <span class="bg-blue-50 text-blue-800 px-2.5 py-1 rounded border border-blue-200">
                                    {{ $v->code }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-bold text-slate-900 block text-xs">{{ $v->unit_category ?? 'Alat Berat' }}</span>
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <a href="{{ route('qma.vehicles.support.units', $v->id) }}" 
                                   class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-[#e8f8f0] text-[#00897b] border border-[#a7f3d0] hover:bg-[#d1fae5] transition shadow-2xs group cursor-pointer"
                                   title="Buka daftar {{ $v->units_count ?? 0 }} unit fisik">
                                    <span>{{ $v->units_count ?? 0 }} Unit</span>
                                    <i class="fa-solid fa-chevron-right text-[10px] ml-1 transition group-hover:translate-x-0.5"></i>
                                </a>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $v->brand_type }}</div>
                                @if($v->model_number)
                                    <div class="text-[10px] text-slate-500 font-mono mt-0.5">Model: {{ $v->model_number }}</div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-slate-700">
                                @if($v->unit_type)
                                    <span class="font-medium text-xs">{{ $v->unit_type }}</span>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-slate-600">
                                <div class="font-medium text-slate-800">{{ $v->division ?? '-' }}</div>
                                <span class="text-[10px] text-slate-400 block">{{ $v->ownership ?? 'PT Kerinci Merangin Hidro' }}</span>
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <button onclick='openDetailModal(@json($v))' 
                                            class="inline-flex items-center space-x-1 text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-2 py-1 rounded-lg transition"
                                            title="Lihat Spesifikasi Kategori">
                                        <i class="fa-regular fa-eye text-sm"></i>
                                    </button>
                                    @can('qma.vehicles.edit')
                                    <button onclick='openEditModal(@json($v))' 
                                            class="inline-flex items-center space-x-1 text-amber-600 hover:text-amber-800 hover:bg-amber-50 px-2 py-1 rounded-lg transition"
                                            title="Edit Data Kategori">
                                        <i class="fa-regular fa-pen-to-square text-sm"></i>
                                    </button>
                                    @endcan
                                    @can('qma.vehicles.delete')
                                    <button type="button" 
                                            onclick="openDeleteModal('{{ $v->id }}', '{{ $v->code }}', '{{ addslashes($v->brand_type ?? '') }}')"
                                            class="inline-flex items-center space-x-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-2 py-1 rounded-lg transition"
                                            title="Hapus Kategori / Unit">
                                        <i class="fa-regular fa-trash-can text-sm"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="serverSupportEmptyRow">
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
                                @if(request('search'))
                                    <p class="text-sm font-semibold text-slate-700">
                                        Data "<span class="text-rose-600 font-bold">{{ request('search') }}</span>" tidak ditemukan di tabel
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        Pastikan kata kunci yang dicari sudah benar.
                                    </p>
                                    <div class="mt-4">
                                        <a href="{{ route('qma.vehicles.support') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                            <span>Hapus Pencarian</span>
                                        </a>
                                    </div>
                                @else
                                    <p class="text-sm font-semibold text-slate-700">Belum ada unit alat berat</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Belum ada unit alat berat yang sesuai dengan filter.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="supportPaginationContainer" class="p-4 bg-slate-50 border-t border-slate-200">
            {{ $vehicles->links() }}
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: TAMBAH UNIT SUPPORT (KENDARAAN BERAT) -->
<!-- ========================================================================= -->
<div id="addVehicleModal" 
     onclick="if(event.target === this) closeAddModal()"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full p-6 sm:p-7 border border-slate-100 max-h-[92vh] overflow-y-auto no-scrollbar">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                    Tambah Kategori Unit Support
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Mendaftarkan kategori/tipe master aset baru ke sistem.</p>
            </div>
            <button type="button" onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="addVehicleForm" action="{{ route('qma.vehicles.store') }}" method="POST" class="space-y-3.5">
            @csrf
            <input type="hidden" name="category" value="unit_support">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Kode Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" id="addCodeInput" required placeholder="MISAL: EX01, CR01, BL01" value="{{ old('code') }}"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border @error('code') border-rose-500 @else border-slate-200 @enderror bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono uppercase font-bold">
                    
                    <p id="codeError" class="text-xs text-rose-600 font-semibold mt-1 flex items-center space-x-1.5 @if(!$errors->has('code')) hidden @endif">
                        <i class="fa-solid fa-circle-exclamation text-rose-500 text-xs"></i>
                        <span id="codeErrorText">{{ $errors->first('code') ?? 'Kode unit sudah ada, silakan gunakan kode lain.' }}</span>
                    </p>
                    <p id="codeHint" class="text-[11px] text-slate-400 mt-0.5 @if($errors->has('code')) hidden @endif">
                        * Digunakan sebagai dasar kode unit fisik (contoh: BL01-001).
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Kategori Alat Berat <span class="text-rose-500">*</span></label>
                    <input type="text" name="unit_category" required placeholder="Contoh: Excavator / Mobile Crane / Boomlift" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-medium">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Merek & Tipe Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="brand_type" required placeholder="Contoh: KOMATSU PC200-8 / KATO SR-250R" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Tipe / Model Khusus</label>
                    <input type="text" name="unit_type" placeholder="Contoh: Hydraulic Excavator / Rough Terrain" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Nomor Model</label>
                    <input type="text" name="model_number" placeholder="Contoh: PC200-8M0" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Estimasi Konsumsi BBM (Liter/Jam)</label>
                    <input type="number" step="0.01" name="fuel_consumption" placeholder="Contoh: 18.5" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                    <label class="block text-xs font-bold text-slate-800 mb-1">Kepemilikan Aset</label>
                    <input type="text" name="ownership" placeholder="Contoh: PT Kerinci Merangin Hidro" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <!-- Footer Tombol -->
            <div class="pt-3 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeAddModal()" 
                        class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs transition shadow-sm">
                    Simpan Kategori
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT KATEGORI UNIT SUPPORT -->
<!-- ========================================================================= -->
<div id="editVehicleModal" 
     onclick="if(event.target === this) closeEditModal()"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full p-6 sm:p-7 border border-slate-100 max-h-[92vh] overflow-y-auto no-scrollbar">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                    Edit Kategori Unit Support
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Memperbarui informasi master kategori aset.</p>
            </div>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="editVehicleForm" action="" method="POST" class="space-y-3.5">
            @csrf
            <input type="hidden" name="category" value="unit_support">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kode Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" id="editCode" required 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono uppercase font-bold">
                    
                    <p id="editCodeError" class="text-xs text-rose-600 font-semibold mt-1.5 hidden flex items-center">
                        <i class="fa-solid fa-circle-exclamation mr-1.5 flex-shrink-0"></i>
                        <span id="editCodeErrorText">Kode unit sudah ada, silakan gunakan kode lain.</span>
                    </p>
                    <p id="editCodeHint" class="text-[11px] text-slate-400 mt-1">* Jika diubah, kode seluruh unit fisik di bawahnya akan otomatis menyesuaikan.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kategori Alat Berat <span class="text-rose-500">*</span></label>
                    <input type="text" name="unit_category" id="editUnitCategory" required 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-medium">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Merek & Tipe Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="brand_type" id="editBrandType" required 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Tipe / Model Khusus</label>
                    <input type="text" name="unit_type" id="editUnitType" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Nomor Model</label>
                    <input type="text" name="model_number" id="editModelNumber" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Konsumsi BBM Standar (L/Jam)</label>
                    <input type="number" step="0.01" name="fuel_consumption" id="editFuelConsumption" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Divisi Penanggung Jawab</label>
                    <select name="division" id="editDivision" 
                            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-medium">
                        <option value="">-- Pilih Divisi Penanggung Jawab --</option>
                        @foreach($availableDivisions as $div)
                            <option value="{{ $div }}">{{ $div }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kepemilikan</label>
                    <input type="text" name="ownership" id="editOwnership" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <!-- Footer Tombol -->
            <div class="pt-4 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeEditModal()" 
                        class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs transition shadow-sm">
                    Perbarui Kategori
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: DETAIL SPESIFIKASI KATEGORI UNIT SUPPORT -->
<!-- ========================================================================= -->
<div id="detailVehicleModal" onclick="if(event.target === this) closeDetailModal()" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full p-6 sm:p-7 border border-slate-100 max-h-[92vh] overflow-y-auto no-scrollbar">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                    Detail Spesifikasi Kategori
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Informasi master kategori aset Unit Support.</p>
            </div>
            <button type="button" onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kode Kategori</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold flex items-center">
                        <span id="detailCode" class="bg-blue-50 text-blue-800 px-2.5 py-0.5 rounded border border-blue-200">-</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kategori Alat</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-bold flex items-center">
                        <span id="detailCategory">-</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Merek & Tipe Lengkap</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-bold flex items-center">
                        <span id="detailBrand">-</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Tipe / Model Khusus</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-medium flex items-center">
                        <span id="detailUnitType">-</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Nomor Model</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono flex items-center">
                        <span id="detailModelNumber">-</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Konsumsi BBM Standar</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-medium flex items-center">
                        <span id="detailFuel">-</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Divisi Penanggung Jawab</label>
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

            <!-- Card Unit Fisik Terdaftar -->
            <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-emerald-950 block">Unit Fisik Terdaftar</span>
                    <span id="detailUnitCountText" class="text-xs text-emerald-800 font-medium">0 Unit Fisik</span>
                </div>
                <a id="detailManageUnitsBtn" href="#" class="px-4 py-2 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white text-xs font-bold transition shadow-sm inline-flex items-center justify-center">
                    <span>Kelola Unit Fisik</span>
                </a>
            </div>

            <!-- Footer Tombol Tutup -->
            <div class="pt-2 flex items-center justify-end">
                <button type="button" onclick="closeDetailModal()" 
                        class="px-6 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs transition shadow-sm cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: KONFIRMASI HAPUS UNIT SUPPORT (KENDARAAN BERAT) -->
<!-- ========================================================================= -->
<div id="deleteVehicleModal" onclick="if(event.target === this) closeDeleteModal()" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
    <div class="bg-white rounded-3xl shadow-2xl max-w-sm sm:max-w-md w-full p-6 sm:p-7 border border-slate-100 transform transition-all text-center">
        <!-- Icon Peringatan Elegan -->
        <div class="w-14 h-14 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4 text-2xl shadow-sm">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h3 class="text-lg font-bold text-slate-900 tracking-tight">
            Konfirmasi Hapus Unit Support
        </h3>

        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
            Apakah Anda yakin ingin menghapus data unit support <span id="deleteVehicleCode" class="font-bold font-mono text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200"></span>?
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
        const tableBody = document.getElementById('supportTableBody');
        const resetWrapper = document.getElementById('supportResetWrapper');
        const paginationContainer = document.getElementById('supportPaginationContainer');
        const serverEmptyRow = document.getElementById('serverSupportEmptyRow');
        const hasServerFilters = {{ request()->anyFilled(['status', 'unit_category']) ? 'true' : 'false' }};

        if (searchInput && tableBody) {
            searchInput.addEventListener('input', function (e) {
                const rawValue = e.target.value;
                const query = rawValue.toLowerCase().trim();
                const rows = tableBody.querySelectorAll('tr.support-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (paginationContainer) {
                    paginationContainer.style.display = query.length > 0 ? 'none' : '';
                }

                const existingEmptyRow = document.getElementById('clientSupportEmptyRow');
                if (existingEmptyRow) existingEmptyRow.remove();

                if (serverEmptyRow) {
                    serverEmptyRow.style.display = query.length > 0 ? 'none' : '';
                }

                if (visibleCount === 0 && query.length > 0) {
                    const emptyTr = document.createElement('tr');
                    emptyTr.id = 'clientSupportEmptyRow';
                    emptyTr.innerHTML = `
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
                            <p class="text-sm font-semibold text-slate-700">
                                Data "<span class="text-rose-600 font-bold">${escapeHtml(rawValue)}</span>" tidak ditemukan di tabel
                            </p>
                            <p class="text-xs text-slate-400 mt-1">
                                Pastikan kata kunci yang dicari sudah benar.
                            </p>
                            <div class="mt-4">
                                <button type="button" 
                                        onclick="const inp = document.getElementById('supportSearchInput'); if(inp){ inp.value=''; inp.dispatchEvent(new Event('input')); inp.focus(); }" 
                                        class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                    <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                    <span>Hapus Pencarian</span>
                                </button>
                            </div>
                        </td>
                    `;
                    tableBody.appendChild(emptyTr);
                }

                if (resetWrapper) {
                    let resetBtn = document.getElementById('supportResetBtn');
                    if (query.length > 0 || hasServerFilters) {
                        if (!resetBtn) {
                            resetBtn = document.createElement('a');
                            resetBtn.id = 'supportResetBtn';
                            resetBtn.href = "{{ route('qma.vehicles.support') }}";
                            resetBtn.className = 'text-xs text-rose-600 hover:text-rose-800 font-semibold flex items-center space-x-1 whitespace-nowrap ml-1';
                            resetBtn.innerHTML = '<i class="fa-solid fa-rotate-left"></i><span>Reset</span>';
                            resetWrapper.appendChild(resetBtn);
                        }
                    } else if (!hasServerFilters && resetBtn) {
                        resetBtn.remove();
                    }
                }
            });
        }
    });

    // Daftar semua kode kendaraan unit support yang sudah terdaftar di sistem
    const existingVehicleCodes = @json($existingCodes ?? \App\Models\Vehicle::unitSupports()->pluck('code')->map(fn($c) => strtoupper(trim($c))));

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
        });
    }

    // Elemen modal Edit Unit Support
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

    function openAddModal() {
        document.getElementById('addVehicleModal').classList.remove('hidden');
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
        document.getElementById('editUnitCategory').value = v.unit_category || '';
        document.getElementById('editBrandType').value = v.brand_type || '';
        document.getElementById('editUnitType').value = v.unit_type || '';
        document.getElementById('editModelNumber').value = v.model_number || '';
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
        document.getElementById('editOwnership').value = v.ownership || '';
        document.getElementById('editFuelConsumption').value = v.fuel_consumption || '';
    }
    function closeEditModal() {
        document.getElementById('editVehicleModal').classList.add('hidden');
        hideEditCodeError();
    }

    function openDetailModal(v) {
        document.getElementById('detailVehicleModal').classList.remove('hidden');
        document.getElementById('detailCode').innerText = v.code || '-';
        document.getElementById('detailCategory').innerText = v.unit_category || '-';
        document.getElementById('detailBrand').innerText = v.brand_type || '-';
        document.getElementById('detailUnitType').innerText = v.unit_type || '-';
        document.getElementById('detailModelNumber').innerText = v.model_number || '-';
        document.getElementById('detailFuel').innerText = v.fuel_consumption ? `${v.fuel_consumption} L/Jam` : '-';
        document.getElementById('detailDivision').innerText = v.division || '-';
        document.getElementById('detailOwnership').innerText = v.ownership || 'PT Kerinci Merangin Hidro';

        const count = v.units_count !== undefined ? v.units_count : 0;
        document.getElementById('detailUnitCountText').innerText = count + ' Unit Fisik';
        const manageBtn = document.getElementById('detailManageUnitsBtn');
        if (manageBtn) {
            manageBtn.href = `/qma/vehicles/unit-support/${v.id}/units`;
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

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGuideModal();
        }
    });
</script>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (UNIT SUPPORT)                               -->
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
                    <p class="text-xs text-slate-500">Master Data Unit Support</p>
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
                Selamat datang di menu <strong>Unit Support</strong>! Halaman ini adalah pusat pengelolaan master armada alat berat dan kendaraan pendukung operasional PT Kerinci Merangin Hidro. Di halaman ini Anda dapat:
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
