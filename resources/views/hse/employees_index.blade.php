@extends('layouts.app')

@section('title', 'Data Simper - Admin HSE | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

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

        <!-- Tombol Aksi Kanan -->
        <div class="flex items-center gap-2.5 sm:gap-3 w-full sm:w-auto">
            <!-- Dropdown Menu Ekspor Format it.allrole.web.id -->
            <div class="relative inline-block w-full sm:w-auto flex-1 sm:flex-none" id="exportSimperDropdownContainer">
                <button type="button" 
                        onclick="toggleSimperExportMenu(event)" 
                        id="btnSimperExportTrigger"
                        class="w-full sm:w-auto border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition shadow-sm cursor-pointer">
                    <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                    <span class="truncate">Ekspor Simper</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-0.5 sm:ml-1"></i>
                </button>
                <div id="exportMenuSimper" style="display: none;" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 z-50 overflow-hidden">
                    <a href="{{ route('hse.employees.export_pdf', request()->query()) }}" target="_blank" class="flex items-center px-4 py-3 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#1b5e20] font-bold transition">
                        <i class="fa-regular fa-file-pdf text-rose-600 text-sm mr-2.5"></i>
                        <span>Format PDF (Cetak)</span>
                    </a>
                    <div class="h-px bg-slate-100 mx-2"></div>
                    <a href="{{ route('hse.employees.export_excel', request()->query()) }}" class="flex items-center px-4 py-3 text-xs text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i class="fa-regular fa-file-excel text-emerald-600 text-sm mr-2.5"></i>
                        <span>Format Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            <!-- Tombol Tambah & Impor Karyawan (Diatur Hak Akses hse.employees.create) -->
            @can('hse.employees.create')
            <button type="button" 
                    onclick="openImportKaryawanModal()" 
                    class="w-full sm:w-auto flex-1 sm:flex-none border border-emerald-600/30 text-emerald-800 bg-emerald-50/60 hover:bg-emerald-100/70 px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition shadow-sm cursor-pointer"
                    title="Impor Data Karyawan secara Massal dari File Excel / CSV">
                <i class="fa-solid fa-file-arrow-up text-emerald-700 text-sm"></i>
                <span class="truncate">Impor Data</span>
            </button>
            <button type="button" 
                    onclick="openAddKaryawanModal()" 
                    class="w-full sm:w-auto flex-1 sm:flex-none bg-[#1b5e20] hover:bg-[#144919] text-white px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition shadow-sm cursor-pointer"
                    title="Tambah Karyawan Baru Secara Manual">
                <i class="fa-solid fa-plus text-xs"></i>
                <span class="truncate">Tambah Karyawan</span>
            </button>
            @endcan
        </div>
    </div>

    <!-- Kotak Pencarian & Filter Status Simper (Tanpa Card Wrapper, Format Referensi) -->
    <form method="GET" action="{{ route('hse.employees.index') }}" id="employeeFilterForm" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 sm:gap-3">
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

        <!-- Filter di Kanan (Pada Mobile: Sejajar di Bawah Search Bar) -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2 sm:gap-2.5 w-full sm:w-auto">
            <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center sm:gap-2.5 w-full sm:w-auto">
                <!-- Filter Jenis Personil / Karyawan -->
                <select name="employee_type" 
                        onchange="this.form.submit()" 
                        class="w-full sm:w-auto text-xs rounded-xl border border-slate-200 bg-white px-2.5 sm:px-3 py-2.5 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition truncate">
                    <option value="">Semua Jenis</option>
                    @if(isset($employeeTypes))
                        @foreach($employeeTypes as $t)
                            <option value="{{ $t }}" {{ request('employee_type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    @endif
                </select>

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

                <!-- Filter Tampilan Per Halaman -->
                <select name="per_page" 
                        onchange="this.form.submit()" 
                        class="w-full sm:w-auto text-xs rounded-xl border border-slate-200 bg-white px-2.5 sm:px-3 py-2.5 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition truncate">
                    <option value="20" {{ request('per_page', '20') == '20' ? 'selected' : '' }}>20 / Hal</option>
                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 / Hal</option>
                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>Semua (100)</option>
                </select>

                <!-- Tombol Submit Pencarian -->
                <button type="submit" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition shadow-sm cursor-pointer whitespace-nowrap col-span-2 sm:col-span-1">
                    Cari
                </button>
            </div>

            <!-- Tombol Reset Filter (Sesuai Referensi Merah / Rose Clean Text) -->
            <div id="simperResetContainer">
                @if(request()->anyFilled(['search', 'employee_type', 'driver_status', 'simper_status', 'per_page']))
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
            <table class="w-full min-w-[1180px] text-xs text-left border-collapse">
                <!-- Header Tabel Navy Gelap Solid #06213f -->
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 pl-6 pr-3 text-center w-14 min-w-[56px] whitespace-nowrap">NO</th>
                        <th class="py-3.5 px-4 min-w-[120px] whitespace-nowrap">NIK</th>
                        <th class="py-3.5 px-4 min-w-[190px]">NAMA LENGKAP</th>
                        <th class="py-3.5 px-3 text-center min-w-[90px] whitespace-nowrap">JENIS</th>
                        <th class="py-3.5 px-4 min-w-[140px]">JABATAN</th>
                        <th class="py-3.5 px-4 min-w-[140px]">UNIT / DIVISI</th>
                        <th class="py-3.5 px-3 text-center min-w-[110px] whitespace-nowrap">STATUS DRIVER</th>
                        <th class="py-3.5 px-4 text-center min-w-[150px] whitespace-nowrap">MASA BERLAKU SIMPER</th>
                        <th class="py-3.5 pl-3 pr-6 text-center min-w-[220px] whitespace-nowrap">AKSI</th>
                    </tr>
                </thead>
                <tbody id="simperTableBody" class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($employees as $index => $emp)
                        @php
                            $isValid = $emp->hasValidSimper();
                        @endphp
                        <tr class="simper-row hover:bg-slate-50/70 transition">
                            <!-- Nomor -->
                            <td class="py-3.5 pl-6 pr-3 text-center font-medium text-slate-400 whitespace-nowrap">{{ $employees->firstItem() ? ($employees->firstItem() + $index) : ($index + 1) }}</td>
                            
                            <!-- NIK (Font Bold Hitam Tegas) -->
                            <td class="py-3.5 px-4 font-bold text-slate-900 font-mono text-xs whitespace-nowrap">{{ $emp->nik }}</td>
                            
                            <!-- Nama Lengkap (Avatar + Font Bold Hitam) -->
                            <td class="py-3.5 px-4 font-bold text-slate-900 min-w-[190px]">
                                <div class="flex items-center space-x-2.5">
                                    @if($emp->photo_path)
                                        <img src="{{ $emp->photo_url }}" alt="{{ $emp->name }}" class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-2xs flex-shrink-0 cursor-pointer" onclick='openEditModal(@json($emp))'>
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-emerald-50 border border-emerald-200 text-[#1b5e20] flex items-center justify-center text-xs font-bold uppercase flex-shrink-0">
                                            {{ substr($emp->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <span class="truncate">{{ $emp->name }}</span>
                                </div>
                            </td>

                            <!-- Jenis Personil (Karyawan / Sukon) -->
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                @if($emp->isSukon())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Sukon
                                    </span>
                                @elseif($emp->isKaryawan())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        Karyawan
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $emp->employee_type }}
                                    </span>
                                @endif
                            </td>
                            
                            <!-- Jabatan -->
                            <td class="py-3.5 px-4 text-slate-600 min-w-[140px]">{{ $emp->position ?? '-' }}</td>
                            
                            <!-- Unit / Divisi -->
                            <td class="py-3.5 px-4 text-slate-500 min-w-[140px]">
                                <span class="font-medium text-slate-700">{{ $emp->unit_kerja ?? '-' }}</span>
                                @if($emp->division)
                                    <span class="text-slate-400 block text-[10px]">{{ $emp->division }}</span>
                                @endif
                            </td>
                            
                            <!-- Status Driver -->
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
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
                            
                            <!-- Masa Berlaku SIMPER & Multi-SIMPER Badges -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap min-w-[150px]">
                                @if(!$emp->is_operator)
                                    <span class="text-slate-400 italic text-[11px]">-</span>
                                @elseif($emp->simpers && $emp->simpers->isNotEmpty())
                                    <div class="flex flex-col gap-1 items-center">
                                        @foreach($emp->simpers as $sm)
                                             @php
                                                $smValid = $sm->isValid();
                                                $smCode = $sm->simperType?->code ?: 'SIMPER';
                                                $shortCode = str_replace('SIMPER-', '', $smCode);
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono {{ $smValid ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}" title="{{ $sm->simperType?->name }}: s/d {{ $sm->expired_at ? $sm->expired_at->format('d/m/Y') : '-' }}">
                                                <i class="fa-solid {{ $smValid ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-rose-600' }} mr-1 text-[8px]"></i>
                                                {{ $shortCode }}: {{ $sm->expired_at ? $sm->expired_at->format('d/m/y') : '-' }}
                                            </span>
                                        @endforeach
                                    </div>
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
                            
                            <!-- Aksi (Edit Data Simper, Perpanjang, & Hapus Karyawan) -->
                            <td class="py-3.5 pl-3 pr-6 text-center whitespace-nowrap min-w-[220px]">
                                @if(auth()->user()->can('hse.employees.edit') || auth()->user()->can('hse.employees.delete'))
                                <div class="inline-flex items-center justify-center space-x-1">
                                    @can('hse.employees.edit')
                                    <button onclick='openEditModal(@json($emp))' 
                                            class="inline-flex items-center space-x-1 text-amber-600 hover:text-amber-800 hover:bg-amber-50 px-2 py-1 rounded-lg transition cursor-pointer"
                                            title="Buka Modal Edit Data SIMPER">
                                        <i class="fa-regular fa-pen-to-square text-xs"></i>
                                        <span class="text-xs font-semibold">Edit</span>
                                    </button>
                                    <a href="{{ route('hse.employees.edit', $emp->id) }}" 
                                       class="inline-flex items-center text-slate-400 hover:text-[#1b5e20] hover:bg-emerald-50 px-1.5 py-1 rounded-lg transition"
                                       title="Buka di Halaman Penuh">
                                        <i class="fa-solid fa-up-right-from-square text-[11px]"></i>
                                    </a>
                                    <span class="text-slate-200">|</span>
                                    <button onclick='openRenewModal(@json($emp))' 
                                            class="text-emerald-700 hover:text-emerald-900 font-bold underline text-[11px] px-1 cursor-pointer"
                                            title="Perpanjang Masa Berlaku SIMPER">
                                        Perpanjang
                                    </button>
                                    @endcan

                                    @can('hse.employees.delete')
                                    <span class="text-slate-200">|</span>
                                    <button type="button"
                                            onclick="openDeleteEmployeeModal({{ $emp->id }}, '{{ addslashes($emp->name) }}', '{{ $emp->nik }}')"
                                            class="inline-flex items-center space-x-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-1.5 py-1 rounded-lg transition cursor-pointer"
                                            title="Hapus Data Karyawan / Personil">
                                        <i class="fa-regular fa-trash-can text-xs"></i>
                                        <span class="text-xs font-semibold">Hapus</span>
                                    </button>
                                    @endcan
                                </div>
                                @else
                                <span class="text-xs text-slate-400 italic">Hanya lihat</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr id="serverSimperEmptyRow">
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
                                @if(request('search'))
                                    <p class="text-sm font-semibold text-slate-700">
                                        Data "<span class="text-rose-600 font-bold">{{ request('search') }}</span>" tidak ditemukan di tabel
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">Pastikan ejaan NIK atau nama karyawan yang dicari sudah benar.</p>
                                @else
                                    <p class="text-sm font-semibold text-slate-700">Belum ada data pengemudi atau SIMPER yang sesuai kriteria filter.</p>
                                @endif

                                @if(request()->anyFilled(['search', 'driver_status', 'simper_status']))
                                    <div class="mt-4">
                                        <a href="{{ route('hse.employees.index') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition">
                                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
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

        @if($employees->hasPages())
            <div class="p-4 border-t border-slate-100 bg-white">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</div>



<!-- ========================================================================= -->
<!-- MODAL: TAMBAH KARYAWAN MANUAL -->
<!-- ========================================================================= -->
<div id="addKaryawanModal" data-backdrop="static" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full p-6 sm:p-7 border border-slate-100 flex flex-col max-h-[92vh] overflow-hidden animate-scale-in">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 flex-shrink-0">
            <div>
                <h3 class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-[#1b5e20]"></i>
                    <span>Tambah Data Karyawan / Personil</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Daftarkan personil baru (Internal KMH / Sukon) beserta izin SIMPER pengemudi.</p>
            </div>
            <button type="button" onclick="closeAddKaryawanModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('hse.employees.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 min-h-0 mt-4">
            @csrf

            <div class="overflow-y-auto flex-1 pr-1 pb-2">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    <!-- Left Column: Identitas & Penugasan -->
                    <div class="lg:col-span-5 space-y-3.5">
                        <!-- Foto Profil -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1.5">Foto Profil Karyawan (Opsional)</label>
                            <div class="flex items-center space-x-3 p-2.5 rounded-2xl border border-slate-200 bg-slate-50/50">
                                <div id="addEmpPhotoPreviewContainer" class="w-12 h-12 rounded-full border-2 border-dashed border-slate-300 bg-white flex items-center justify-center overflow-hidden flex-shrink-0 shadow-2xs">
                                    <img id="addEmpPhotoPreview" src="" alt="Pratinjau" class="w-full h-full object-cover hidden">
                                    <i id="addEmpPhotoPlaceholder" class="fa-solid fa-camera text-slate-400 text-sm"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <input type="file" 
                                           name="photo" 
                                           id="addEmpPhotoInput" 
                                           accept="image/jpeg,image/png,image/jpg,image/webp" 
                                           onchange="previewEmployeeImage(this, 'addEmpPhotoPreview', 'addEmpPhotoPlaceholder')"
                                           class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-emerald-50 file:text-[#1b5e20] hover:file:bg-emerald-100 cursor-pointer">
                                    <p class="text-[9px] text-slate-400 mt-0.5">JPG, PNG, WebP (Maks. 10MB)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Jenis Personil -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1">
                                Jenis Personil / Karyawan <span class="text-rose-500">*</span>
                            </label>
                            <select name="employee_type" id="addEmployeeTypeSelect" onchange="toggleCustomEmployeeType(this.value, 'add')" required 
                                    class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] transition">
                                <option value="Karyawan" selected>Karyawan (Internal KMH)</option>
                                <option value="Sukon">Sukon (Subkontraktor)</option>
                                @if(isset($employeeTypes))
                                    @foreach($employeeTypes as $t)
                                        @if($t !== 'Karyawan' && $t !== 'Sukon')
                                            <option value="{{ $t }}">{{ $t }}</option>
                                        @endif
                                    @endforeach
                                @endif
                                <option value="__CUSTOM__">+ Ketik Jenis Baru...</option>
                            </select>
                            <div id="addCustomEmployeeTypeWrapper" class="hidden mt-1.5">
                                <input type="text" name="custom_employee_type" id="addCustomEmployeeTypeInput" placeholder="Ketik jenis personil baru (misal: Vendor, Mitra)..." 
                                       class="w-full text-xs px-3 py-1.5 rounded-xl border border-emerald-300 bg-emerald-50/40 text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20]">
                            </div>
                        </div>

                        <!-- NIK & Nama -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">NIK <span class="text-rose-500">*</span></label>
                                <input type="text" name="nik" required placeholder="KMH-2026-101" 
                                       class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" required placeholder="Nama Lengkap" 
                                       class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20]">
                            </div>
                        </div>

                        <!-- Unit Kerja, Divisi & Jabatan -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">Unit Kerja <span class="text-rose-500">*</span></label>
                                <input type="text" name="unit_kerja" id="addUnitKerjaInput" list="unitKerjaSuggestions" required placeholder="Contoh: HSE, Umum, dll" value="{{ old('unit_kerja', 'Umum') }}" 
                                       class="w-full text-xs px-2.5 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">Divisi <span class="text-rose-500">*</span></label>
                                <input type="text" name="division" required placeholder="Divisi" 
                                       class="w-full text-xs px-2.5 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">Jabatan <span class="text-rose-500">*</span></label>
                                <input type="text" name="position" required placeholder="Jabatan" 
                                       class="w-full text-xs px-2.5 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20]">
                            </div>
                        </div>

                        <!-- Status Operator -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1">Status Operator / Driver <span class="text-rose-500">*</span></label>
                            <select name="is_operator" id="addOperatorSelect" onchange="toggleAddSimperDate(this.value)" required 
                                    class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20]">
                                <option value="1">YA (Aktif Mengemudi)</option>
                                <option value="0">TIDAK (Bukan Driver)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Right Column: SIMPER & Akun Info -->
                    <div class="lg:col-span-7 space-y-3">
                        <div class="p-3 rounded-2xl bg-emerald-50/70 border border-emerald-100 flex items-start gap-2.5">
                            <i class="fa-solid fa-circle-info text-emerald-600 text-xs mt-0.5"></i>
                            <div class="text-[11px] text-emerald-900 leading-relaxed">
                                Akun login dibuat otomatis dengan <strong>Username NIK</strong> dan <strong>Password default: 123456</strong> (Role: Karyawan).
                            </div>
                        </div>

                        <div id="addSimperDateWrapper" class="space-y-2">
                            <div class="flex items-center justify-between pb-1">
                                <div>
                                    <label class="block text-xs font-bold text-slate-800">
                                        Izin SIMPER yang Diberikan <span class="text-rose-500">*</span>
                                    </label>
                                    <p class="text-[10px] text-slate-400">Centang jenis izin & tentukan masa berlakunya</p>
                                </div>
                                <span class="text-[10px] font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-lg">
                                    Multi-SIMPER
                                </span>
                            </div>

                            <input type="hidden" name="simper_expired_at" id="addSimperDateInput">

                            @if(isset($availableSimperTypes) && count($availableSimperTypes) > 0)
                                <div class="space-y-2 max-h-[48vh] overflow-y-auto pr-1">
                                    @foreach($availableSimperTypes as $st)
                                    <div class="p-3 rounded-2xl border border-slate-200 bg-slate-50/70 hover:bg-slate-50 transition">
                                        <div class="flex items-center justify-between gap-2">
                                            <label class="flex items-center space-x-2.5 text-xs font-bold text-slate-800 cursor-pointer flex-1 min-w-0">
                                                <input type="checkbox" 
                                                       name="simper_assignments[{{ $st->id }}][enabled]" 
                                                       value="1" 
                                                       id="addSimperCheck_{{ $st->id }}"
                                                       onchange="toggleAddSimperFields({{ $st->id }})"
                                                       class="w-4 h-4 rounded text-emerald-700 border-slate-300 focus:ring-emerald-500 flex-shrink-0">
                                                <span class="truncate">{{ $st->name }} ({{ $st->code }})</span>
                                            </label>
                                            <span class="text-[9px] font-mono px-2 py-0.5 rounded-md bg-slate-200 text-slate-700 font-bold uppercase flex-shrink-0" title="{{ $st->category_scope_display }}">
                                                {{ $st->category_scope_display }}
                                            </span>
                                        </div>
                                        <div id="addSimperFields_{{ $st->id }}" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2 pt-2 border-t border-slate-200/60">
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Nomor Registrasi SIMPER</label>
                                                <input type="text" 
                                                       name="simper_assignments[{{ $st->id }}][simper_number]" 
                                                       id="addSimperNum_{{ $st->id }}"
                                                       placeholder="Contoh: SMP-LV-001" 
                                                       class="w-full text-xs px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Masa Berlaku SIMPER <span class="text-rose-500">*</span></label>
                                                <input type="date" 
                                                       name="simper_assignments[{{ $st->id }}][expired_at]" 
                                                       id="addSimperExp_{{ $st->id }}"
                                                       onchange="syncAddPrimarySimperDate()"
                                                       class="w-full text-xs px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold">
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div>
                                    <input type="date" name="simper_expired_at" id="addSimperDateLegacy" 
                                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] transition">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5 flex-shrink-0">
                <button type="button" onclick="closeAddKaryawanModal()" class="px-4 py-2 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs transition shadow-sm">
                    Simpan Karyawan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: IMPOR DATA KARYAWAN EXCEL -->
<!-- ========================================================================= -->
<div id="importKaryawanModal" data-backdrop="static" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-slate-100 overflow-hidden animate-scale-in">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-file-excel text-emerald-600"></i>
                    <span>Impor Data Karyawan via Excel</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Unggah data karyawan secara massal menggunakan file Excel (.xlsx) atau CSV (.csv).</p>
            </div>
            <button type="button" onclick="closeImportKaryawanModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Banner Unduh Template -->
        <div class="mt-4 bg-emerald-50/70 border border-emerald-200/80 rounded-2xl p-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fa-regular fa-file-excel"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-emerald-950">Unduh Format Template</p>
                    <p class="text-[11px] text-emerald-700">Format kolom resmi agar data dapat diproses otomatis tanpa kendala.</p>
                </div>
            </div>
            <a href="{{ route('hse.employees.import_template') }}" 
               class="px-3 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 flex-shrink-0 shadow-xs"
               title="Unduh Template Excel">
                <i class="fa-solid fa-download text-[11px]"></i>
                <span>Unduh</span>
            </a>
        </div>

        <div class="mt-3.5 p-3 rounded-2xl bg-slate-50 border border-slate-100 flex items-start gap-2.5">
            <i class="fa-solid fa-shield-halved text-emerald-600 text-xs mt-0.5"></i>
            <div class="text-[11px] text-slate-600 leading-relaxed">
                Setiap data karyawan yang diimpor otomatis dibuatkan akun login sistem dengan <strong>Username NIK</strong>, <strong>Role Karyawan</strong>, dan <strong>Password default: 123456</strong>.
            </div>
        </div>

        <form action="{{ route('hse.employees.import') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf

            <!-- Upload Box -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-2">Pilih File Spreadsheet <span class="text-rose-500">*</span></label>
                <div class="border-2 border-dashed border-slate-300 hover:border-emerald-600 rounded-2xl p-6 text-center bg-slate-50/60 hover:bg-white transition cursor-pointer relative"
                     onclick="document.getElementById('importFileInput').click()">
                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-emerald-600 mb-2 block"></i>
                    <p class="text-xs font-bold text-slate-800" id="importFileName">Klik untuk memilih file spreadsheet</p>
                    <p class="text-[11px] text-slate-400 mt-1">Mendukung format Microsoft Excel (.xlsx) atau file teks CSV (.csv)</p>
                    <input type="file" 
                           name="file" 
                           id="importFileInput" 
                           accept=".xlsx,.csv" 
                           required 
                           class="hidden" 
                           onchange="handleImportFileChange(this)">
                </div>
            </div>

            <!-- Petunjuk Ringkas -->
            <div class="bg-slate-50 border border-slate-200/70 rounded-xl p-3 text-[11px] text-slate-600 space-y-1">
                <p class="font-bold text-slate-700 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info text-emerald-600"></i>
                    <span>Informasi Pemrosesan:</span>
                </p>
                <ul class="list-disc list-inside text-[11px] text-slate-500 space-y-0.5 pl-1">
                    <li>Jika NIK sudah ada di database, data karyawan akan <strong>diperbarui (update)</strong>.</li>
                    <li>Jika NIK baru, sistem otomatis <strong>menambahkan data & membuat akun login karyawan</strong>.</li>
                    <li>Pastikan kolom NIK dan Nama tidak kosong pada setiap baris data.</li>
                </ul>
            </div>

            <!-- Tombol Footer -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeImportKaryawanModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs transition shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-file-import text-xs"></i>
                    <span>Unggah & Proses Impor</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Dialog Perpanjang SIMPER (Clean White Card dengan Dukungan Multi-SIMPER) -->
<div id="renewModal" data-backdrop="static" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-slate-100 overflow-hidden animate-scale-in max-h-[92vh] flex flex-col">
        <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 flex-shrink-0">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-sm border border-emerald-100">
                        <i class="fa-solid fa-id-card-clip"></i>
                    </div>
                    <span>Perpanjang Masa Berlaku SIMPER</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Pilih jenis SIMPER personel dan tentukan tanggal masa berlaku operasional baru.</p>
            </div>
            <button type="button" onclick="closeRenewModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="renewForm" method="POST" action="" class="mt-4 space-y-4 overflow-y-auto pr-1">
            @csrf
            <!-- Identitas Personel (Karyawan / Sukon) -->
            <div class="bg-slate-50/90 p-3.5 rounded-2xl border border-slate-200/80 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase tracking-wider">Personel / Operator:</span>
                    <strong id="renewName" class="block text-sm text-slate-900 font-extrabold mt-0.5 truncate"></strong>
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <span id="renewNik" class="text-[11px] font-mono font-bold text-slate-600 bg-white px-2 py-0.5 rounded border border-slate-200"></span>
                        <span id="renewUnitDept" class="text-[11px] text-slate-500"></span>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                    <span id="renewCategoryBadge" class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-slate-200/70 text-slate-700">
                        Karyawan
                    </span>
                    <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-100 text-[#1b5e20] border border-emerald-200/80">
                        Driver / Operator
                    </span>
                </div>
            </div>

            <!-- Status Driver (Hidden input default 1) -->
            <input type="hidden" name="is_operator" id="renewOperator" value="1">

            <!-- Ringkasan SIMPER yang Sedang Dimiliki Saat Ini -->
            <div class="p-3.5 rounded-2xl bg-indigo-50/30 border border-indigo-100/70 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-indigo-600 text-xs"></i>
                        <span>SIMPER yang Sedang Dimiliki Saat Ini:</span>
                    </span>
                    <span id="renewSimperBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-white text-indigo-700 border border-indigo-200 font-mono"></span>
                </div>
                <div id="renewCurrentSimperCards" class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <!-- Dinamis via JavaScript -->
                </div>
            </div>

            <!-- Dropdown Pilihan Jenis SIMPER yang Diperpanjang -->
            <div>
                <label for="renewSimperTypeSelect" class="block text-xs font-bold text-slate-800 mb-1.5 flex items-center justify-between">
                    <span>Pilih SIMPER yang Ingin Diperpanjang <span class="text-rose-500">*</span></span>
                    <span class="text-[10px] text-slate-400 font-normal">Pilih satu atau semua</span>
                </label>
                <select name="renew_simper_type_id" id="renewSimperTypeSelect" onchange="onRenewSimperTypeChange(this.value)" required 
                        class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition shadow-xs">
                    <!-- Dinamis via JavaScript -->
                </select>
            </div>

            <!-- Detail SIMPER Terpilih (Live Information Box) -->
            <div id="renewSelectedDetailBox" class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-xs space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] text-slate-500">Masa Berlaku Saat Ini:</span>
                    <strong id="renewCurrentExpText" class="text-slate-800 font-mono text-[11px]">-</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] text-slate-500">Status Izin:</span>
                    <span id="renewCurrentStatusBadge" class="text-[10px] font-bold px-2 py-0.2 rounded font-mono">-</span>
                </div>
            </div>

            <!-- Input Tanggal Kedaluwarsa Baru -->
            <div>
                <label for="renewDate" class="block text-xs font-bold text-slate-800 mb-1.5 flex items-center justify-between">
                    <span>Masa Berlaku Baru (Hasil Perpanjangan) <span class="text-rose-500">*</span></span>
                    <span class="text-[10px] text-emerald-700 font-semibold" id="renewRecommendLabel">Rekomendasi: +1 Tahun</span>
                </label>
                <input type="date" name="simper_expired_at" id="renewDate" required 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 font-bold font-mono focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition shadow-xs">
                
                <!-- Quick Preset Buttons (+6 Bulan, +1 Tahun, +2 Tahun, +3 Tahun) -->
                <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mr-1">Pintas Cepat:</span>
                    <button type="button" onclick="setRenewDateOffset(6)" class="text-[11px] px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold transition cursor-pointer">
                        +6 Bulan
                    </button>
                    <button type="button" onclick="setRenewDateOffset(12)" class="text-[11px] px-2.5 py-1 rounded-lg border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-[#1b5e20] font-bold transition cursor-pointer">
                        +1 Tahun (Standar)
                    </button>
                    <button type="button" onclick="setRenewDateOffset(24)" class="text-[11px] px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold transition cursor-pointer">
                        +2 Tahun
                    </button>
                    <button type="button" onclick="setRenewDateOffset(36)" class="text-[11px] px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold transition cursor-pointer">
                        +3 Tahun
                    </button>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-2.5 mt-5">
                <button type="button" onclick="closeRenewModal()" 
                        class="px-4 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-5 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs transition shadow-md shadow-emerald-700/20 inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    <span>Simpan Perpanjangan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Dialog Edit Data Simper Karyawan -->
<div id="editModal" data-backdrop="static" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full p-6 sm:p-7 border border-slate-100 flex flex-col max-h-[92vh] overflow-hidden animate-scale-in">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 flex-shrink-0">
            <div>
                <h3 class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-id-card-clip text-emerald-700"></i>
                    <span>Kelola SIMPER & Personil</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Kelola tipe personil, status driver, dan izin operasional SIMPER.</p>
            </div>
            <div class="flex items-center gap-2">
                <a id="editFullPageLink" href="#" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-[#1b5e20] text-xs font-bold transition border border-emerald-200"
                   title="Buka form di halaman tersendiri yang lebih leluasa">
                    <i class="fa-solid fa-up-right-from-square text-[10px]"></i>
                    <span class="hidden sm:inline">Halaman Penuh</span>
                </a>
                <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
        </div>

        <form id="editForm" method="POST" action="" enctype="multipart/form-data" class="flex flex-col flex-1 min-h-0 mt-4">
            @csrf

            <div class="overflow-y-auto flex-1 pr-1 pb-2">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    <!-- Left Column: Profil & Driver Status -->
                    <div class="lg:col-span-5 space-y-3.5">
                        <!-- Foto Profil -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1.5">Foto Profil Karyawan</label>
                            <div class="flex items-center space-x-3 p-2.5 rounded-2xl border border-slate-200 bg-slate-50/50">
                                <div id="editEmpPhotoPreviewContainer" class="w-12 h-12 rounded-full border border-slate-200 bg-white flex items-center justify-center overflow-hidden flex-shrink-0 shadow-2xs">
                                    <img id="editEmpPhotoPreview" src="" alt="Foto Karyawan" class="w-full h-full object-cover hidden">
                                    <i id="editEmpPhotoPlaceholder" class="fa-solid fa-user text-slate-400 text-sm"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <input type="file" 
                                           name="photo" 
                                           id="editEmpPhotoInput" 
                                           accept="image/jpeg,image/png,image/jpg,image/webp" 
                                           onchange="previewEmployeeImage(this, 'editEmpPhotoPreview', 'editEmpPhotoPlaceholder')"
                                           class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-emerald-50 file:text-[#1b5e20] hover:file:bg-emerald-100 cursor-pointer">
                                    <p class="text-[9px] text-slate-400 mt-0.5">JPG, PNG, WebP (Maks. 10MB)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Profil Read-Only Card -->
                        <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-3.5">
                            <div class="flex items-center justify-between mb-2 pb-1.5 border-b border-slate-200/60">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Identitas Personil</span>
                                <span class="text-[9px] font-semibold text-slate-500 bg-slate-200/70 px-2 py-0.5 rounded-full inline-flex items-center">
                                    <i class="fa-solid fa-lock text-[8px] mr-1 text-slate-400"></i>Master HR
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2.5 text-xs">
                                <div>
                                    <span class="text-slate-400 block text-[10px]">NIK:</span>
                                    <span id="editNikDisplay" class="font-bold text-slate-800 font-mono text-[11px]"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px]">Nama Lengkap:</span>
                                    <span id="editNameDisplay" class="font-bold text-slate-900 text-[11px] truncate block"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px]">Unit Kerja:</span>
                                    <span id="editUnitKerjaDisplay" class="font-semibold text-slate-700 text-[11px]"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px]">Divisi / Jabatan:</span>
                                    <span id="editDivisionPositionDisplay" class="font-semibold text-slate-700 text-[11px] truncate block"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Jenis Personil -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1">
                                Jenis Personil / Karyawan <span class="text-rose-500">*</span>
                            </label>
                            <select name="employee_type" id="editEmployeeType" onchange="toggleCustomEmployeeType(this.value, 'edit')" required 
                                    class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] transition">
                                <option value="Karyawan">Karyawan (Internal KMH)</option>
                                <option value="Sukon">Sukon (Subkontraktor)</option>
                                @if(isset($employeeTypes))
                                    @foreach($employeeTypes as $t)
                                        @if($t !== 'Karyawan' && $t !== 'Sukon')
                                            <option value="{{ $t }}">{{ $t }}</option>
                                        @endif
                                    @endforeach
                                @endif
                                <option value="__CUSTOM__">+ Ketik Jenis Baru...</option>
                            </select>
                            <div id="editCustomEmployeeTypeWrapper" class="hidden mt-1.5">
                                <input type="text" name="custom_employee_type" id="editCustomEmployeeTypeInput" placeholder="Ketik jenis personil baru..." 
                                       class="w-full text-xs px-3 py-1.5 rounded-xl border border-emerald-300 bg-emerald-50/40 text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20]">
                            </div>
                        </div>

                        <!-- Unit Kerja (Fleksibel) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1">
                                Unit Kerja <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="unit_kerja" id="editUnitKerjaInput" list="unitKerjaSuggestions" required placeholder="Contoh: HSE, Umum, dll" 
                                   class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] transition">
                        </div>

                        <!-- Divisi & Jabatan -->
                        <div class="grid grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">
                                    Divisi <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="division" id="editDivisionInput" required placeholder="Contoh: Operasi, Private Guard" 
                                       class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">
                                    Posisi / Jabatan <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="position" id="editPositionInput" required placeholder="Contoh: Anggota, Driver, Staff" 
                                       class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] transition">
                            </div>
                        </div>

                        <!-- Status Operator -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1">Status Operator / Driver <span class="text-rose-500">*</span></label>
                            <select name="is_operator" id="editOperator" onchange="toggleSimperInput('edit')" required 
                                    class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] transition">
                                <option value="1">YA (Aktif Mengemudi)</option>
                                <option value="0">TIDAK (Bukan Driver)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Right Column: Daftar Izin SIMPER -->
                    <div class="lg:col-span-7 space-y-2.5">
                        <div class="flex items-center justify-between pb-1">
                            <div>
                                <label class="block text-xs font-bold text-slate-800">
                                    Izin SIMPER yang Dimiliki <span class="text-rose-500">*</span>
                                </label>
                                <p class="text-[10px] text-slate-400">Centang jenis SIMPER yang diizinkan dan tentukan masa berlakunya</p>
                            </div>
                            <span class="text-[10px] font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-lg">
                                Multi-SIMPER
                            </span>
                        </div>

                        <input type="hidden" name="simper_expired_at" id="editSimperDate">

                        <div id="editSimperWrapper" class="space-y-2">
                            @if(isset($availableSimperTypes) && count($availableSimperTypes) > 0)
                                <div class="space-y-2 max-h-[50vh] overflow-y-auto pr-1">
                                    @foreach($availableSimperTypes as $st)
                                    <div class="p-3 rounded-2xl border border-slate-200 bg-slate-50/70 hover:bg-slate-50 transition">
                                        <div class="flex items-center justify-between gap-2">
                                            <label class="flex items-center space-x-2.5 text-xs font-bold text-slate-800 cursor-pointer flex-1 min-w-0">
                                                <input type="checkbox" 
                                                       name="simper_assignments[{{ $st->id }}][enabled]" 
                                                       value="1" 
                                                       id="editSimperCheck_{{ $st->id }}"
                                                       onchange="toggleEditSimperFields({{ $st->id }})"
                                                       class="w-4 h-4 rounded text-emerald-700 border-slate-300 focus:ring-emerald-500 flex-shrink-0">
                                                <span class="truncate">{{ $st->name }} ({{ $st->code }})</span>
                                            </label>
                                            <span class="text-[9px] font-mono px-2 py-0.5 rounded-md bg-slate-200 text-slate-700 font-bold uppercase flex-shrink-0" title="{{ $st->category_scope_display }}">
                                                {{ $st->category_scope_display }}
                                            </span>
                                        </div>
                                        <div id="editSimperFields_{{ $st->id }}" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2 pt-2 border-t border-slate-200/60">
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Nomor Registrasi SIMPER</label>
                                                <input type="text" 
                                                       name="simper_assignments[{{ $st->id }}][simper_number]" 
                                                       id="editSimperNum_{{ $st->id }}"
                                                       placeholder="Contoh: SMP-LV-001" 
                                                       class="w-full text-xs px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Masa Berlaku SIMPER</label>
                                                <input type="date" 
                                                       name="simper_assignments[{{ $st->id }}][expired_at]" 
                                                       id="editSimperExp_{{ $st->id }}"
                                                       onchange="syncEditPrimarySimperDate()"
                                                       class="w-full text-xs px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold">
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div>
                                    <input type="date" name="simper_expired_at" id="editSimperDateLegacy" 
                                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] transition">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Tombol -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-2.5 flex-shrink-0">
                <button type="button" onclick="closeEditModal()" 
                        class="px-5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-5 py-2 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs transition shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Datalist Saran Unit Kerja Fleksibel -->
