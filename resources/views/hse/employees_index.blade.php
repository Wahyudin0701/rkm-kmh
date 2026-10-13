@extends('layouts.app')

@section('title', 'Data Simper - Admin HSE | PT Kerinci Merangin Hidro')

@section('content')
<div class="space-y-6">

    <!-- Header Judul & Tombol Aksi (Persis Format Gambar Data Karyawan IT All Role) -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20] block mb-1">
                MANAJEMEN SISTEM
            </span>
            <div class="flex items-center space-x-3">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Data Simper
                </h2>
                <!-- Info Icon Badge bulat abu-abu sesuai gambar -->
                <span class="w-6 h-6 rounded-full bg-slate-200/90 text-slate-500 text-xs inline-flex items-center justify-center font-bold shadow-sm" title="Kelola data driver dan masa berlaku SIMPER">
                    <i class="fa-solid fa-info"></i>
                </span>
            </div>
        </div>

        <!-- Tombol Aksi Kanan (Ekspor Simper & Tambah Karyawan) -->
        <div class="flex items-center space-x-3 w-full sm:w-auto">
            <!-- Tombol Ekspor Dropdown -->
            <button type="button" 
                    onclick="alert('Fitur ekspor spreadsheet SIMPER sedang disiapkan')" 
                    class="border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-4 py-2.5 rounded-xl text-xs font-bold flex items-center space-x-2 transition shadow-sm">
                <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                <span>Ekspor Simper</span>
                <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
            </button>

            <!-- Tombol Tambah Karyawan (+ Tambah Karyawan Hijau Tua) -->
            <button onclick="document.getElementById('addKaryawanModal').classList.remove('hidden')" 
                    class="bg-[#1b5e20] hover:bg-[#144919] text-white px-4 py-2.5 rounded-xl text-xs font-bold flex items-center space-x-2 transition shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah Karyawan</span>
            </button>
        </div>
    </div>

    <!-- Kotak Pencarian & Filter Status Simper (Tanpa Card Wrapper, Format Referensi) -->
    <form method="GET" action="{{ route('hse.employees.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <!-- Pencarian Cepat di Kiri -->
        <div class="relative w-full max-w-sm sm:max-w-md">
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

        <!-- Filter di Kanan (Disusun dari Kanan ke Tengah) -->
        <div class="flex flex-wrap items-center justify-end gap-2.5">
            <!-- Filter Status Driver -->
            <select name="driver_status" 
                    onchange="this.form.submit()" 
                    class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition">
                <option value="">Semua Status Driver</option>
                <option value="driver" {{ request('driver_status') === 'driver' ? 'selected' : '' }}>Driver</option>
                <option value="non_driver" {{ request('driver_status') === 'non_driver' ? 'selected' : '' }}>Bukan Driver</option>
            </select>

            <!-- Filter Masa Berlaku SIMPER -->
            <select name="simper_status" 
                    onchange="this.form.submit()" 
                    class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition">
                <option value="">Semua Masa Berlaku</option>
                <option value="valid" {{ request('simper_status') === 'valid' ? 'selected' : '' }}>Masih Berlaku (Aktif)</option>
                <option value="expiring_soon" {{ request('simper_status') === 'expiring_soon' ? 'selected' : '' }}>Segera Habis (≤ 30 Hari)</option>
                <option value="expired" {{ request('simper_status') === 'expired' ? 'selected' : '' }}>Kedaluwarsa (Expired)</option>
            </select>

            <!-- Tombol Reset (Hanya tampil saat filter digunakan) -->
            @if(request()->anyFilled(['search', 'driver_status', 'simper_status']))
                <a href="{{ route('hse.employees.index') }}" 
                   class="px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition whitespace-nowrap shadow-sm">
                    <span>Reset</span>
                </a>
            @endif
        </div>
    </form>

    <!-- Tabel Data Simper dengan Header Biru Tua Navy (Persis Format Gambar) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
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
@endsection

@section('scripts')
<script>
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
    document.getElementById('simperSearchInput')?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#simperTableBody tr.simper-row');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
</script>
@endsection
