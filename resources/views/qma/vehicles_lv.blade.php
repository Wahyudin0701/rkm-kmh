@extends('layouts.app')

@section('title', 'Light Vehicle - Admin QMA | PT Kerinci Merangin Hidro')

@section('content')
<div class="space-y-6">

    <!-- Header Judul & Tombol Aksi -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20] block mb-1">
                MANAJEMEN SISTEM
            </span>
            <div class="flex items-center space-x-3">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Light Vehicle
                </h2>
                <span class="w-6 h-6 rounded-full bg-slate-200/90 text-slate-500 text-xs inline-flex items-center justify-center font-bold shadow-sm" title="Kelola data master armada kendaraan ringan operasional">
                    <i class="fa-solid fa-info"></i>
                </span>
            </div>
        </div>

        <!-- Tombol Aksi Kanan -->
        <div class="flex items-center space-x-3 w-full sm:w-auto">
            <button type="button" 
                    onclick="alert('Fitur ekspor spreadsheet Light Vehicle sedang disiapkan')" 
                    class="border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-4 py-2.5 rounded-xl text-xs font-bold flex items-center space-x-2 transition shadow-sm">
                <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                <span>Ekspor Light Vehicle</span>
                <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
            </button>

            <button onclick="openAddModal()" 
                    class="bg-[#1b5e20] hover:bg-[#144919] text-white px-4 py-2.5 rounded-xl text-xs font-bold flex items-center space-x-2 transition shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah Light Vehicle</span>
            </button>
        </div>
    </div>


    <!-- Kotak Pencarian & Filter Status -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <form method="GET" action="{{ route('qma.vehicles.lv') }}" class="relative w-full max-w-sm sm:max-w-md">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" 
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari kode LV, nopol, merek, tipe, atau pemilik..." 
                   class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 shadow-sm transition">
        </form>

        <div class="flex items-center space-x-2">
            <form method="GET" action="{{ route('qma.vehicles.lv') }}">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                <select name="status" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20]">
                    <option value="">Semua Status Operasi</option>
                    <option value="Operation" {{ request('status') === 'Operation' ? 'selected' : '' }}>Operation (Siap Jalan)</option>
                    <option value="Standby" {{ request('status') === 'Standby' ? 'selected' : '' }}>Standby</option>
                    <option value="Breakdown" {{ request('status') === 'Breakdown' ? 'selected' : '' }}>Breakdown</option>
                    <option value="Maintenance" {{ request('status') === 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
            </form>

            @if(request('search') || request('status'))
                <a href="{{ route('qma.vehicles.lv') }}" class="text-xs text-rose-600 hover:text-rose-800 font-semibold flex items-center space-x-1 whitespace-nowrap ml-2">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Reset Filter</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Tabel Data Light Vehicle (Header Navy Solid #06213f) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4">KODE LV</th>
                        <th class="py-3.5 px-4">MEREK & TIPE</th>
                        <th class="py-3.5 px-4 text-center">NO. POLISI</th>
                        <th class="py-3.5 px-4">DIVISI</th>
                        <th class="py-3.5 px-4">KEPEMILIKAN</th>
                        <th class="py-3.5 px-4 text-right">ODOMETER</th>
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
                                <span class="bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded border border-emerald-200">
                                    {{ $v->code }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ $v->brand_type }}
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
                                    <button onclick='openEditModal(@json($v))' 
                                            class="inline-flex items-center space-x-1 text-amber-600 hover:text-amber-800 hover:bg-amber-50 px-2 py-1 rounded-lg transition"
                                            title="Edit Data Light Vehicle">
                                        <i class="fa-regular fa-pen-to-square text-sm"></i>
                                    </button>
                                    <button type="button" 
                                            onclick="openDeleteModal('{{ $v->id }}', '{{ $v->code }}', '{{ addslashes($v->brand_type ?? '') }}')"
                                            class="inline-flex items-center space-x-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-2 py-1 rounded-lg transition"
                                            title="Hapus Kendaraan">
                                        <i class="fa-regular fa-trash-can text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-car text-3xl mb-2 block text-slate-300"></i>
                                Belum ada armada Light Vehicle yang sesuai dengan kriteria pencarian.
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
<!-- MODAL: TAMBAH LIGHT VEHICLE -->
<!-- ========================================================================= -->
<div id="addVehicleModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
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

        <form id="addVehicleForm" action="{{ route('qma.vehicles.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="category" value="light_vehicle">

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
<div id="editVehicleModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
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

        <form id="editVehicleForm" action="" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="category" value="light_vehicle">

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
<div id="detailVehicleModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
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
<div id="deleteVehicleModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
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
        document.getElementById('editBrandType').value = v.brand_type || '';
        document.getElementById('editLicensePlate').value = v.license_plate || '';
        document.getElementById('editDivision').value = v.division || '';
        document.getElementById('editOwnership').value = v.ownership || '';
        document.getElementById('editStatus').value = v.status || 'Operation';
        document.getElementById('editCurrentKm').value = v.current_km || 0;
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
</script>

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
</style>
@endsection