<datalist id="unitKerjaSuggestions">
    @php
        $defaultUnits = ['Umum', 'HSE', 'QMA', 'Pembangkit', 'Bendungan', 'Teknik', 'Logistik', 'Security'];
        $allUnits = collect($defaultUnits);
        if (isset($unitKerjaList)) {
            $allUnits = $allUnits->merge($unitKerjaList)->filter()->unique()->values();
        }
    @endphp
    @foreach($allUnits as $unit)
        <option value="{{ $unit }}">
    @endforeach
</datalist>

<!-- ========================================================================= -->
<!-- MODAL POPUP: KONFIRMASI HAPUS DATA KARYAWAN                               -->
<!-- ========================================================================= -->
<div id="deleteEmployeeModal" 
     data-backdrop="static"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
    <div class="bg-white rounded-3xl shadow-2xl max-w-sm sm:max-w-md w-full p-6 sm:p-7 border border-slate-100 transform transition-all text-center animate-scale-in">
        <!-- Icon Peringatan Sampah Merah -->
        <div class="w-14 h-14 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4 text-2xl shadow-sm">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h3 class="text-lg font-bold text-slate-900 tracking-tight">
            Konfirmasi Hapus Karyawan
        </h3>

        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
            Apakah Anda yakin ingin menghapus data karyawan / personil <span id="deleteEmployeeName" class="font-bold text-slate-900"></span> (<span id="deleteEmployeeNik" class="font-bold font-mono text-slate-800 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200"></span>)?
        </p>

        <p class="text-[11px] text-rose-600 mt-3 bg-rose-50/70 border border-rose-100 py-2 px-3 rounded-xl inline-flex items-center text-left">
            <i class="fa-solid fa-circle-info mr-2 flex-shrink-0 text-xs"></i>
            <span>Data personil beserta izin SIMPER dan akun login terkait akan dihapus permanen.</span>
        </p>

        <!-- Tombol Aksi (Batal & Hapus Data) -->
        <div class="flex items-center space-x-3 mt-6">
            <button type="button" 
                    onclick="closeDeleteEmployeeModal()" 
                    class="flex-1 py-2.5 px-4 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition cursor-pointer">
                Batal
            </button>
            <form id="deleteEmployeeForm" action="" method="POST" class="flex-1">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition shadow-sm flex items-center justify-center space-x-1.5 cursor-pointer">
                    <i class="fa-regular fa-trash-can text-xs"></i>
                    <span>Ya, Hapus Data</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (DATA SIMPER)                                -->
