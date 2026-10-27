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

    <!-- Ringkasan Statistik Minimalis (Tanpa Ikon & Tanpa Card Fleksibilitas) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-xl">
        <div class="bg-white rounded-2xl border border-slate-200/90 px-6 py-4 shadow-xs">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Kategori</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-1">
                {{ count($simperTypes) }} <span class="text-xs font-semibold text-slate-400">Jenis</span>
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/90 px-6 py-4 shadow-xs">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pemegang Aktif</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-1">
                {{ $simperTypes->sum('active_holders_count') }} <span class="text-xs font-semibold text-slate-400">Personil</span>
            </p>
        </div>
    </div>

    <!-- Tabel Data Master Jenis SIMPER -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-4 px-5 text-center w-14">NO</th>
                        <th class="py-4 px-5 whitespace-nowrap">KODE</th>
                        <th class="py-4 px-5 whitespace-nowrap min-w-[200px]">NAMA JENIS SIMPER</th>
                        <th class="py-4 px-5 whitespace-nowrap min-w-[240px]">CAKUPAN KENDARAAN</th>
                        <th class="py-4 px-5 text-center whitespace-nowrap">PEMEGANG AKTIF</th>
                        <th class="py-4 px-5 text-center whitespace-nowrap">STATUS</th>
                        <th class="py-4 px-5 whitespace-nowrap min-w-[240px]">KETERANGAN</th>
                        <th class="py-4 px-5 text-center whitespace-nowrap w-28">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 bg-white font-medium">
                    @forelse($simperTypes as $idx => $st)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-4 px-5 text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                        <td class="py-4 px-5 whitespace-nowrap">
                            <span class="inline-block font-mono font-extrabold text-xs text-emerald-900 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200 whitespace-nowrap">
                                {{ $st->code }}
                            </span>
                        </td>
                        <td class="py-4 px-5 min-w-[200px]">
                            <span class="font-bold text-slate-900 text-[13px] block">{{ $st->name }}</span>
                        </td>
                        <td class="py-4 px-5">
                            <div class="flex flex-wrap items-center gap-1.5 min-w-[240px]">
                                @if($st->coversScope('all'))
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        Semua Kategori (Universal)
                                    </span>
                                @else
                                    @php $scopesList = $st->scopes; @endphp
                                    @foreach(array_slice($scopesList, 0, 3) as $scope)
                                        @if($scope === 'light_vehicle')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                LV (Light Vehicle)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                {{ str_replace('_', ' ', $scope) }}
                                            </span>
                                        @endif
                                    @endforeach
                                    @if(count($scopesList) > 3)
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200" 
                                              title="{{ implode(', ', array_slice($scopesList, 3)) }}">
                                            +{{ count($scopesList) - 3 }} lainnya
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </td>
                        <td class="py-4 px-5 text-center font-bold whitespace-nowrap">
                            <span class="inline-block font-mono text-xs text-slate-800 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200">
                                {{ $st->active_holders_count }} Personil
                            </span>
                        </td>
                        <td class="py-4 px-5 text-center whitespace-nowrap">
                            @if($st->is_active)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                    Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-5 min-w-[240px]">
                            <p class="text-xs text-slate-500 leading-relaxed line-clamp-2">
                                {{ $st->description ?: '-' }}
                            </p>
                        </td>
                        <td class="py-4 px-5 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-1.5">
                                @can('hse.employees.edit')
                                <button type="button" 
                                        onclick="openEditSimperTypeModal({{ json_encode($st) }})" 
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-700 bg-amber-50 hover:bg-amber-100 transition border border-amber-200 cursor-pointer" 
                                        title="Edit Jenis SIMPER">
                                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                                </button>
                                @if($st->total_holders_count == 0)
                                <button type="button" 
                                        onclick="openDeleteSimperTypeModal({{ $st->id }}, '{{ $st->name }}')" 
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-rose-700 bg-rose-50 hover:bg-rose-100 transition border border-rose-200 cursor-pointer" 
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
                        <td colspan="8" class="py-16 text-center text-slate-400">
                            <p class="text-sm font-bold text-slate-700">Belum ada data jenis SIMPER</p>
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
    <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full p-6 sm:p-7 border border-slate-100 flex flex-col max-h-[92vh] overflow-hidden animate-scale-in">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 flex-shrink-0">
            <div>
                <h3 class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-id-card text-emerald-700"></i>
                    <span>Tambah Jenis SIMPER</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Definisikan kategori izin mengemudi dan pilih satu atau lebih cakupan armada.</p>
            </div>
            <button type="button" onclick="closeAddSimperTypeModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('hse.simper_types.store') }}" method="POST" class="flex flex-col flex-1 min-h-0 mt-4">
            @csrf

            <div class="overflow-y-auto flex-1 pr-1 pb-2 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">Kode SIMPER <span class="text-rose-500">*</span></label>
                        <input type="text" name="code" required placeholder="Contoh: SIMPER-HAULING" 
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-mono font-bold focus:outline-none focus:border-[#1b5e20] transition uppercase">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">Nama Jenis SIMPER <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="Contoh: SIMPER Angkutan Berat & Material" 
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-bold focus:outline-none focus:border-[#1b5e20] transition">
                    </div>
                </div>

                <!-- Cakupan Kendaraan (Multiple Choice Selection) -->
                <div class="space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-1">
                        <div>
                            <label class="block text-xs font-bold text-slate-800">
                                Cakupan Armada yang Diizinkan <span class="text-rose-500">*</span>
                            </label>
                            <p class="text-[11px] text-slate-500">Centang satu atau lebih jenis armada yang dapat dioperasikan pemegang SIMPER ini.</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span id="addScopeSelectedCount" class="text-[11px] font-mono font-bold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200">
                                0 Kategori Dipilih
                            </span>
                        </div>
                    </div>

                    <!-- Toolbar Filter & Quick Actions -->
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                            <input type="text" placeholder="Cari kategori unit (contoh: Dump Truck, Excavator)..." 
                                   oninput="filterScopeOptions(this.value, 'add')" 
                                   class="w-full text-xs pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600">
                        </div>
                        <button type="button" onclick="selectAllHeavyScopes('add')" 
                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold transition whitespace-nowrap">
                            Pilih Semua Alat Berat
                        </button>
                        <button type="button" onclick="clearAllScopes('add')" 
                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 text-[11px] font-bold transition">
                            Reset
                        </button>
                    </div>

                    <!-- Scrollable Checkbox Grid -->
                    <div class="max-h-56 overflow-y-auto pr-1 space-y-3 p-3 rounded-2xl border border-slate-200 bg-slate-50/70">
                        <!-- Pilihan Umum -->
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-1.5">
                                Kategori Umum
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label class="add-scope-item flex items-center space-x-2.5 p-2.5 rounded-xl border border-purple-200 bg-purple-50/40 hover:bg-purple-100/60 transition cursor-pointer text-xs" data-name="semua kategori universal all">
                                    <input type="checkbox" name="category_scopes[]" value="all" onchange="updateScopeCount('add')" class="add-scope-cb w-4 h-4 rounded text-purple-700 border-purple-300 focus:ring-purple-500 flex-shrink-0">
                                    <div>
                                        <span class="font-extrabold text-purple-900 block text-xs">Semua Kategori (Universal)</span>
                                        <span class="text-[10px] text-purple-600">Mencakup semua armada LV dan Heavy Equipment</span>
                                    </div>
                                </label>
                                <label class="add-scope-item flex items-center space-x-2.5 p-2.5 rounded-xl border border-blue-200 bg-blue-50/40 hover:bg-blue-100/60 transition cursor-pointer text-xs" data-name="light vehicle lv mobil operasional">
                                    <input type="checkbox" name="category_scopes[]" value="light_vehicle" onchange="updateScopeCount('add')" class="add-scope-cb w-4 h-4 rounded text-blue-700 border-blue-300 focus:ring-blue-500 flex-shrink-0">
                                    <div>
                                        <span class="font-extrabold text-blue-900 block text-xs">Light Vehicle (LV)</span>
                                        <span class="text-[10px] text-blue-600">Khusus kendaraan operasional ringan proyek</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Pilihan Heavy Equipment -->
                        @php
                            $generalKeys = ['all', 'light_vehicle'];
                            $heavyScopes = array_diff_key($categoryScopes, array_flip($generalKeys));
                        @endphp
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-1.5">
                                Kategori Heavy Equipment (Alat Berat)
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                @foreach($heavyScopes as $scopeKey => $scopeLabel)
                                <label data-name="{{ strtolower($scopeKey . ' ' . $scopeLabel) }}" 
                                       class="add-scope-item flex items-center space-x-2 p-2 rounded-xl border border-slate-200 bg-white hover:bg-emerald-50/70 hover:border-emerald-300 transition cursor-pointer text-xs">
                                    <input type="checkbox" 
                                           name="category_scopes[]" 
                                           value="{{ $scopeKey }}" 
                                           onchange="updateScopeCount('add')" 
                                           class="add-scope-cb add-scope-heavy-cb w-4 h-4 rounded text-[#1b5e20] border-slate-300 focus:ring-emerald-500 flex-shrink-0">
                                    <span class="font-bold text-slate-800 text-[11px] truncate" title="{{ $scopeKey }}">
                                        {{ str_replace('Heavy: ', '', $scopeLabel) }}
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
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
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5 flex-shrink-0">
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
    <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full p-6 sm:p-7 border border-slate-100 flex flex-col max-h-[92vh] overflow-hidden animate-scale-in">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 flex-shrink-0">
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

        <form id="editSimperTypeForm" action="" method="POST" class="flex flex-col flex-1 min-h-0 mt-4">
            @csrf
            @method('PUT')

            <div class="overflow-y-auto flex-1 pr-1 pb-2 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">Kode SIMPER <span class="text-rose-500">*</span></label>
                        <input type="text" name="code" id="editSimperCode" required 
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-mono font-bold focus:outline-none focus:border-[#1b5e20] transition uppercase">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">Nama Jenis SIMPER <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="editSimperName" required 
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-bold focus:outline-none focus:border-[#1b5e20] transition">
                    </div>
                </div>

                <!-- Cakupan Kendaraan (Multiple Choice Selection) -->
                <div class="space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-1">
                        <div>
                            <label class="block text-xs font-bold text-slate-800">
                                Cakupan Armada yang Diizinkan <span class="text-rose-500">*</span>
                            </label>
                            <p class="text-[11px] text-slate-500">Centang satu atau lebih jenis armada yang dapat dioperasikan pemegang SIMPER ini.</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span id="editScopeSelectedCount" class="text-[11px] font-mono font-bold px-2.5 py-1 rounded-lg bg-amber-50 text-amber-800 border border-amber-200">
                                0 Kategori Dipilih
                            </span>
                        </div>
                    </div>

                    <!-- Toolbar Filter & Quick Actions -->
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                            <input type="text" placeholder="Cari kategori unit (contoh: Dump Truck, Excavator)..." 
                                   oninput="filterScopeOptions(this.value, 'edit')" 
                                   class="w-full text-xs pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-amber-600">
                        </div>
                        <button type="button" onclick="selectAllHeavyScopes('edit')" 
                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold transition whitespace-nowrap">
                            Pilih Semua Alat Berat
                        </button>
                        <button type="button" onclick="clearAllScopes('edit')" 
                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 text-[11px] font-bold transition">
                            Reset
                        </button>
                    </div>

                    <!-- Scrollable Checkbox Grid -->
                    <div class="max-h-56 overflow-y-auto pr-1 space-y-3 p-3 rounded-2xl border border-slate-200 bg-slate-50/70">
                        <!-- Pilihan Umum -->
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-1.5">
                                Kategori Umum
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label class="edit-scope-item flex items-center space-x-2.5 p-2.5 rounded-xl border border-purple-200 bg-purple-50/40 hover:bg-purple-100/60 transition cursor-pointer text-xs" data-name="semua kategori universal all">
                                    <input type="checkbox" name="category_scopes[]" value="all" onchange="updateScopeCount('edit')" class="edit-scope-cb w-4 h-4 rounded text-purple-700 border-purple-300 focus:ring-purple-500 flex-shrink-0">
                                    <div>
                                        <span class="font-extrabold text-purple-900 block text-xs">Semua Kategori (Universal)</span>
                                        <span class="text-[10px] text-purple-600">Mencakup semua armada LV dan Heavy Equipment</span>
                                    </div>
                                </label>
                                <label class="edit-scope-item flex items-center space-x-2.5 p-2.5 rounded-xl border border-blue-200 bg-blue-50/40 hover:bg-blue-100/60 transition cursor-pointer text-xs" data-name="light vehicle lv mobil operasional">
                                    <input type="checkbox" name="category_scopes[]" value="light_vehicle" onchange="updateScopeCount('edit')" class="edit-scope-cb w-4 h-4 rounded text-blue-700 border-blue-300 focus:ring-blue-500 flex-shrink-0">
                                    <div>
                                        <span class="font-extrabold text-blue-900 block text-xs">Light Vehicle (LV)</span>
                                        <span class="text-[10px] text-blue-600">Khusus kendaraan operasional ringan proyek</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Pilihan Heavy Equipment -->
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-1.5">
                                Kategori Heavy Equipment (Alat Berat)
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                @foreach($heavyScopes as $scopeKey => $scopeLabel)
                                <label data-name="{{ strtolower($scopeKey . ' ' . $scopeLabel) }}" 
                                       class="edit-scope-item flex items-center space-x-2 p-2 rounded-xl border border-slate-200 bg-white hover:bg-emerald-50/70 hover:border-emerald-300 transition cursor-pointer text-xs">
                                    <input type="checkbox" 
                                           name="category_scopes[]" 
                                           value="{{ $scopeKey }}" 
                                           onchange="updateScopeCount('edit')" 
                                           class="edit-scope-cb edit-scope-heavy-cb w-4 h-4 rounded text-emerald-700 border-slate-300 focus:ring-emerald-500 flex-shrink-0">
                                    <span class="font-bold text-slate-800 text-[11px] truncate" title="{{ $scopeKey }}">
                                        {{ str_replace('Heavy: ', '', $scopeLabel) }}
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
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
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5 flex-shrink-0">
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
    function updateScopeCount(prefix) {
        const checked = document.querySelectorAll(`.${prefix}-scope-cb:checked`);
        const badge = document.getElementById(`${prefix}ScopeSelectedCount`);
        if (badge) {
            badge.textContent = `${checked.length} Kategori Dipilih`;
        }
    }

    function selectAllHeavyScopes(prefix) {
        document.querySelectorAll(`.${prefix}-scope-heavy-cb`).forEach(cb => {
            cb.checked = true;
        });
        updateScopeCount(prefix);
    }

    function clearAllScopes(prefix) {
        document.querySelectorAll(`.${prefix}-scope-cb`).forEach(cb => {
            cb.checked = false;
        });
        updateScopeCount(prefix);
    }

    function filterScopeOptions(keyword, prefix) {
        const q = (keyword || '').toLowerCase().trim();
        document.querySelectorAll(`.${prefix}-scope-item`).forEach(el => {
            const text = (el.dataset.name || el.textContent || '').toLowerCase();
            if (!q || text.includes(q)) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        });
    }

    function openAddSimperTypeModal() {
        document.querySelectorAll('.add-scope-cb').forEach(cb => {
            cb.checked = (cb.value === 'all');
        });
        updateScopeCount('add');

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
        document.getElementById('editSimperDescription').value = st.description || '';
        document.getElementById('editSimperActive').checked = Boolean(st.is_active);

        let targetScopes = [];
        if (Array.isArray(st.scopes)) {
            targetScopes = st.scopes;
        } else if (typeof st.category_scope === 'string') {
            try {
                const parsed = JSON.parse(st.category_scope);
                targetScopes = Array.isArray(parsed) ? parsed : [st.category_scope];
            } catch(e) {
                targetScopes = [st.category_scope];
            }
        }

        document.querySelectorAll('.edit-scope-cb').forEach(cb => {
            cb.checked = targetScopes.includes(cb.value);
        });
        updateScopeCount('edit');

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
