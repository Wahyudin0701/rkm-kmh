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
        <div class="flex items-center space-x-3 w-full sm:w-auto">
            <button type="button" 
                    onclick="alert('Fitur ekspor spreadsheet Unit Support sedang disiapkan')" 
                    class="border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-4 py-2.5 rounded-xl text-xs font-bold flex items-center space-x-2 transition shadow-sm">
                <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                <span>Ekspor Unit Support</span>
                <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
            </button>

            @can('qma.vehicles.create')
            <button onclick="openAddModal()" 
                    class="bg-[#1b5e20] hover:bg-[#144919] text-white px-4 py-2.5 rounded-xl text-xs font-bold flex items-center space-x-2 transition shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah Unit Support</span>
            </button>
            @endcan
        </div>
    </div>


    <!-- Kotak Pencarian & Filter Kategori Alat Berat -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <form method="GET" action="{{ route('qma.vehicles.support') }}" class="relative w-full max-w-sm sm:max-w-md">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            @if(request('unit_category'))
                <input type="hidden" name="unit_category" value="{{ request('unit_category') }}">
            @endif
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" 
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari kode unit, model, tipe, lokasi, dokumen..." 
                   class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 shadow-sm transition">
        </form>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Filter Kategori Alat Berat -->
            <form method="GET" action="{{ route('qma.vehicles.support') }}" class="flex items-center space-x-2">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <select name="unit_category" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20]">
                    <option value="">Semua Kategori Alat</option>
                    @foreach($availableCategories as $cat)
                        <option value="{{ $cat }}" {{ request('unit_category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </form>

            <!-- Filter Status Operasional -->
            <form method="GET" action="{{ route('qma.vehicles.support') }}" class="flex items-center space-x-2">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                @if(request('unit_category'))
                    <input type="hidden" name="unit_category" value="{{ request('unit_category') }}">
                @endif
                <select name="status" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20]">
                    <option value="">Semua Status Operasi</option>
                    <option value="Operation" {{ request('status') === 'Operation' ? 'selected' : '' }}>Operation (Siap Kerja)</option>
                    <option value="Standby" {{ request('status') === 'Standby' ? 'selected' : '' }}>Standby</option>
                    <option value="Breakdown" {{ request('status') === 'Breakdown' ? 'selected' : '' }}>Breakdown</option>
                    <option value="Maintenance" {{ request('status') === 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
            </form>

            @if(request('search') || request('status') || request('unit_category'))
                <a href="{{ route('qma.vehicles.support') }}" class="text-xs text-rose-600 hover:text-rose-800 font-semibold flex items-center space-x-1 whitespace-nowrap ml-1">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Reset</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Tabel Data Unit Support (Header Navy Solid #06213f) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4">KODE UNIT</th>
                        <th class="py-3.5 px-4">KATEGORI & MODEL</th>
                        <th class="py-3.5 px-4">MEREK & SPESIFIKASI</th>
                        <th class="py-3.5 px-4">LOKASI KERJA</th>
                        <th class="py-3.5 px-4 text-right">ODOMETER (HM/KM)</th>
                        <th class="py-3.5 px-4 text-center">DOKUMEN IZIN</th>
                        <th class="py-3.5 px-4 text-center">STATUS</th>
                        <th class="py-3.5 px-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($vehicles as $index => $v)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                {{ $vehicles->firstItem() + $index }}
                            </td>

                            <td class="py-3.5 px-4 font-bold text-slate-900 font-mono text-xs whitespace-nowrap">
                                <span class="bg-blue-50 text-blue-800 px-2 py-0.5 rounded border border-blue-200">
                                    {{ $v->code }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-bold text-slate-900 block">{{ $v->unit_category ?? 'Alat Berat' }}</span>
                                <span class="text-[10px] text-slate-500 font-mono">{{ $v->model_number ? "Model: {$v->model_number}" : '-' }}</span>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $v->brand_type }}</div>
                                <div class="flex items-center space-x-2 mt-0.5 text-[10px] text-slate-500">
                                    @if($v->unit_type)
                                        <span>{{ $v->unit_type }}</span>
                                    @endif
                                    @if($v->production_year)
                                        <span>&bull; Thn {{ $v->production_year }}</span>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-slate-600">
                                <div class="font-medium text-slate-800">
                                    @if($v->location)
                                        <i class="fa-solid fa-location-dot text-[9px] mr-1 text-rose-500"></i>{{ $v->location }}
                                    @else
                                        -
                                    @endif
                                </div>
                                <span class="text-[10px] text-slate-400 block">{{ $v->division ?? '-' }}</span>
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-800 whitespace-nowrap">
                                {{ number_format($v->current_km, 0, ',', '.') }}
                                <span class="text-[10px] text-slate-400 font-sans font-normal">HM/KM</span>
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="flex flex-col items-center space-y-1">
                                    @if($v->silo_document)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200" title="SILO: {{ $v->silo_document }}">
                                            <i class="fa-solid fa-certificate mr-1 text-[9px] text-emerald-600"></i> SILO Ada
                                        </span>
                                    @else
                                        <span class="text-[10px] text-slate-400 italic">Tanpa SILO</span>
                                    @endif

                                    @if($v->kir_document)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-mono bg-slate-100 text-slate-600 border border-slate-200">
                                            KIR: {{ $v->kir_document }}
                                        </span>
                                    @endif
                                </div>
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
                                        <i class="fa-solid fa-screwdriver-wrench mr-1 text-[9px]"></i> Breakdown
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
                                            title="Lihat Detail & Perizinan">
                                        <i class="fa-regular fa-eye text-sm"></i>
                                    </button>
                                    @can('qma.vehicles.edit')
                                    <button onclick='openEditModal(@json($v))' 
                                            class="inline-flex items-center space-x-1 text-amber-600 hover:text-amber-800 hover:bg-amber-50 px-2 py-1 rounded-lg transition"
                                            title="Edit Data Unit Support">
                                        <i class="fa-regular fa-pen-to-square text-sm"></i>
                                    </button>
                                    @endcan
                                    @can('qma.vehicles.delete')
                                    <button type="button" 
                                            onclick="openDeleteModal('{{ $v->id }}', '{{ $v->code }}', '{{ addslashes($v->brand_type ?? '') }}')"
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
                                <i class="fa-solid fa-tractor text-3xl mb-2 block text-slate-300"></i>
                                Belum ada unit alat berat yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 bg-slate-50 border-t border-slate-200">
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
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 sm:p-7 border border-slate-100 max-h-[92vh] overflow-y-auto no-scrollbar">
        <!-- Header Modal Bersih Tanpa Icon Box Sesuai Referensi Tambah Aset -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                Tambah Unit Support
            </h3>
            <button type="button" onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="addVehicleForm" action="{{ route('qma.vehicles.store') }}" method="POST" class="space-y-3">
            @csrf
            <input type="hidden" name="category" value="unit_support">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Kode Unit <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" id="addCodeInput" required placeholder="MISAL: EX01, CR01, DT05" value="{{ old('code') }}"
                           class="w-full text-xs px-3.5 py-2 rounded-xl border @error('code') border-rose-500 @else border-slate-200 @enderror bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono uppercase">
                    
                    <p id="codeError" class="text-xs text-rose-600 font-semibold mt-1 flex items-center space-x-1.5 @if(!$errors->has('code')) hidden @endif">
                        <i class="fa-solid fa-circle-exclamation text-rose-500 text-xs"></i>
                        <span id="codeErrorText">{{ $errors->first('code') ?? 'Kode unit sudah ada, silakan gunakan kode lain.' }}</span>
                    </p>
                    <p id="codeHint" class="text-[11px] text-slate-400 mt-0.5 @if($errors->has('code')) hidden @endif">
                        * Kode unit unik dan tidak boleh sama dengan kode aset yang lain.
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Kategori Alat Berat <span class="text-rose-500">*</span></label>
                    <input type="text" name="unit_category" required placeholder="Contoh: Excavator / Mobile Crane / Dump Truck" 
                           class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Merek & Tipe Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="brand_type" required placeholder="Contoh: KOMATSU PC200-8 / KATO SR-250R" 
                           class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Tipe / Model Khusus</label>
                    <input type="text" name="unit_type" placeholder="Contoh: Hydraulic Excavator / Rough Terrain" 
                           class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Nomor Model</label>
                    <input type="text" name="model_number" placeholder="Contoh: PC200-8M0" 
                           class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Tahun Produksi</label>
                    <input type="number" name="production_year" placeholder="Contoh: 2021" 
                           class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Nopol (Jika ada)</label>
                    <input type="text" name="license_plate" placeholder="Contoh: BH 8412 AA" 
                           class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition uppercase font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Lokasi Kerja Lapangan</label>
                    <input type="text" name="location" placeholder="Contoh: Beringin Tunggal / CCR DAM / Tunnel" 
                           class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Divisi Penanggung Jawab</label>
                    <input type="text" name="division" placeholder="Contoh: Civil & Inviro / Mechanical" 
                           class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Kepemilikan Aset</label>
                    <input type="text" name="ownership" placeholder="Contoh: PT Kerinci Merangin Hidro" 
                           class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Status Operasi <span class="text-rose-500">*</span></label>
                    <select name="status" required class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        <option value="Operation">Operation (Siap Kerja)</option>
                        <option value="Standby">Standby</option>
                        <option value="Breakdown">Breakdown</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">Odometer Awal (HM/KM) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" name="current_km" required value="0" 
                           class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
            </div>

            <!-- Bagian Perizinan & Dokumen -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
                <div class="flex items-center space-x-2 text-slate-800 font-bold text-xs">
                    <i class="fa-solid fa-file-contract text-emerald-600"></i>
                    <span>Dokumen Legalitas & Perizinan Kelayakan</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Nomor SILO (Surat Izin Layak Operasi)</label>
                        <input type="text" name="silo_document" placeholder="Contoh: SILO-KMH-2024/091" 
                               class="w-full text-xs px-3 py-1.5 rounded-xl border border-slate-200 bg-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Nomor Dokumen KIR</label>
                        <input type="text" name="kir_document" placeholder="Contoh: KIR-2026/08" 
                               class="w-full text-xs px-3 py-1.5 rounded-xl border border-slate-200 bg-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Nomor STNK</label>
                        <input type="text" name="stnk_document" placeholder="Contoh: STNK-BH-EX01" 
                               class="w-full text-xs px-3 py-1.5 rounded-xl border border-slate-200 bg-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Konsumsi BBM (Liter/Jam)</label>
                        <input type="number" step="0.01" name="fuel_consumption" placeholder="Contoh: 18.5" 
                               class="w-full text-xs px-3 py-1.5 rounded-xl border border-slate-200 bg-white">
                    </div>
                </div>
            </div>

            <!-- Footer Tombol Bersih Sesuai Referensi Tambah Aset -->
            <div class="pt-3 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeAddModal()" 
                        class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs transition shadow-sm">
                    Simpan Unit Support
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT UNIT SUPPORT -->
<!-- ========================================================================= -->
<div id="editVehicleModal" 
     onclick="if(event.target === this) closeEditModal()"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 sm:p-7 border border-slate-100 max-h-[92vh] overflow-y-auto no-scrollbar">
        <!-- Header Modal Bersih Tanpa Icon Box Sesuai Referensi Tambah Aset -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                Edit Unit Support
            </h3>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="editVehicleForm" action="" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="category" value="unit_support">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kode Unit <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" id="editCode" required 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono uppercase">
                    
                    <p id="editCodeError" class="text-xs text-rose-600 font-semibold mt-1.5 hidden flex items-center">
                        <i class="fa-solid fa-circle-exclamation mr-1.5 flex-shrink-0"></i>
                        <span id="editCodeErrorText">Kode unit sudah ada, silakan gunakan kode lain.</span>
                    </p>
                    <p id="editCodeHint" class="text-[11px] text-slate-400 mt-1">* Kode unit unik dan tidak boleh sama dengan kode aset yang lain.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kategori Alat Berat <span class="text-rose-500">*</span></label>
                    <input type="text" name="unit_category" id="editUnitCategory" required 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
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

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Nomor Model</label>
                    <input type="text" name="model_number" id="editModelNumber" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Tahun Produksi</label>
                    <input type="number" name="production_year" id="editProductionYear" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Nopol (Jika ada)</label>
                    <input type="text" name="license_plate" id="editLicensePlate" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition uppercase font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Lokasi Kerja</label>
                    <input type="text" name="location" id="editLocation" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Divisi</label>
                    <input type="text" name="division" id="editDivision" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kepemilikan</label>
                    <input type="text" name="ownership" id="editOwnership" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Status Operasi <span class="text-rose-500">*</span></label>
                    <select name="status" id="editStatus" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        <option value="Operation">Operation (Siap Kerja)</option>
                        <option value="Standby">Standby</option>
                        <option value="Breakdown">Breakdown</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Odometer HM/KM <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" name="current_km" id="editCurrentKm" required 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-mono">
                </div>
            </div>

            <!-- Bagian Perizinan di Edit -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                <div class="flex items-center space-x-2 text-slate-800 font-bold text-xs">
                    <i class="fa-solid fa-file-contract text-emerald-600"></i>
                    <span>Dokumen Legalitas & Perizinan</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Nomor Dokumen SILO</label>
                        <input type="text" name="silo_document" id="editSiloDocument" 
                               class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Nomor Dokumen KIR</label>
                        <input type="text" name="kir_document" id="editKirDocument" 
                               class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Nomor STNK</label>
                        <input type="text" name="stnk_document" id="editStnkDocument" 
                               class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Konsumsi BBM (L/Jam)</label>
                        <input type="number" step="0.01" name="fuel_consumption" id="editFuelConsumption" 
                               class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                    </div>
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
<!-- MODAL: DETAIL UNIT SUPPORT -->
<!-- ========================================================================= -->
<div id="detailVehicleModal" onclick="if(event.target === this) closeDetailModal()" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 sm:p-7 border border-slate-100 max-h-[92vh] overflow-y-auto no-scrollbar">
        <!-- Header Modal Bersih Tanpa Icon Box Sesuai Referensi Card Tambah Data -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                Detail Unit Support
            </h3>
            <button type="button" onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kode Unit</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold flex items-center">
                        <span id="detailCode" class="bg-blue-50 text-blue-800 px-2 py-0.5 rounded border border-blue-200">-</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kategori & Model</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-bold flex items-center">
                        <span id="detailCategoryModel">-</span>
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
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Nomor Polisi (Nopol)</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold flex items-center uppercase">
                        <span id="detailNopol">-</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Tahun Produksi</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-medium flex items-center">
                        <span id="detailYear">-</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Lokasi Kerja</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-medium flex items-center">
                        <span id="detailLocation">-</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Konsumsi BBM</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-medium flex items-center">
                        <span id="detailFuel">-</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Divisi & Kepemilikan</label>
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-medium flex items-center">
                        <span id="detailOwnerDiv">-</span>
                    </div>
                </div>
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
                    <div class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-blue-700 font-mono font-bold flex items-center">
                        <span id="detailKm">0 HM/KM</span>
                    </div>
                </div>
            </div>

            <!-- Bagian Legalitas & Perizinan -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                <div class="flex items-center space-x-2 text-slate-800 font-bold text-xs">
                    <i class="fa-solid fa-file-contract text-emerald-600"></i>
                    <span>Dokumen Legalitas & Perizinan Kelayakan</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <span class="text-[11px] font-bold text-slate-700 block mb-1">Nomor Dokumen SILO</span>
                        <div class="text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white font-mono font-semibold text-slate-800">
                            <span id="detailSilo">-</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-slate-700 block mb-1">Nomor Dokumen KIR</span>
                        <div class="text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white font-mono font-semibold text-slate-800">
                            <span id="detailKir">-</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-slate-700 block mb-1">Nomor STNK</span>
                        <div class="text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white font-mono font-semibold text-slate-800">
                            <span id="detailStnk">-</span>
                        </div>
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
        document.getElementById('editProductionYear').value = v.production_year || '';
        document.getElementById('editLicensePlate').value = v.license_plate || '';
        document.getElementById('editLocation').value = v.location || '';
        document.getElementById('editDivision').value = v.division || '';
        document.getElementById('editOwnership').value = v.ownership || '';
        document.getElementById('editStatus').value = v.status || 'Operation';
        document.getElementById('editCurrentKm').value = v.current_km || 0;
        document.getElementById('editSiloDocument').value = v.silo_document || '';
        document.getElementById('editKirDocument').value = v.kir_document || '';
        document.getElementById('editStnkDocument').value = v.stnk_document || '';
        document.getElementById('editFuelConsumption').value = v.fuel_consumption || '';
    }
    function closeEditModal() {
        document.getElementById('editVehicleModal').classList.add('hidden');
        hideEditCodeError();
    }

    function openDetailModal(v) {
        document.getElementById('detailVehicleModal').classList.remove('hidden');
        document.getElementById('detailCode').innerText = v.code || '-';
        document.getElementById('detailBrand').innerText = v.brand_type || '-';
        document.getElementById('detailCategoryModel').innerText = (v.unit_category || '-') + (v.model_number ? ` (${v.model_number})` : '');
        document.getElementById('detailStatus').innerText = v.status || '-';
        document.getElementById('detailLocation').innerText = v.location || '-';
        document.getElementById('detailKm').innerText = Number(v.current_km || 0).toLocaleString('id-ID') + ' HM/KM';
        document.getElementById('detailYear').innerText = v.production_year || '-';
        document.getElementById('detailFuel').innerText = v.fuel_consumption ? `${v.fuel_consumption} L/Jam` : '-';
        document.getElementById('detailOwnerDiv').innerText = (v.division || '-') + ' / ' + (v.ownership || '-');
        document.getElementById('detailSilo').innerText = v.silo_document || '-';
        document.getElementById('detailKir').innerText = v.kir_document || '-';
        document.getElementById('detailStnk').innerText = v.stnk_document || '-';
        document.getElementById('detailNopol').innerText = v.license_plate || '-';

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
