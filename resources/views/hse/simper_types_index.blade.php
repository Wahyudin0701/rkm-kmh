@extends('layouts.app')

@section('title', 'Master Jenis SIMPER - Admin HSE | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Judul & Tombol Aksi -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-1.5 mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                    MANAJEMEN HSE
                </span>
                <button type="button" 
                        onclick="openSimperTypeGuideModal()" 
                        class="w-3.5 h-3.5 rounded-full bg-slate-200/90 hover:bg-slate-300 text-slate-500 hover:text-slate-800 text-[8px] inline-flex items-center justify-center font-bold transition shadow-2xs cursor-pointer focus:outline-none" 
                        title="Panduan Jenis SIMPER">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                Jenis SIMPER
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Kelola kategori izin mengemudi (SIMPER) dan cakupan kendaraan yang diizinkan untuk personil.
            </p>
        </div>

        <div class="flex items-center gap-2.5 sm:gap-3 w-full sm:w-auto">
            <a href="{{ route('hse.employees.index') }}" 
               class="w-full sm:w-auto border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 px-3.5 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition shadow-xs">
                <i class="fa-solid fa-arrow-left text-slate-400"></i>
                <span>Kembali ke Data Personil</span>
            </a>

            @can('hse.employees.edit')
            <button type="button" 
                    onclick="openAddSimperTypeModal()" 
                    class="w-full sm:w-auto bg-[#1b5e20] hover:bg-[#144919] text-white px-4 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition shadow-sm cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah Jenis SIMPER</span>
            </button>
            @endcan
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if(session('success'))
    <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-2xs">
        <div class="flex items-center space-x-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 p-1">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center justify-between shadow-2xs">
        <div class="flex items-center space-x-2.5">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800 p-1">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>
    @endif

    @if($errors->any())
    <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center justify-between shadow-2xs">
        <div class="flex items-center space-x-2.5">
            <i class="fa-solid fa-circle-exclamation text-rose-600 text-base"></i>
            <span>{{ $errors->first() }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800 p-1">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>
    @endif

    <!-- Kartu KPI Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-[#1b5e20] flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Kategori</p>
                <p class="text-xl font-extrabold text-slate-900 mt-0.5">{{ count($simperTypes) }} Jenis</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-users-gear"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pemegang Aktif</p>
                <p class="text-xl font-extrabold text-slate-900 mt-0.5">
                    {{ $simperTypes->sum('active_holders_count') }} Personil
                </p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status Fleksibilitas</p>
                <p class="text-sm font-extrabold text-slate-800 mt-0.5">Siap Dikustomisasi HSE</p>
            </div>
        </div>
    </div>

    <!-- Tabel Data Master Jenis SIMPER -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">KODE</th>
                        <th class="py-3.5 px-4 whitespace-nowrap min-w-[200px]">NAMA JENIS SIMPER</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">CAKUPAN KENDARAAN</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">PEMEGANG AKTIF</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">STATUS</th>
                        <th class="py-3.5 px-4 whitespace-nowrap min-w-[220px]">KETERANGAN</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap w-24">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 bg-white font-medium">
                    @forelse($simperTypes as $idx => $st)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3 px-4 text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                        <td class="py-3 px-4">
                            <span class="font-mono font-extrabold text-xs text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                {{ $st->code }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-slate-900 text-xs">{{ $st->name }}</span>
                        </td>
                        <td class="py-3 px-4">
                            @if($st->category_scope === 'light_vehicle')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    <i class="fa-solid fa-car-side mr-1"></i> Light Vehicle (LV)
                                </span>
                            @elseif($st->category_scope === 'all')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                    <i class="fa-solid fa-shapes mr-1"></i> Semua Kategori
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    <i class="fa-solid fa-truck-moving mr-1"></i> {{ str_replace('_', ' ', $st->category_scope) }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center font-bold">
                            <span class="font-mono text-slate-800 bg-slate-100 px-2 py-0.5 rounded">
                                {{ $st->active_holders_count }} Personil
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($st->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                    Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-500 text-[11px] line-clamp-2">
                            {{ $st->description ?: '-' }}
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center space-x-1">
                                @can('hse.employees.edit')
                                <button type="button" 
                                        onclick="openEditSimperTypeModal({{ json_encode($st) }})" 
                                        class="p-1.5 rounded-lg text-amber-600 hover:text-amber-800 hover:bg-amber-50 transition cursor-pointer" 
                                        title="Edit Jenis SIMPER">
                                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                                </button>
                                @if($st->total_holders_count == 0)
                                <button type="button" 
                                        onclick="openDeleteSimperTypeModal({{ $st->id }}, '{{ $st->name }}')" 
                                        class="p-1.5 rounded-lg text-rose-600 hover:text-rose-800 hover:bg-rose-50 transition cursor-pointer" 
                                        title="Hapus Jenis SIMPER">
                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                </button>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-id-card text-3xl mb-2 block text-slate-300"></i>
                            <p class="text-sm font-semibold text-slate-700">Belum ada data jenis SIMPER</p>
                            <p class="text-xs text-slate-400 mt-1">Klik tombol "+ Tambah Jenis SIMPER" untuk membuat kategori baru.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: TAMBAH JENIS SIMPER                                                -->
<!-- ========================================================================= -->
<div id="addSimperTypeModal" onclick="if(event.target === this) closeAddSimperTypeModal()" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-slate-100 overflow-hidden animate-scale-in">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-id-card text-emerald-700"></i>
                    <span>Tambah Jenis SIMPER</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Definisikan kategori izin mengemudi dan cakupan unitnya.</p>
            </div>
            <button type="button" onclick="closeAddSimperTypeModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('hse.simper_types.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kode SIMPER <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" required placeholder="Contoh: SIMPER-CRANE" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-mono font-bold focus:outline-none focus:border-[#1b5e20] transition uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Cakupan Kendaraan <span class="text-rose-500">*</span></label>
                    <select name="category_scope" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] transition">
                        <optgroup label="Kategori Umum">
                            <option value="all">Semua Kategori (Universal)</option>
                            <option value="light_vehicle">Light Vehicle (LV)</option>
                        </optgroup>
                        @if(isset($heavyCategories) && count($heavyCategories) > 0)
                        <optgroup label="Kategori Heavy Equipment (Sinkronisasi QMA)">
                            @foreach($heavyCategories as $cat)
                                @php $key = $cat->unit_category ?: $cat->brand_type; @endphp
                                @if($key)
                                    <option value="{{ $key }}">{{ $cat->brand_type ?: $key }} ({{ $cat->code }})</option>
                                @endif
                            @endforeach
                        </optgroup>
                        @endif
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Nama Jenis SIMPER <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="Contoh: SIMPER Mobile Crane & Rigging" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-bold focus:outline-none focus:border-[#1b5e20] transition">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Keterangan / Persyaratan K3 <span class="text-slate-400 font-normal">(Opsional)</span></label>
                <textarea name="description" rows="2" placeholder="Catatan kualifikasi SIO, sertifikasi Kemnaker, atau jenis unit spesifik..." 
                          class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] transition resize-none"></textarea>
            </div>

            <div class="flex items-center space-x-2 pt-1">
                <input type="checkbox" name="is_active" id="addSimperActive" value="1" checked 
                       class="w-4 h-4 rounded text-emerald-700 border-slate-300 focus:ring-emerald-500">
                <label for="addSimperActive" class="text-xs font-bold text-slate-700 cursor-pointer">
                    Aktifkan jenis SIMPER ini untuk penugasan ke personil
                </label>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeAddSimperTypeModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs transition shadow-sm">
                    Simpan Jenis SIMPER
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT JENIS SIMPER                                                  -->
<!-- ========================================================================= -->
<div id="editSimperTypeModal" onclick="if(event.target === this) closeEditSimperTypeModal()" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-slate-100 overflow-hidden animate-scale-in">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-amber-600"></i>
                    <span>Edit Jenis SIMPER</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui informasi dan cakupan kendaraan izin SIMPER.</p>
            </div>
            <button type="button" onclick="closeEditSimperTypeModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="editSimperTypeForm" action="" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kode SIMPER <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" id="editSimperCode" required 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-mono font-bold focus:outline-none focus:border-[#1b5e20] transition uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Cakupan Kendaraan <span class="text-rose-500">*</span></label>
                    <select name="category_scope" id="editSimperScope" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] transition">
                        <optgroup label="Kategori Umum">
                            <option value="all">Semua Kategori (Universal)</option>
                            <option value="light_vehicle">Light Vehicle (LV)</option>
                        </optgroup>
                        @if(isset($heavyCategories) && count($heavyCategories) > 0)
                        <optgroup label="Kategori Heavy Equipment (Sinkronisasi QMA)">
                            @foreach($heavyCategories as $cat)
                                @php $key = $cat->unit_category ?: $cat->brand_type; @endphp
                                @if($key)
                                    <option value="{{ $key }}">{{ $cat->brand_type ?: $key }} ({{ $cat->code }})</option>
                                @endif
                            @endforeach
                        </optgroup>
                        @endif
                        <optgroup label="Cakupan Khusus / Legacy">
                            <option value="unit_support_mobile">Heavy Mobile / Dump Truck (Legacy)</option>
                            <option value="unit_support_heavy">Alat Berat Operasional (Legacy)</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Nama Jenis SIMPER <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="editSimperName" required 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-bold focus:outline-none focus:border-[#1b5e20] transition">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Keterangan / Persyaratan K3 <span class="text-slate-400 font-normal">(Opsional)</span></label>
                <textarea name="description" id="editSimperDescription" rows="2" 
                          class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] transition resize-none"></textarea>
            </div>

            <div class="flex items-center space-x-2 pt-1">
                <input type="checkbox" name="is_active" id="editSimperActive" value="1" 
                       class="w-4 h-4 rounded text-emerald-700 border-slate-300 focus:ring-emerald-500">
                <label for="editSimperActive" class="text-xs font-bold text-slate-700 cursor-pointer">
                    Aktifkan jenis SIMPER ini
                </label>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeEditSimperTypeModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: HAPUS JENIS SIMPER                                                 -->
<!-- ========================================================================= -->
<div id="deleteSimperTypeModal" onclick="if(event.target === this) closeDeleteSimperTypeModal()" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 border border-slate-100 text-center animate-scale-in">
        <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-xl mx-auto mb-3">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="text-sm font-bold text-slate-900 mb-1">Hapus Jenis SIMPER?</h3>
        <p class="text-xs text-slate-500 mb-4">
            Apakah Anda yakin ingin menghapus jenis SIMPER <strong id="deleteSimperTypeName" class="text-slate-800"></strong>?
        </p>

        <form id="deleteSimperTypeForm" action="" method="POST" class="flex items-center justify-center gap-2">
            @csrf
            @method('DELETE')
            <button type="button" onclick="closeDeleteSimperTypeModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                Batal
            </button>
            <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2 rounded-xl text-xs font-bold transition shadow-xs">
                Ya, Hapus
            </button>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: PANDUAN HALAMAN MASTER SIMPER                                      -->
<!-- ========================================================================= -->
<div id="simperTypeGuideModal" onclick="if(event.target === this) closeSimperTypeGuideModal()" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#1b5e20] flex items-center justify-center text-lg shadow-2xs">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Panduan Master SIMPER</h3>
                    <p class="text-xs text-slate-500 font-medium">Pengelolaan izin mengemudi & pengoperasian unit proyek</p>
                </div>
            </div>
            <button type="button" onclick="closeSimperTypeGuideModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="py-5 space-y-3.5 text-xs text-slate-600 leading-relaxed max-h-[60vh] overflow-y-auto pr-1">
            <div class="p-3.5 rounded-2xl bg-emerald-50/60 border border-emerald-100/80">
                <h4 class="font-bold text-[#1b5e20] text-xs flex items-center mb-1">
                    <i class="fa-solid fa-car-side mr-1.5"></i> 1. SIMPER Light Vehicle (LV)
                </h4>
                <p class="text-slate-600">
                    Diperuntukkan bagi driver armada ringan (Hilux, Triton, Avanza, dsb.) yang melintasi gerbang proyek untuk mobilisasi harian.
                </p>
            </div>

            <div class="p-3.5 rounded-2xl bg-amber-50/60 border border-amber-100/80">
                <h4 class="font-bold text-amber-800 text-xs flex items-center mb-1">
                    <i class="fa-solid fa-truck-moving mr-1.5"></i> 2. SIMPER Dump Truck / Heavy Mobile
                </h4>
                <p class="text-slate-600">
                    Diperuntukkan bagi operator/driver armada berat yang bergerak aktif di jalan proyek melintasi pos gerbang (Dump Truck, Mixer, Fuel Truck, Water Truck, Manhaul, dsb.).
                </p>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <h4 class="font-bold text-slate-800 text-xs flex items-center mb-1">
                    <i class="fa-solid fa-trowel-bricks mr-1.5 text-emerald-700"></i> 3. SIMPER Alat Berat Operasional
                </h4>
                <p class="text-slate-600">
                    Diperuntukkan bagi operator alat berat galian, tambang, atau lifting (Excavator, Bulldozer, Wheel Loader, Crane) yang jam kerjanya dicatat terpusat di modul QMA.
                </p>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeSimperTypeGuideModal()" class="px-5 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white text-xs font-bold transition shadow-sm">
                Saya Mengerti
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openAddSimperTypeModal() {
        const modal = document.getElementById('addSimperTypeModal');
        if (modal) modal.classList.remove('hidden');
    }
    function closeAddSimperTypeModal() {
        const modal = document.getElementById('addSimperTypeModal');
        if (modal) modal.classList.add('hidden');
    }

    function openEditSimperTypeModal(st) {
        document.getElementById('editSimperCode').value = st.code || '';
        document.getElementById('editSimperName').value = st.name || '';
        document.getElementById('editSimperScope').value = st.category_scope || 'all';
        document.getElementById('editSimperDescription').value = st.description || '';
        document.getElementById('editSimperActive').checked = Boolean(st.is_active);

        const form = document.getElementById('editSimperTypeForm');
        form.action = `/hse/simper-types/${st.id}`;

        const modal = document.getElementById('editSimperTypeModal');
        if (modal) modal.classList.remove('hidden');
    }
    function closeEditSimperTypeModal() {
        const modal = document.getElementById('editSimperTypeModal');
        if (modal) modal.classList.add('hidden');
    }

    function openDeleteSimperTypeModal(id, name) {
        document.getElementById('deleteSimperTypeName').textContent = name;
        const form = document.getElementById('deleteSimperTypeForm');
        form.action = `/hse/simper-types/${id}`;

        const modal = document.getElementById('deleteSimperTypeModal');
        if (modal) modal.classList.remove('hidden');
    }
    function closeDeleteSimperTypeModal() {
        const modal = document.getElementById('deleteSimperTypeModal');
        if (modal) modal.classList.add('hidden');
    }

    function openSimperTypeGuideModal() {
        const modal = document.getElementById('simperTypeGuideModal');
        if (modal) modal.classList.remove('hidden');
    }
    function closeSimperTypeGuideModal() {
        const modal = document.getElementById('simperTypeGuideModal');
        if (modal) modal.classList.add('hidden');
    }
</script>
@endsection