<!-- ========================================================================= -->
<div id="guideModal" 
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
                        <i class="fa-solid fa-id-badge"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Kelola Status Driver & SIMPER</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Mengatur status kelayakan driver dan memperbarui tanggal SIMPER. Pendaftaran dan master data profil karyawan dikelola terpusat oleh Super Admin.
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

    // Logika Jenis Personil (Karyawan / Sukon / Custom)
    function toggleCustomEmployeeType(val, mode) {
        const wrapper = document.getElementById(mode === 'edit' ? 'editCustomEmployeeTypeWrapper' : 'addCustomEmployeeTypeWrapper');
        const input = document.getElementById(mode === 'edit' ? 'editCustomEmployeeTypeInput' : 'addCustomEmployeeTypeInput');
        if (wrapper) {
            if (val === '__CUSTOM__') {
                wrapper.classList.remove('hidden');
                if (input) {
                    input.required = true;
                    input.focus();
                }
            } else {
                wrapper.classList.add('hidden');
                if (input) {
                    input.required = false;
                    input.value = '';
                }
            }
        }
    }

    // Logika Penguncian Input SIMPER jika Bukan Driver
    function toggleSimperInput(type) {
        let opSelect, dateInput;
        if (type === 'edit') {
            opSelect = document.getElementById('editOperator');
            dateInput = document.getElementById('editSimperDate');
            const wrapper = document.getElementById('editSimperWrapper');
            if (wrapper) {
                wrapper.style.display = opSelect.value === '0' ? 'none' : 'block';
            }
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

    // Toggle Field Multi-SIMPER Tambah
    function toggleAddSimperFields(id) {
        const check = document.getElementById(`addSimperCheck_${id}`);
        const fields = document.getElementById(`addSimperFields_${id}`);
        if (check && fields) {
            if (check.checked) {
                fields.classList.remove('hidden');
            } else {
                fields.classList.add('hidden');
                const expInp = document.getElementById(`addSimperExp_${id}`);
                if (expInp) expInp.value = '';
            }
        }
        syncAddPrimarySimperDate();
    }

    function syncAddPrimarySimperDate() {
        let maxDate = '';
        const expInputs = document.querySelectorAll('[id^="addSimperExp_"]');
        expInputs.forEach(inp => {
            const id = inp.id.replace('addSimperExp_', '');
            const check = document.getElementById(`addSimperCheck_${id}`);
            if (check && check.checked && inp.value) {
                if (!maxDate || inp.value > maxDate) {
                    maxDate = inp.value;
                }
            }
        });
        const primaryInp = document.getElementById('addSimperDateInput');
        if (primaryInp) primaryInp.value = maxDate;
    }

    // Toggle Field Multi-SIMPER Edit
    function toggleEditSimperFields(id) {
        const check = document.getElementById(`editSimperCheck_${id}`);
        const fields = document.getElementById(`editSimperFields_${id}`);
        const numInp = document.getElementById(`editSimperNum_${id}`);
        const expInp = document.getElementById(`editSimperExp_${id}`);
        if (check && fields) {
            if (check.checked) {
                fields.classList.remove('hidden');
                if (numInp) numInp.disabled = false;
                if (expInp) expInp.disabled = false;
            } else {
                fields.classList.add('hidden');
                if (numInp) numInp.disabled = true;
                if (expInp) {
                    expInp.disabled = true;
                    expInp.value = '';
                }
            }
        }
        syncEditPrimarySimperDate();
    }

    function syncEditPrimarySimperDate() {
        let maxDate = '';
        const expInputs = document.querySelectorAll('[id^="editSimperExp_"]');
        expInputs.forEach(inp => {
            const id = inp.id.replace('editSimperExp_', '');
            const check = document.getElementById(`editSimperCheck_${id}`);
            if (check && check.checked && inp.value) {
                if (!maxDate || inp.value > maxDate) {
                    maxDate = inp.value;
                }
            }
        });
        const primaryInp = document.getElementById('editSimperDate');
        if (primaryInp) primaryInp.value = maxDate;
    }

    function previewEmployeeImage(input, imgId, placeholderId) {
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

    function openEditModal(emp) {
        document.getElementById('editNikDisplay').textContent = emp.nik || '-';
        document.getElementById('editNameDisplay').textContent = emp.name || '-';
        document.getElementById('editUnitKerjaDisplay').textContent = emp.unit_kerja || '-';
        const editUnitKerjaInput = document.getElementById('editUnitKerjaInput');
        if (editUnitKerjaInput) {
            editUnitKerjaInput.value = emp.unit_kerja || '';
        }
        const editDivisionInput = document.getElementById('editDivisionInput');
        if (editDivisionInput) {
            editDivisionInput.value = emp.division || '';
        }
        const editPositionInput = document.getElementById('editPositionInput');
        if (editPositionInput) {
            editPositionInput.value = emp.position || '';
        }
        document.getElementById('editDivisionPositionDisplay').textContent = (emp.division || '-') + ' / ' + (emp.position || '-');
        document.getElementById('editOperator').value = emp.is_operator ? '1' : '0';
        
        const editPhotoPreview = document.getElementById('editEmpPhotoPreview');
        const editPhotoPlaceholder = document.getElementById('editEmpPhotoPlaceholder');
        const editPhotoInput = document.getElementById('editEmpPhotoInput');
        if (editPhotoInput) editPhotoInput.value = '';
        if (emp.photo_url) {
            if (editPhotoPreview) {
                editPhotoPreview.src = emp.photo_url;
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

        let expDate = '';
        if (emp.simper_expired_at) {
            expDate = emp.simper_expired_at.substring(0, 10);
        }
        document.getElementById('editSimperDate').value = expDate;

        // Reset semua checklist SIMPER di editModal
        document.querySelectorAll('[id^="editSimperCheck_"]').forEach(cb => {
            cb.checked = false;
            const id = cb.id.replace('editSimperCheck_', '');
            const fields = document.getElementById(`editSimperFields_${id}`);
            if (fields) fields.classList.add('hidden');
            const num = document.getElementById(`editSimperNum_${id}`);
            if (num) { num.value = ''; num.disabled = true; }
            const exp = document.getElementById(`editSimperExp_${id}`);
            if (exp) { exp.value = ''; exp.disabled = true; }
        });

        // Isi checklist SIMPER dari data emp.simpers
        if (emp.simpers && emp.simpers.length > 0) {
            emp.simpers.forEach(s => {
                const cb = document.getElementById(`editSimperCheck_${s.simper_type_id}`);
                const fields = document.getElementById(`editSimperFields_${s.simper_type_id}`);
                const num = document.getElementById(`editSimperNum_${s.simper_type_id}`);
                const exp = document.getElementById(`editSimperExp_${s.simper_type_id}`);
                if (cb) {
                    cb.checked = true;
                    if (fields) fields.classList.remove('hidden');
                    if (num) { num.value = s.simper_number || ''; num.disabled = false; }
                    if (exp && s.expired_at) { exp.value = s.expired_at.substring(0, 10); exp.disabled = false; }
                }
            });
        } else if (emp.is_operator && expDate) {
            const firstCb = document.querySelector('[id^="editSimperCheck_"]');
            if (firstCb) {
                firstCb.checked = true;
                const id = firstCb.id.replace('editSimperCheck_', '');
                const fields = document.getElementById(`editSimperFields_${id}`);
                if (fields) fields.classList.remove('hidden');
                const num = document.getElementById(`editSimperNum_${id}`);
                if (num) num.disabled = false;
                const exp = document.getElementById(`editSimperExp_${id}`);
                if (exp) { exp.value = expDate; exp.disabled = false; }
            }
        }
        syncEditPrimarySimperDate();
        
        // Set jenis personil
        const editTypeSelect = document.getElementById('editEmployeeType');
        const editCustomWrapper = document.getElementById('editCustomEmployeeTypeWrapper');
        const editCustomInput = document.getElementById('editCustomEmployeeTypeInput');
        const empType = emp.employee_type || 'Karyawan';
        let found = false;
        if (editTypeSelect) {
            for (let i = 0; i < editTypeSelect.options.length; i++) {
                if (editTypeSelect.options[i].value === empType) {
                    editTypeSelect.value = empType;
                    found = true;
                    break;
                }
            }
            if (!found) {
                editTypeSelect.value = '__CUSTOM__';
                if (editCustomWrapper) editCustomWrapper.classList.remove('hidden');
                if (editCustomInput) {
                    editCustomInput.required = true;
                    editCustomInput.value = empType;
                }
            } else {
                if (editCustomWrapper) editCustomWrapper.classList.add('hidden');
                if (editCustomInput) {
                    editCustomInput.required = false;
                    editCustomInput.value = '';
                }
            }
        }
        
        // Cek status driver untuk mengunci/membuka input SIMPER
        toggleSimperInput('edit');
        
        const fullPageLink = document.getElementById('editFullPageLink');
        if (fullPageLink) {
            fullPageLink.href = `/hse/employees/${emp.id}/edit`;
        }
        
        document.getElementById('editForm').action = `/hse/employees/${emp.id}`;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        const modal = document.getElementById('editModal');
        if (modal) modal.classList.add('hidden');
    }

    const allMasterSimperTypes = @json($availableSimperTypes ?? []);
    let currentRenewEmployee = null;

    function openRenewModal(empOrId, legacyName, legacyDate, legacyIsOp) {
        let emp = null;
        if (typeof empOrId === 'object' && empOrId !== null) {
            emp = empOrId;
        } else {
            emp = {
                id: empOrId,
                name: legacyName || '',
                nik: '',
                unit_kerja: '',
                division: '',
                employee_type: 'Karyawan',
                is_operator: legacyIsOp ? 1 : 0,
                simper_expired_at: legacyDate || '',
                simpers: []
            };
        }
        currentRenewEmployee = emp;

        // 1. Tampilkan Identitas Personel (Karyawan / Sukon)
        document.getElementById('renewName').textContent = emp.name || '-';
        document.getElementById('renewNik').textContent = emp.nik ? `NIK: ${emp.nik}` : 'Tanpa NIK';
        
        const unitDeptText = [emp.unit_kerja, emp.division].filter(Boolean).join(' • ');
        document.getElementById('renewUnitDept').textContent = unitDeptText ? `(${unitDeptText})` : '';

        const catBadge = document.getElementById('renewCategoryBadge');
        const empType = (emp.employee_type || 'Karyawan').trim();
        const isSukon = empType.toLowerCase().includes('sukon');
        catBadge.textContent = isSukon ? 'Sukon (Subkontraktor)' : 'Karyawan PT KMH';
        catBadge.className = isSukon 
            ? 'px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200' 
            : 'px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-slate-200/80 text-slate-700';

        document.getElementById('renewOperator').value = '1';

        // 2. Baca Seluruh SIMPER yang Sedang Dimiliki Karyawan / Sukon
        let simpers = Array.isArray(emp.simpers) ? [...emp.simpers] : [];
        
        // Fallback jika belum tercatat di employee_simpers namun memiliki simper_expired_at di tabel utama
        if (simpers.length === 0 && emp.simper_expired_at) {
            const defaultMasterLv = allMasterSimperTypes.find(t => t.code === 'SIMPER-LV') || allMasterSimperTypes[0];
            if (defaultMasterLv) {
                simpers.push({
                    id: null,
                    employee_id: emp.id,
                    simper_type_id: defaultMasterLv.id,
                    simper_number: 'SMP-' + String(emp.id).padStart(4, '0'),
                    expired_at: emp.simper_expired_at,
                    status: 'active',
                    simper_type: defaultMasterLv
                });
            }
        }

        const simperCount = simpers.length;
        const badgeEl = document.getElementById('renewSimperBadge');
        const cardsContainer = document.getElementById('renewCurrentSimperCards');
        cardsContainer.innerHTML = '';

        const todayStr = new Date().toISOString().substring(0, 10);
        const todayDate = new Date(todayStr);

        if (simperCount > 0) {
            badgeEl.textContent = `${simperCount} SIMPER Aktif`;
            badgeEl.className = 'text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono';

            simpers.forEach(s => {
                const st = s.simper_type || s.simperType || allMasterSimperTypes.find(t => t.id === s.simper_type_id) || {};
                const typeName = st.name || `SIMPER ID #${s.simper_type_id}`;
                const typeCode = st.code || '';
                const expDateStr = s.expired_at ? s.expired_at.substring(0, 10) : '';
                
                let statusLabel = 'Belum Diatur';
                let statusClass = 'bg-slate-100 text-slate-600 border-slate-200';
                let iconClass = 'fa-regular fa-clock';

                if (expDateStr) {
                    const expDate = new Date(expDateStr);
                    const diffDays = Math.ceil((expDate - todayDate) / (1000 * 60 * 60 * 24));
                    if (diffDays < 0) {
                        statusLabel = `Kedaluwarsa (${Math.abs(diffDays)} hari lalu)`;
                        statusClass = 'bg-rose-50 text-rose-700 border-rose-200';
                        iconClass = 'fa-solid fa-circle-xmark text-rose-600';
                    } else if (diffDays <= 30) {
                        statusLabel = `Segera Habis (Sisa ${diffDays} hari)`;
                        statusClass = 'bg-amber-50 text-amber-700 border-amber-200';
                        iconClass = 'fa-solid fa-hourglass-half text-amber-600';
                    } else {
                        statusLabel = `Aktif (s/d ${expDateStr})`;
                        statusClass = 'bg-emerald-50 text-emerald-800 border border-emerald-200';
                        iconClass = 'fa-solid fa-circle-check text-emerald-600';
                    }
                }

                const card = document.createElement('div');
                card.className = 'p-2.5 rounded-xl border bg-white shadow-2xs flex flex-col justify-between border-slate-200/80';
                card.innerHTML = `
                    <div class="flex items-center justify-between">
                        <strong class="font-extrabold text-slate-800 text-xs">${typeName}</strong>
                        ${typeCode ? `<span class="font-mono text-[9px] px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded">${typeCode}</span>` : ''}
                    </div>
                    <div class="mt-1.5 flex items-center justify-between text-[11px]">
                        <span class="text-slate-400 font-mono text-[10px]">${s.simper_number || '-'}</span>
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded ${statusClass}">
                            <i class="${iconClass} text-[9px]"></i>
                            <span>${statusLabel}</span>
                        </span>
                    </div>
                `;
                cardsContainer.appendChild(card);
            });
        } else {
            badgeEl.textContent = 'Belum Ada SIMPER';
            badgeEl.className = 'text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200 font-mono';
            
            const emptyNotice = document.createElement('div');
            emptyNotice.className = 'col-span-full p-3 rounded-xl bg-slate-100/70 border border-slate-200 text-center text-xs text-slate-500 italic';
            emptyNotice.innerHTML = '<i class="fa-solid fa-circle-info mr-1 text-slate-400"></i> Personel belum memiliki izin SIMPER terdaftar. Silakan pilih jenis SIMPER di bawah untuk menerbitkan perpanjangan.';
            cardsContainer.appendChild(emptyNotice);
        }

        // 3. Bangun Dropdown Pilihan SIMPER
        const select = document.getElementById('renewSimperTypeSelect');
        select.innerHTML = '';

        const ownedTypeIds = simpers.map(s => Number(s.simper_type_id)).filter(Boolean);

        // Opsi A: Perpanjang Semua (jika punya lebih dari 1 SIMPER)
        if (simperCount > 1) {
            const allOpt = document.createElement('option');
            allOpt.value = 'all';
            allOpt.textContent = `★ Perpanjang Semua SIMPER Sekaligus (${simperCount} Izin Aktif)`;
            allOpt.dataset.type = 'all';
            select.appendChild(allOpt);
        }

        // Opsi B: Grup SIMPER yang Sedang Dimiliki
        if (simperCount > 0) {
            const ownedGroup = document.createElement('optgroup');
            ownedGroup.label = '── SIMPER yang Sedang Dimiliki ──';
            
            simpers.forEach(s => {
                const st = s.simper_type || s.simperType || allMasterSimperTypes.find(t => t.id === s.simper_type_id) || {};
                const typeName = st.name || `SIMPER #${s.simper_type_id}`;
                const expDateStr = s.expired_at ? s.expired_at.substring(0, 10) : 'Belum diatur';
                
                const opt = document.createElement('option');
                opt.value = s.simper_type_id;
                opt.dataset.type = 'owned';
                opt.dataset.exp = s.expired_at ? s.expired_at.substring(0, 10) : '';
                opt.dataset.name = typeName;
                opt.dataset.code = st.code || '';
                opt.textContent = `${typeName} (Masa berlaku saat ini: ${expDateStr})`;
                ownedGroup.appendChild(opt);
            });
            select.appendChild(ownedGroup);
        }

        // Opsi C: Tambah / Perpanjang Jenis SIMPER Lain dari Master
        const otherMasterTypes = allMasterSimperTypes.filter(t => !ownedTypeIds.includes(Number(t.id)));
        if (otherMasterTypes.length > 0) {
            const masterGroup = document.createElement('optgroup');
            masterGroup.label = simperCount > 0 ? '── Tambah / Berikan Jenis SIMPER Lainnya ──' : '── Pilih Jenis SIMPER dari Master ──';
            
            otherMasterTypes.forEach(mt => {
                const opt = document.createElement('option');
                opt.value = mt.id;
                opt.dataset.type = 'new';
                opt.dataset.exp = '';
                opt.dataset.name = mt.name;
                opt.dataset.code = mt.code || '';
                opt.textContent = `+ ${mt.name} (${mt.code || 'Master SIMPER'})`;
                masterGroup.appendChild(opt);
            });
            select.appendChild(masterGroup);
        }

        // 4. Sinkronisasi Informasi & Preset Tanggal Baru
        onRenewSimperTypeChange(select.value);

        document.getElementById('renewForm').action = `/hse/employees/${emp.id}`;
        document.getElementById('renewModal').classList.remove('hidden');
    }

    function onRenewSimperTypeChange(val) {
        const select = document.getElementById('renewSimperTypeSelect');
        const selectedOpt = select.options[select.selectedIndex];
        const dateInput = document.getElementById('renewDate');
        const currentExpText = document.getElementById('renewCurrentExpText');
        const statusBadge = document.getElementById('renewCurrentStatusBadge');
        const recommendLabel = document.getElementById('renewRecommendLabel');

        const optType = selectedOpt ? selectedOpt.dataset.type : '';
        const currentExp = selectedOpt && selectedOpt.dataset.exp ? selectedOpt.dataset.exp : '';

        const todayStr = new Date().toISOString().substring(0, 10);
        const todayDate = new Date(todayStr);

        let baseDateForRenewal = todayDate;

        if (optType === 'all') {
            currentExpText.textContent = 'Mencakup seluruh jenis SIMPER yang dimiliki';
            statusBadge.textContent = 'Semua Izin';
            statusBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200';
            
            // Cari tanggal maksimal dari simper yang dimiliki
            if (currentRenewEmployee && Array.isArray(currentRenewEmployee.simpers) && currentRenewEmployee.simpers.length > 0) {
                let maxExp = null;
                currentRenewEmployee.simpers.forEach(s => {
                    if (s.expired_at) {
                        const d = s.expired_at.substring(0, 10);
                        if (!maxExp || d > maxExp) maxExp = d;
                    }
                });
                if (maxExp) {
                    const maxDateObj = new Date(maxExp);
                    if (!isNaN(maxDateObj) && maxDateObj > todayDate) {
                        baseDateForRenewal = maxDateObj;
                    }
                }
            }
        } else if (optType === 'new') {
            currentExpText.textContent = 'Belum aktif (Akan diterbitkan baru)';
            statusBadge.textContent = 'Penerbitan Baru';
            statusBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200';
            baseDateForRenewal = todayDate;
        } else if (currentExp) {
            currentExpText.textContent = currentExp;
            const expDateObj = new Date(currentExp);
            const diffDays = Math.ceil((expDateObj - todayDate) / (1000 * 60 * 60 * 24));
            
            if (diffDays < 0) {
                statusBadge.textContent = `Kedaluwarsa (${Math.abs(diffDays)} hari lalu)`;
                statusBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200';
                baseDateForRenewal = todayDate;
            } else if (diffDays <= 30) {
                statusBadge.textContent = `Segera Habis (Sisa ${diffDays} hari)`;
                statusBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200';
                baseDateForRenewal = expDateObj;
            } else {
                statusBadge.textContent = `Aktif (s/d ${currentExp})`;
                statusBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200';
                baseDateForRenewal = expDateObj;
            }
        } else {
            currentExpText.textContent = 'Belum pernah ditentukan';
            statusBadge.textContent = 'Belum Diatur';
            statusBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200';
            baseDateForRenewal = todayDate;
        }

        // Hitung tanggal rekomendasi perpanjangan: +1 Tahun dari basis tanggal
        const targetNewDate = new Date(baseDateForRenewal);
        targetNewDate.setFullYear(targetNewDate.getFullYear() + 1);
        dateInput.value = targetNewDate.toISOString().substring(0, 10);
        recommendLabel.textContent = `Rekomendasi (+1 Tahun): ${dateInput.value}`;
    }

    function setRenewDateOffset(months) {
        const select = document.getElementById('renewSimperTypeSelect');
        const selectedOpt = select ? select.options[select.selectedIndex] : null;
        const currentExp = selectedOpt && selectedOpt.dataset.exp ? selectedOpt.dataset.exp : '';
        const todayDate = new Date();

        let baseDate = todayDate;
        if (currentExp) {
            const expDateObj = new Date(currentExp);
            if (!isNaN(expDateObj) && expDateObj > todayDate) {
                baseDate = expDateObj;
            }
        }

        const newDate = new Date(baseDate);
        newDate.setMonth(newDate.getMonth() + months);
        document.getElementById('renewDate').value = newDate.toISOString().substring(0, 10);
    }

    function closeRenewModal() {
        const modal = document.getElementById('renewModal');
        if (modal) modal.classList.add('hidden');
        currentRenewEmployee = null;
    }
        const modal = document.getElementById('renewModal');
        if (modal) modal.classList.add('hidden');
        currentRenewEmployee = null;
    }

    function openDeleteEmployeeModal(id, name, nik) {
        document.getElementById('deleteEmployeeName').textContent = name;
        document.getElementById('deleteEmployeeNik').textContent = nik;
        document.getElementById('deleteEmployeeForm').action = `/hse/employees/${id}`;
        const modal = document.getElementById('deleteEmployeeModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeDeleteEmployeeModal() {
        const modal = document.getElementById('deleteEmployeeModal');
        if (modal) modal.classList.add('hidden');
    }

    // Pencarian Server-side Otomatis (Global ke seluruh database)
    const employeeFilterForm = document.getElementById('employeeFilterForm');
    const searchInput = document.getElementById('simperSearchInput');
    let searchDebounce = null;

    if (searchInput && searchInput.value) {
        searchInput.focus();
        const len = searchInput.value.length;
        searchInput.setSelectionRange(len, len);
    }

    searchInput?.addEventListener('input', function() {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(function() {
            employeeFilterForm?.submit();
        }, 500);
    });

    // Toggle Menu Dropdown Ekspor Simper (Format it.allrole.web.id)
    function toggleSimperExportMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('exportMenuSimper');
        if (menu) {
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }
    }

    // Klik di luar dropdown untuk menutup menu
    document.addEventListener('click', function(event) {
        const container = document.getElementById('exportSimperDropdownContainer');
        const menu = document.getElementById('exportMenuSimper');
        if (container && !container.contains(event.target)) {
            if (menu) {
                menu.style.display = 'none';
            }
        }
    });

    // Keyboard ESC untuk menutup modal panduan
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGuideModal();
            closeAddKaryawanModal();
            closeEditModal();
            closeRenewModal();
            closeImportKaryawanModal();
            closeDeleteEmployeeModal();
            const exportMenu = document.getElementById('exportMenuSimper');
            if (exportMenu) exportMenu.style.display = 'none';
        }
    });

    // Modal Tambah Karyawan Manual
    function openAddKaryawanModal() {
        const modal = document.getElementById('addKaryawanModal');
        const addUnitKerja = document.getElementById('addUnitKerjaInput');
        if (addUnitKerja) addUnitKerja.value = 'Umum';
        const addPhotoPreview = document.getElementById('addEmpPhotoPreview');
        const addPhotoPlaceholder = document.getElementById('addEmpPhotoPlaceholder');
        const addPhotoInput = document.getElementById('addEmpPhotoInput');
        if (addPhotoInput) addPhotoInput.value = '';
        if (addPhotoPreview) {
            addPhotoPreview.src = '';
            addPhotoPreview.classList.add('hidden');
        }
        if (addPhotoPlaceholder) {
            addPhotoPlaceholder.classList.remove('hidden');
        }
        const addTypeSelect = document.getElementById('addEmployeeTypeSelect');
        if (addTypeSelect) {
            addTypeSelect.value = 'Karyawan';
            toggleCustomEmployeeType('Karyawan', 'add');
        }
        document.querySelectorAll('[id^="addSimperCheck_"]').forEach(cb => {
            cb.checked = false;
            const id = cb.id.replace('addSimperCheck_', '');
            const fields = document.getElementById(`addSimperFields_${id}`);
            if (fields) fields.classList.add('hidden');
            const num = document.getElementById(`addSimperNum_${id}`);
            if (num) num.value = '';
            const exp = document.getElementById(`addSimperExp_${id}`);
            if (exp) exp.value = '';
        });
        const addSimperDateInp = document.getElementById('addSimperDateInput');
        if (addSimperDateInp) addSimperDateInp.value = '';
        if (modal) modal.classList.remove('hidden');
    }
    function closeAddKaryawanModal() {
        const modal = document.getElementById('addKaryawanModal');
        if (modal) modal.classList.add('hidden');
    }
    function toggleAddSimperDate(val) {
        const wrapper = document.getElementById('addSimperDateWrapper');
        const input = document.getElementById('addSimperDateInput');
        if (wrapper) {
            if (val === '1') {
                wrapper.style.display = 'block';
            } else {
                wrapper.style.display = 'none';
                if (input) input.value = '';
                document.querySelectorAll('[id^="addSimperCheck_"]').forEach(cb => {
                    cb.checked = false;
                    const id = cb.id.replace('addSimperCheck_', '');
                    const fields = document.getElementById(`addSimperFields_${id}`);
                    if (fields) fields.classList.add('hidden');
                    const exp = document.getElementById(`addSimperExp_${id}`);
                    if (exp) exp.value = '';
                });
            }
        }
    }

    // Modal Impor Karyawan via Excel
    function openImportKaryawanModal() {
        const modal = document.getElementById('importKaryawanModal');
        if (modal) modal.classList.remove('hidden');
    }
    function closeImportKaryawanModal() {
        const modal = document.getElementById('importKaryawanModal');
        if (modal) modal.classList.add('hidden');
    }
    function handleImportFileChange(input) {
        const display = document.getElementById('importFileName');
        if (input.files && input.files[0]) {
            display.textContent = 'File terpilih: ' + input.files[0].name;
            display.classList.add('text-emerald-700');
        } else {
            display.textContent = 'Klik untuk memilih file spreadsheet';
            display.classList.remove('text-emerald-700');
        }
    }
</script>
@endsection
