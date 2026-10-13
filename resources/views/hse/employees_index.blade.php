@extends('layouts.app')

@section('title', 'Data Simper - Admin HSE | PT Kerinci Merangin Hidro')

@section('content')
<div class="space-y-6">

    <!-- Header Judul & Tombol Aksi (Persis Format Gambar Data Karyawan IT All Role) -->
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
                        title="Panduan Halaman Data Simper">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                Data Simper
            </h2>
        </div>

        <!-- Tombol Aksi Kanan (Pada Mobile: Lebar Sama / Proporsional 50-50) -->
        <div class="grid grid-cols-2 gap-2.5 w-full sm:flex sm:items-center sm:gap-3 sm:w-auto">
            <!-- Tombol Ekspor Dropdown -->
            <button type="button" 
                    onclick="alert('Fitur ekspor spreadsheet SIMPER sedang disiapkan')" 
                    class="w-full sm:w-auto border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition shadow-sm">
                <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                <span>Ekspor Simper</span>
                <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-0.5 sm:ml-1"></i>
            </button>

            <!-- Tombol Tambah Karyawan (+ Tambah Karyawan Hijau Tua) -->
            <button type="button"
                    onclick="document.getElementById('addKaryawanModal').classList.remove('hidden')" 
                    class="w-full sm:w-auto bg-[#1b5e20] hover:bg-[#144919] text-white px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah Karyawan</span>
            </button>
        </div>
    </div>

    <!-- Kotak Pencarian & Filter Status Simper (Tanpa Card Wrapper, Format Referensi) -->
    <form method="GET" action="{{ route('hse.employees.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 sm:gap-3">
        <!-- Pencarian Cepat di Kiri -->
        <div class="relative w-full sm:max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" 
                   name="search"
                   id="simperSearchInput"
                   value="{{ request('search') }}"
                   placeholder="Cari NIK atau nama karyawan..." 
                   class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition">
        </div>

        <!-- Filter di Kanan (Pada Mobile: Sejajar 2 Kolom di Bawah Search Bar) -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2 sm:gap-2.5 w-full sm:w-auto">
            <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center sm:gap-2.5 w-full sm:w-auto">
                <!-- Filter Status Driver -->
                <select name="driver_status" 
                        onchange="this.form.submit()" 
                        class="w-full sm:w-auto text-xs rounded-xl border border-slate-200 bg-white px-2.5 sm:px-3 py-2.5 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition truncate">
                    <option value="">Semua Status Driver</option>
                    <option value="driver" {{ request('driver_status') === 'driver' ? 'selected' : '' }}>Driver</option>
                    <option value="non_driver" {{ request('driver_status') === 'non_driver' ? 'selected' : '' }}>Bukan Driver</option>
                </select>

                <!-- Filter Masa Berlaku SIMPER -->
                <select name="simper_status" 
                        onchange="this.form.submit()" 
                        class="w-full sm:w-auto text-xs rounded-xl border border-slate-200 bg-white px-2.5 sm:px-3 py-2.5 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition truncate">
                    <option value="">Semua Masa Berlaku</option>
                    <option value="valid" {{ request('simper_status') === 'valid' ? 'selected' : '' }}>Masih Berlaku (Aktif)</option>
                    <option value="expiring_soon" {{ request('simper_status') === 'expiring_soon' ? 'selected' : '' }}>Segera Habis (≤ 30 Hari)</option>
                    <option value="expired" {{ request('simper_status') === 'expired' ? 'selected' : '' }}>Kedaluwarsa (Expired)</option>
                </select>
            </div>

            <!-- Tombol Reset Filter (Sesuai Referensi Merah / Rose Clean Text) -->
            <div id="simperResetContainer">
                @if(request()->anyFilled(['search', 'driver_status', 'simper_status']))
                    <a href="{{ route('hse.employees.index') }}" 
                       id="simperResetBtn"
                       class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center justify-center sm:justify-start space-x-1.5 transition whitespace-nowrap py-1 sm:py-0 sm:ml-2">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i>
                        <span>Reset Filter</span>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Tabel Data Simper dengan Header Biru Tua Navy (Pada Mobile: Format Kotak Tanpa Border Radius) -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <!-- Header Tabel Navy Gelap Solid #06213f -->
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4">NIK</th>
                        <th class="py-3.5 px-4">NAMA LENGKAP</th>
                        <th class="py-3.5 px-4">JABATAN</th>
                        <th class="py-3.5 px-4">UNIT / DIVISI</th>
                        <th class="py-3.5 px-4 text-center">STATUS DRIVER</th>
                        <th class="py-3.5 px-4 text-center">MASA BERLAKU SIMPER</th>
                        <th class="py-3.5 px-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody id="simperTableBody" class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($employees as $index => $emp)
                        @php
                            $isValid = $emp->hasValidSimper();
                        @endphp
                        <tr class="simper-row hover:bg-slate-50/70 transition">
                            <!-- Nomor -->
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">{{ $index + 1 }}</td>
                            
                            <!-- NIK (Font Bold Hitam Tegas) -->
                            <td class="py-3.5 px-4 font-bold text-slate-900 font-mono text-xs">{{ $emp->nik }}</td>
                            
                            <!-- Nama Lengkap (Font Bold Hitam) -->
                            <td class="py-3.5 px-4 font-bold text-slate-900">{{ $emp->name }}</td>
                            
                            <!-- Jabatan -->
                            <td class="py-3.5 px-4 text-slate-600">{{ $emp->position ?? '-' }}</td>
                            
                            <!-- Unit / Divisi -->
                            <td class="py-3.5 px-4 text-slate-500">
                                <span class="font-medium text-slate-700">{{ $emp->unit_kerja ?? '-' }}</span>
                                @if($emp->division)
                                    <span class="text-slate-400 block text-[10px]">{{ $emp->division }}</span>
                                @endif
                            </td>
                            
                            <!-- Status Driver -->
                            <td class="py-3.5 px-4 text-center">
                                @if($emp->is_operator)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        Driver
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500">
                                        Bukan Driver
                                    </span>
                                @endif
                            </td>
                            
                            <!-- Masa Berlaku SIMPER -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if(!$emp->is_operator)
                                    <span class="text-slate-400 italic text-[11px]">-</span>
                                @elseif($isValid)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                        <i class="fa-solid fa-circle-check mr-1.5 text-emerald-600 text-[10px]"></i>
                                        {{ $emp->simper_expired_at->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200 font-mono">
                                        <i class="fa-solid fa-circle-xmark mr-1.5 text-rose-600 text-[10px]"></i>
                                        {{ $emp->simper_expired_at ? $emp->simper_expired_at->format('d/m/Y') : 'Belum Ada' }}
                                    </span>
                                @endif
                            </td>
                            
                            <!-- Aksi (Edit Data Simper & Perpanjang SIMPER) -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-2">
                                    <button onclick='openEditModal(@json($emp))' 
                                            class="inline-flex items-center space-x-1 text-amber-500 hover:text-amber-700 hover:bg-amber-50 px-2 py-1 rounded-lg transition"
                                            title="Edit Data Simper Karyawan">
                                        <i class="fa-regular fa-pen-to-square text-sm"></i>
                                        <span class="text-xs font-semibold">Edit</span>
                                    </button>
                                    <span class="text-slate-200">|</span>
                                    <button onclick="openRenewModal({{ $emp->id }}, '{{ $emp->name }}', '{{ $emp->simper_expired_at ? $emp->simper_expired_at->format('Y-m-d') : '' }}', {{ $emp->is_operator ? '1' : '0' }})" 
                                            class="text-emerald-700 hover:text-emerald-900 font-bold underline text-[11px]"
                                            title="Perpanjang Masa Berlaku SIMPER">
                                        Perpanjang
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <i class="fa-regular fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                                Belum ada data pengemudi atau SIMPER yang sesuai kriteria filter.
                                @if(request()->anyFilled(['search', 'driver_status', 'simper_status']))
                                    <div class="mt-3">
                                        <a href="{{ route('hse.employees.index') }}" class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#1b5e20] bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition">
                                            <span>Reset Filter</span>
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Dialog Add Karyawan (Persis Format Gambar IT All Role) -->
<div id="addKaryawanModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-7 sm:p-8 border border-slate-100 overflow-hidden">
        <!-- Header Modal Bersih Tanpa Banner Gelap Sesuai Gambar -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                Tambah Data Simper
            </h3>
            <button type="button" onclick="document.getElementById('addKaryawanModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('hse.employees.store') }}" method="POST" class="mt-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-slate-800 mb-2">Nomor Induk Karyawan (NIK) <span class="text-rose-500">*</span></label>
                <input type="text" name="nik" required placeholder="Contoh: 0001" 
                       class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-800 mb-2">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="Contoh: Ahmad Budi" 
                       class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Unit Kerja <span class="text-rose-500">*</span></label>
                    <select name="unit_kerja" required 
                            class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        <option value="" disabled selected>Pilih Unit Kerja</option>
                        <option value="Pembangkit">Pembangkit</option>
                        <option value="Bendungan">Bendungan</option>
                        <option value="Teknik">Teknik</option>
                        <option value="Umum">Umum</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Divisi <span class="text-rose-500">*</span></label>
                    <select name="division" required 
                            class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        <option value="" disabled selected>Pilih Divisi</option>
                        <option value="INFORMATION & TECHNOLOGY">INFORMATION & TECHNOLOGY</option>
                        <option value="K3">K3 / HSE</option>
                        <option value="Quality Management Asset (QMA)">Quality Management Asset (QMA)</option>
                        <option value="GA">General Affairs (GA)</option>
                        <option value="HR">Human Resources (HR)</option>
                        <option value="Keuangan">Keuangan</option>
                        <option value="Logistik">Logistik / Warehouse</option>
                        <option value="Mechanical">Mechanical</option>
                        <option value="Civil & Inviro">Civil & Inviro</option>
                        <option value="PG">Private Guard (PG)</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-800 mb-2">Jabatan <span class="text-rose-500">*</span></label>
                <input type="text" name="position" required placeholder="Contoh: Staff IT" 
                       class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Operator / Driver <span class="text-rose-500">*</span></label>
                    <select name="is_operator" id="addOperator" onchange="toggleSimperInput('add')" required 
                            class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        <option value="1">YA (Aktif Mengemudi)</option>
                        <option value="0">TIDAK (Bukan Driver)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Masa Berlaku SIMPER</label>
                    <input type="date" name="simper_expired_at" id="addSimperDate"
                           class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <!-- Footer Tombol: Batal & Simpan Data Sesuai Gambar -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3 mt-6">
                <button type="button" onclick="document.getElementById('addKaryawanModal').classList.add('hidden')" 
                        class="px-6 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-sm transition shadow-sm">
                    Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Dialog Perpanjang SIMPER (Clean White Card) -->
<div id="renewModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-7 border border-slate-100 overflow-hidden">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">Perpanjang SIMPER</h3>
            <button type="button" onclick="document.getElementById('renewModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="renewForm" method="POST" action="" class="mt-5 space-y-4">
            @csrf
            <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80">
                <span class="text-xs text-slate-400 block font-medium">Pengemudi:</span>
                <strong id="renewName" class="block text-base text-slate-900 font-bold mt-0.5"></strong>
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-800 mb-2">Status Driver</label>
                <select name="is_operator" id="renewOperator" onchange="toggleSimperInput('renew')" 
                        class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                    <option value="1">YA (Aktif Mengemudi)</option>
                    <option value="0">TIDAK (Bukan Driver)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-800 mb-2">Masa Berlaku Baru <span class="text-rose-500">*</span></label>
                <input type="date" name="simper_expired_at" id="renewDate" required 
                       class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
            </div>
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3 mt-6">
                <button type="button" onclick="document.getElementById('renewModal').classList.add('hidden')" 
                        class="px-6 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-sm transition shadow-sm">
                    Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Dialog Edit Data Simper Karyawan -->
<div id="editModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-7 sm:p-8 border border-slate-100 overflow-hidden">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                Edit Data Simper
            </h3>
            <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="editForm" method="POST" action="" class="mt-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-slate-800 mb-2">Nomor Induk Karyawan (NIK) <span class="text-rose-500">*</span></label>
                <input type="text" name="nik" id="editNik" required placeholder="Contoh: 0001" 
                       class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-800 mb-2">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="editName" required placeholder="Contoh: Ahmad Budi" 
                       class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Unit Kerja <span class="text-rose-500">*</span></label>
                    <select name="unit_kerja" id="editUnitKerja" required 
                            class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        <option value="Pembangkit">Pembangkit</option>
                        <option value="Bendungan">Bendungan</option>
                        <option value="Teknik">Teknik</option>
                        <option value="Umum">Umum</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Divisi <span class="text-rose-500">*</span></label>
                    <select name="division" id="editDivision" required 
                            class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        <option value="INFORMATION & TECHNOLOGY">INFORMATION & TECHNOLOGY</option>
                        <option value="K3">K3 / HSE</option>
                        <option value="Quality Management Asset (QMA)">Quality Management Asset (QMA)</option>
                        <option value="GA">General Affairs (GA)</option>
                        <option value="HR">Human Resources (HR)</option>
                        <option value="Keuangan">Keuangan</option>
                        <option value="Logistik">Logistik / Warehouse</option>
                        <option value="Mechanical">Mechanical</option>
                        <option value="Civil & Inviro">Civil & Inviro</option>
                        <option value="PG">Private Guard (PG)</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-800 mb-2">Jabatan <span class="text-rose-500">*</span></label>
                <input type="text" name="position" id="editPosition" required placeholder="Contoh: Staff IT" 
                       class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Operator / Driver <span class="text-rose-500">*</span></label>
                    <select name="is_operator" id="editOperator" onchange="toggleSimperInput('edit')" required 
                            class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        <option value="1">YA (Aktif Mengemudi)</option>
                        <option value="0">TIDAK (Bukan Driver)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Masa Berlaku SIMPER</label>
                    <input type="date" name="simper_expired_at" id="editSimperDate" 
                           class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>
            </div>

            <!-- Footer Tombol -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3 mt-6">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" 
                        class="px-6 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-sm transition shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (DATA SIMPER)                                -->
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
                    <p class="text-xs text-slate-500">Data Simper & Pengemudi</p>
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
                Selamat datang di menu <strong>Data Simper</strong>! Halaman ini adalah pusat pengelolaan izin mengemudi SIMPER dan data master pengemudi operasional PT Kerinci Merangin Hidro. Di halaman ini Anda dapat:
            </p>

            <div class="space-y-3">
                <!-- Fitur 1 (Hijau) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-emerald-200/50">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Kelola Masa Berlaku SIMPER</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Memantau masa berlaku SIMPER setiap driver, memperbarui tanggal berlaku, dan mengunggah dokumen pendukung agar patuh regulasi keselamatan kerja (HSE).
                        </p>
                    </div>
                </div>

                <!-- Fitur 2 (Biru) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-blue-200/50">
                        <i class="fa-solid fa-filter"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Pencarian & Filter Cepat</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Mencari data berdasarkan NIK, Nama, atau Divisi, serta menyaring berdasarkan status pengemudi (Driver / Bukan Driver) dan masa kedaluwarsa SIMPER.
                        </p>
                    </div>
                </div>

                <!-- Fitur 3 (Amber) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-amber-200/50">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Tambah & Perbarui Data Karyawan</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Mendaftarkan karyawan baru melalui tombol '+ Tambah Karyawan', serta mengedit informasi jabatan atau unit kerja melalui tombol aksi di tabel.
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
@endsection

@section('scripts')
<script>
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

    // Logika Penguncian Input SIMPER jika Bukan Driver
    function toggleSimperInput(type) {
        let opSelect, dateInput;
        if (type === 'add') {
            opSelect = document.getElementById('addOperator');
            dateInput = document.getElementById('addSimperDate');
        } else if (type === 'edit') {
            opSelect = document.getElementById('editOperator');
            dateInput = document.getElementById('editSimperDate');
        } else if (type === 'renew') {
            opSelect = document.getElementById('renewOperator');
            dateInput = document.getElementById('renewDate');
        }

        if (!opSelect || !dateInput) return;

        if (opSelect.value === '0') {
            // Bukan Driver: Kosongkan dan kunci input tanggal SIMPER
            dateInput.value = '';
            dateInput.disabled = true;
            dateInput.required = false;
            dateInput.classList.add('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
            dateInput.classList.remove('bg-white', 'text-slate-800');
        } else {
            // Driver: Aktifkan input tanggal SIMPER
            dateInput.disabled = false;
            dateInput.classList.remove('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
            dateInput.classList.add('bg-white', 'text-slate-800');
        }
    }

    function openEditModal(emp) {
        document.getElementById('editNik').value = emp.nik || '';
        document.getElementById('editName').value = emp.name || '';
        document.getElementById('editUnitKerja').value = emp.unit_kerja || '';
        document.getElementById('editDivision').value = emp.division || '';
        document.getElementById('editPosition').value = emp.position || '';
        document.getElementById('editOperator').value = emp.is_operator ? '1' : '0';
        
        let expDate = '';
        if (emp.simper_expired_at) {
            expDate = emp.simper_expired_at.substring(0, 10);
        }
        document.getElementById('editSimperDate').value = expDate;
        
        // Cek status driver untuk mengunci/membuka input SIMPER
        toggleSimperInput('edit');
        
        document.getElementById('editForm').action = `/hse/employees/${emp.id}`;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function openRenewModal(id, name, expiredDate, isOperator) {
        document.getElementById('renewName').textContent = name;
        document.getElementById('renewOperator').value = isOperator ? '1' : '0';
        document.getElementById('renewDate').value = expiredDate;
        
        // Cek status driver untuk mengunci/membuka input SIMPER
        toggleSimperInput('renew');

        document.getElementById('renewForm').action = `/hse/employees/${id}`;
        document.getElementById('renewModal').classList.remove('hidden');
    }

    // Inisialisasi awal untuk modal tambah
    document.addEventListener('DOMContentLoaded', function() {
        toggleSimperInput('add');
    });

    // Filter Pencarian Instan Real-time
    const searchInput = document.getElementById('simperSearchInput');
    const resetContainer = document.getElementById('simperResetContainer');
    const hasServerFilters = {{ request()->anyFilled(['search', 'driver_status', 'simper_status']) ? 'true' : 'false' }};

    searchInput?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#simperTableBody tr.simper-row');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });

        // Tampilkan/sembunyikan tombol Reset Filter secara real-time saat mengetik
        let resetBtn = document.getElementById('simperResetBtn');
        if (query.length > 0) {
            if (!resetBtn && resetContainer) {
                resetBtn = document.createElement('a');
                resetBtn.id = 'simperResetBtn';
                resetBtn.href = "{{ route('hse.employees.index') }}";
                resetBtn.className = "text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center justify-center sm:justify-start space-x-1.5 transition whitespace-nowrap py-1 sm:py-0 sm:ml-2";
                const resetIcon = document.createElement('i');
                resetIcon.className = "fa-solid fa-rotate-left text-[11px]";
                const resetText = document.createElement('span');
                resetText.textContent = "Reset Filter";
                resetBtn.appendChild(resetIcon);
                resetBtn.appendChild(resetText);
                resetContainer.appendChild(resetBtn);
            }
        } else if (!hasServerFilters && resetBtn) {
            resetBtn.remove();
        }
    });

    // Keyboard ESC untuk menutup modal panduan
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGuideModal();
        }
    });
</script>
@endsection
