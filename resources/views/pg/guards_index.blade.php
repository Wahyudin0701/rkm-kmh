@extends('layouts.app')

@section('title', 'Data Satpam - Admin Private Guard | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Judul & Tombol Aksi (Selaras dengan format Data Karyawan) -->
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
                        title="Panduan Halaman Data Satpam">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                Data Satpam
            </h2>
        </div>

        <!-- Tombol Aksi Kanan -->
        <div class="grid grid-cols-2 gap-2.5 w-full sm:flex sm:items-center sm:gap-3 sm:w-auto">
            <!-- Dropdown Menu Ekspor Format it.allrole.web.id -->
            <div class="relative inline-block w-full sm:w-auto" id="exportGuardDropdownContainer">
                <button type="button" 
                        onclick="toggleGuardExportMenu(event)" 
                        id="btnGuardExportTrigger"
                        class="w-full sm:w-auto flex items-center justify-center space-x-2 border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                    <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                    <span class="truncate">Ekspor Satpam</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
                </button>
                <div id="exportMenuGuard" style="display: none;" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 z-50 overflow-hidden">
                    <a href="{{ route('pg.guards.export_pdf', request()->query()) }}" target="_blank" class="flex items-center px-4 py-3 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#1b5e20] font-bold transition">
                        <i class="fa-regular fa-file-pdf text-rose-600 text-sm mr-2.5"></i>
                        <span>Format PDF (Cetak)</span>
                    </a>
                    <div class="h-px bg-slate-100 mx-2"></div>
                    <a href="{{ route('pg.guards.export_excel', request()->query()) }}" class="flex items-center px-4 py-3 text-xs text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i class="fa-regular fa-file-excel text-emerald-600 text-sm mr-2.5"></i>
                        <span>Format Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            @can('pg.guards.create')
            <button type="button" 
                    onclick="document.getElementById('addGuardModal').classList.remove('hidden')" 
                    class="w-full sm:w-auto bg-[#1b5e20] hover:bg-[#144919] text-white px-4 sm:px-5 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition shadow-sm cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span class="truncate">Tambah Petugas Satpam</span>
            </button>
            @endcan
        </div>
    </div>

    <!-- Kotak Pencarian & Filter Regu + Penempatan (Selaras dengan Format Referensi) -->
    <form method="GET" action="{{ route('pg.guards.index') }}" id="guardFilterForm" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 sm:gap-3">
        <!-- Pencarian Cepat di Kiri -->
        <div class="relative w-full sm:max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" 
                   name="search"
                   id="guardSearchInput"
                   value="{{ request('search') }}"
                   placeholder="Cari ID Satpam atau nama petugas..." 
                   class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition">
        </div>

        <!-- Filter di Kanan (Pada Mobile: Sejajar di Bawah Search Bar) -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2 sm:gap-2.5 w-full sm:w-auto">
            <div class="flex items-center gap-2 sm:gap-2.5 w-full sm:w-auto">
                <!-- Filter Regu Satpam -->
                <select name="team" 
                        id="guardTeamSelect"
                        onchange="this.form.submit()" 
                        class="w-full sm:w-auto text-xs rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition truncate">
                    <option value="">Semua Regu</option>
                    <option value="1" {{ request('team') == '1' ? 'selected' : '' }}>Regu 1</option>
                    <option value="2" {{ request('team') == '2' ? 'selected' : '' }}>Regu 2</option>
                    <option value="3" {{ request('team') == '3' ? 'selected' : '' }}>Regu 3</option>
                </select>

                <!-- Filter Jumlah Tampilan Per Halaman -->
                <select name="per_page" 
                        onchange="this.form.submit()" 
                        class="w-full sm:w-auto text-xs rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition truncate">
                    <option value="20" {{ request('per_page', '20') == '20' ? 'selected' : '' }}>20 / Hal</option>
                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 / Hal</option>
                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>Semua (100)</option>
                </select>

                <!-- Tombol Submit Pencarian -->
                <button type="submit" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition shadow-sm cursor-pointer whitespace-nowrap">
                    Cari
                </button>
            </div>

            <!-- Tombol Reset Filter (Sesuai Referensi Merah / Rose Clean Text) -->
            <div id="guardResetContainer">
                @if(request()->anyFilled(['search', 'team', 'gate_id', 'per_page']))
                    <a href="{{ route('pg.guards.index') }}" 
                       id="guardResetBtn"
                       class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center justify-center sm:justify-start space-x-1.5 transition whitespace-nowrap py-1 sm:py-0 sm:ml-2">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i>
                        <span>Reset Filter</span>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Tabel Penuh Master Data Satpam (Header Navy Solid #06213f, Pada Mobile: Kotak Tanpa Border Radius) -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <!-- Header Tabel Navy Gelap Solid #06213f -->
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-14">NO</th>
                        <th class="py-3.5 px-4">ID SATPAM</th>
                        <th class="py-3.5 px-4">NAMA LENGKAP</th>
                        <th class="py-3.5 px-4 text-center">REGU</th>
                        <th class="py-3.5 px-4 text-center">STATUS</th>
                        <th class="py-3.5 px-4 text-center w-36">AKSI</th>
                    </tr>
                </thead>
                <tbody id="guardTableBody" class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($guards as $index => $g)
                        <tr class="guard-row hover:bg-slate-50/70 transition">
                            <!-- Nomor -->
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                {{ $index + 1 }}
                            </td>

                            <!-- ID Satpam (Font Bold Hitam Monospace) -->
                            <td class="py-3.5 px-4 font-bold text-slate-900 font-mono text-xs whitespace-nowrap">
                                <span class="bg-slate-100 text-slate-800 px-2 py-0.5 rounded border border-slate-200">
                                    {{ $g->badge_id ?? 'SEC-' . str_pad($g->id, 3, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>

                            <!-- Nama Lengkap (Font Bold Hitam Tegas) -->
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ $g->name }}
                            </td>

                            <!-- Regu Tugas (Badge Pill Warna-Warni) -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($g->team == 1)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        Regu 1
                                    </span>
                                @elseif($g->team == 2)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Regu 2
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        Regu 3
                                    </span>
                                @endif
                            </td>

                            <!-- Status Aktif / Nonaktif -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($g->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi (Ikon Edit Oranye & Ikon Status Merah Selaras Format Gambar) -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-2">
                                    @can('pg.guards.edit')
                                    <!-- Tombol Edit Oranye -->
                                    <button type="button" 
                                            onclick="openEditGuardModal({{ json_encode($g) }})" 
                                            class="text-amber-500 hover:text-amber-700 p-1.5 transition cursor-pointer" 
                                            title="Edit Data / Reset Password">
                                        <i class="fa-regular fa-pen-to-square text-sm"></i>
                                    </button>
                                    @endcan

                                    @can('pg.guards.toggle')
                                    <!-- Tombol Toggle Nonaktifkan/Aktifkan Merah/Hijau -->
                                    <button type="button" 
                                            onclick="openToggleStatusModal({{ $g->id }}, '{{ addslashes($g->name) }}', '{{ $g->badge_id ?? 'SEC-' . str_pad($g->id, 3, '0', STR_PAD_LEFT) }}', {{ $g->is_active ? 'true' : 'false' }})" 
                                            title="{{ $g->is_active ? 'Nonaktifkan Petugas' : 'Aktifkan Petugas' }}" 
                                            class="{{ $g->is_active ? 'text-rose-500 hover:text-rose-700' : 'text-emerald-600 hover:text-emerald-800' }} p-1.5 transition cursor-pointer">
                                        <i class="fa-solid fa-power-off text-sm"></i>
                                    </button>
                                    @endcan

                                    @can('pg.guards.delete')
                                    <!-- Tombol Hapus Merah -->
                                    <button type="button" 
                                            onclick="openDeleteGuardModal({{ $g->id }}, '{{ addslashes($g->name) }}', '{{ $g->badge_id ?? 'SEC-' . str_pad($g->id, 3, '0', STR_PAD_LEFT) }}')" 
                                            title="Hapus Petugas Satpam" 
                                            class="text-rose-600 hover:text-rose-800 p-1.5 transition cursor-pointer">
                                        <i class="fa-regular fa-trash-can text-sm"></i>
                                    </button>
                                    @endcan

                                    @if(!auth()->user()->can('pg.guards.edit') && !auth()->user()->can('pg.guards.toggle') && !auth()->user()->can('pg.guards.delete'))
                                        <span class="text-xs text-slate-400 italic">Hanya lihat</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="serverGuardEmptyRow">
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
                                @if(request('search'))
                                    <p class="text-sm font-semibold text-slate-700">
                                        Data "<span class="text-rose-600 font-bold">{{ request('search') }}</span>" tidak ditemukan di tabel
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">Pastikan ejaan ID Satpam atau nama petugas yang dicari sudah benar.</p>
                                @elseif(request()->anyFilled(['team', 'gate_id']))
                                    <p class="text-sm font-semibold text-slate-700">Belum ada data petugas Satpam yang sesuai kriteria filter.</p>
                                    <p class="text-xs text-slate-400 mt-1">Silakan pilih regu atau ubah kata kunci pencarian.</p>
                                @else
                                    <i class="fa-solid fa-user-slash text-3xl mb-2 block text-slate-300"></i>
                                    <p class="text-sm font-semibold text-slate-700">Belum ada data petugas Satpam terdaftar.</p>
                                @endif

                                @if(request()->anyFilled(['search', 'team', 'gate_id']))
                                    <div class="mt-4">
                                        <a href="{{ route('pg.guards.index') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition">
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

        @if($guards->hasPages())
            <div class="p-4 border-t border-slate-100 bg-white">
                {{ $guards->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: TAMBAH PETUGAS SATPAM BARU                                    -->
<!-- ========================================================================= -->
<div id="addGuardModal" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-7 sm:p-8 border border-slate-100 overflow-hidden">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                Tambah Petugas Satpam
            </h3>
            <button type="button" onclick="document.getElementById('addGuardModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('pg.guards.store') }}" method="POST" class="mt-5 space-y-4">
            @csrf

            <!-- ID Satpam -->
            <div>
                <label for="badge_id" class="block text-sm font-bold text-slate-800 mb-2">
                    ID Satpam (Username Login) <span class="text-rose-500">*</span>
                </label>
                <input type="text" 
                       name="badge_id" 
                       id="badge_id" 
                       value="{{ old('badge_id') }}" 
                       required 
                       placeholder="Contoh: SEC-006 atau PG-06" 
                       class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 font-mono uppercase placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                <span class="text-[11px] text-slate-400 block mt-1">Digunakan oleh petugas untuk login di pos jaga.</span>
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label for="name" class="block text-sm font-bold text-slate-800 mb-2">
                    Nama Lengkap Satpam <span class="text-rose-500">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       value="{{ old('name') }}" 
                       required 
                       placeholder="Contoh: Budi Santoso" 
                       class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
            </div>

            <!-- Regu Tugas Satpam (Fleksibel Lintas Seluruh Pos Gerbang) -->
            <div>
                <label for="team" class="block text-sm font-bold text-slate-800 mb-2">
                    Regu Tugas <span class="text-rose-500">*</span>
                </label>
                <select name="team" 
                        id="team" 
                        required 
                        class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                    <option value="" disabled selected>Pilih Regu</option>
                    <option value="1" {{ old('team') == 1 ? 'selected' : '' }}>Regu 1</option>
                    <option value="2" {{ old('team') == 2 ? 'selected' : '' }}>Regu 2</option>
                    <option value="3" {{ old('team') == 3 ? 'selected' : '' }}>Regu 3</option>
                </select>
                <span class="text-[11px] text-slate-400 block mt-1">Petugas dapat memilih pos gerbang jaga saat login/bertugas secara fleksibel.</span>
            </div>

            <!-- Password Akun Login -->
            <div>
                <label for="password" class="block text-sm font-bold text-slate-800 mb-2">
                    Password Akun Login <span class="text-rose-500">*</span>
                </label>
                <input type="password" 
                       name="password" 
                       id="password" 
                       required 
                       placeholder="Minimal 4 karakter" 
                       class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
            </div>

            <!-- Footer Tombol: Batal & Simpan Data -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3 mt-6">
                <button type="button" 
                        onclick="document.getElementById('addGuardModal').classList.add('hidden')" 
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

<!-- ========================================================================= -->
<!-- MODAL POPUP: EDIT DATA PETUGAS SATPAM                                      -->
<!-- ========================================================================= -->
<div id="editGuardModal" onclick="if(event.target === this) closeEditGuardModal()" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-7 sm:p-8 border border-slate-100 overflow-hidden">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                Edit Data Satpam
            </h3>
            <button type="button" onclick="closeEditGuardModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="editGuardForm" method="POST" action="" class="mt-5 space-y-4">
            @csrf
            
            <!-- ID Satpam (Read-only) -->
            <div>
                <label class="block text-sm font-bold text-slate-800 mb-2">ID Satpam (Username Login)</label>
                <input type="text" id="editBadgeId" disabled class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-slate-100 text-slate-600 font-mono font-bold">
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label for="editName" class="block text-sm font-bold text-slate-800 mb-2">
                    Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="name" id="editName" required placeholder="Contoh: Budi Santoso" 
                       class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
            </div>

            <!-- Regu Tugas -->
            <div>
                <label for="editTeam" class="block text-sm font-bold text-slate-800 mb-2">
                    Regu Tugas <span class="text-rose-500">*</span>
                </label>
                <select name="team" id="editTeam" required 
                        class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                    <option value="1">Regu 1</option>
                    <option value="2">Regu 2</option>
                    <option value="3">Regu 3</option>
                </select>
                <span class="text-[11px] text-slate-400 block mt-1">Petugas dapat memilih pos gerbang jaga saat login/bertugas secara fleksibel.</span>
            </div>

            <!-- Ganti Password Baru (Opsional) -->
            <div>
                <label for="editPassword" class="block text-sm font-bold text-slate-800 mb-2">
                    Ganti Password Baru <span class="text-slate-400 font-normal text-xs">(Kosongkan jika tidak ingin diubah)</span>
                </label>
                <input type="password" name="password" id="editPassword" placeholder="Minimal 4 karakter" 
                       class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
            </div>

            <!-- Footer Tombol -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3 mt-6">
                <button type="button" onclick="closeEditGuardModal()" 
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
<!-- MODAL POPUP: KONFIRMASI AKTIFKAN / NONAKTIFKAN PETUGAS SATPAM             -->
<!-- ========================================================================= -->
<div id="toggleStatusModal" 
     onclick="if(event.target === this) closeToggleStatusModal()" 
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
    <div class="bg-white rounded-3xl shadow-2xl max-w-sm sm:max-w-md w-full p-6 sm:p-7 border border-slate-100 transform transition-all text-center">
        <!-- Icon Dinamis (Rose untuk Nonaktifkan, Emerald untuk Aktifkan) -->
        <div id="toggleIconContainer" class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl shadow-sm border">
            <i id="toggleIcon" class="fa-solid fa-power-off"></i>
        </div>

        <!-- Judul Modal -->
        <h3 id="toggleModalTitle" class="text-lg font-bold text-slate-900 tracking-tight">
            Konfirmasi Status Petugas
        </h3>

        <!-- Deskripsi Pertanyaan Konfirmasi -->
        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
            Apakah Anda yakin ingin <span id="toggleActionVerb" class="font-bold"></span> petugas Satpam 
            <span id="toggleGuardName" class="font-bold text-slate-900"></span> 
            (<span id="toggleGuardBadge" class="font-bold font-mono text-slate-800 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200"></span>)?
        </p>

        <!-- Keterangan Efek Perubahan -->
        <div id="toggleInfoContainer" class="text-[11px] mt-3 py-2 px-3 rounded-xl inline-flex items-center text-left border">
            <i class="fa-solid fa-circle-info mr-2 flex-shrink-0 text-xs"></i>
            <span id="toggleInfoText"></span>
        </div>

        <!-- Tombol Aksi (Batal & Konfirmasi) -->
        <div class="flex items-center space-x-3 mt-6">
            <button type="button" 
                    onclick="closeToggleStatusModal()" 
                    class="flex-1 py-2.5 px-4 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition cursor-pointer">
                Batal
            </button>
            <form id="toggleStatusForm" action="" method="POST" class="flex-1">
                @csrf
                <button type="submit" 
                        id="toggleSubmitBtn" 
                        class="w-full py-2.5 px-4 rounded-xl text-white font-bold text-xs transition shadow-sm flex items-center justify-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-power-off text-xs"></i>
                    <span id="toggleSubmitText">Ya, Lanjutkan</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: KONFIRMASI HAPUS PETUGAS SATPAM                              -->
<!-- ========================================================================= -->
<div id="deleteGuardModal" 
     onclick="if(event.target === this) closeDeleteGuardModal()" 
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto no-scrollbar">
    <div class="bg-white rounded-3xl shadow-2xl max-w-sm sm:max-w-md w-full p-6 sm:p-7 border border-slate-100 transform transition-all text-center">
        <!-- Icon Peringatan Sampah Merah -->
        <div class="w-14 h-14 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4 text-2xl shadow-sm">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h3 class="text-lg font-bold text-slate-900 tracking-tight">
            Konfirmasi Hapus Petugas Satpam
        </h3>

        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
            Apakah Anda yakin ingin menghapus data petugas Satpam <span id="deleteGuardName" class="font-bold text-slate-900"></span> (<span id="deleteGuardBadge" class="font-bold font-mono text-slate-800 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200"></span>)?
        </p>

        <p class="text-[11px] text-rose-600 mt-3 bg-rose-50/70 border border-rose-100 py-2 px-3 rounded-xl inline-flex items-center text-left">
            <i class="fa-solid fa-circle-info mr-2 flex-shrink-0 text-xs"></i>
            <span>Akun login pos dan data petugas ini akan dihapus permanen dari sistem.</span>
        </p>

        <!-- Tombol Aksi (Batal & Hapus Data) -->
        <div class="flex items-center space-x-3 mt-6">
            <button type="button" 
                    onclick="closeDeleteGuardModal()" 
                    class="flex-1 py-2.5 px-4 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition cursor-pointer">
                Batal
            </button>
            <form id="deleteGuardForm" action="" method="POST" class="flex-1">
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

<script>
    // Dropdown Menu Ekspor Data Satpam
    function toggleGuardExportMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('exportMenuGuard');
        if (menu) {
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }
    }

    document.addEventListener('click', function(e) {
        const container = document.getElementById('exportGuardDropdownContainer');
        const menu = document.getElementById('exportMenuGuard');
        if (menu && container && !container.contains(e.target)) {
            menu.style.display = 'none';
        }
    });

    function openDeleteGuardModal(id, name, badge) {
        const modal = document.getElementById('deleteGuardModal');
        const form = document.getElementById('deleteGuardForm');
        const nameSpan = document.getElementById('deleteGuardName');
        const badgeSpan = document.getElementById('deleteGuardBadge');

        form.action = `/pg/guards/${id}`;
        nameSpan.textContent = name;
        badgeSpan.textContent = badge;

        modal.classList.remove('hidden');
    }

    function closeDeleteGuardModal() {
        const modal = document.getElementById('deleteGuardModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function openToggleStatusModal(id, name, badge, isActive) {
        const modal = document.getElementById('toggleStatusModal');
        const form = document.getElementById('toggleStatusForm');
        const iconContainer = document.getElementById('toggleIconContainer');
        const title = document.getElementById('toggleModalTitle');
        const actionVerb = document.getElementById('toggleActionVerb');
        const nameSpan = document.getElementById('toggleGuardName');
        const badgeSpan = document.getElementById('toggleGuardBadge');
        const infoContainer = document.getElementById('toggleInfoContainer');
        const infoText = document.getElementById('toggleInfoText');
        const submitBtn = document.getElementById('toggleSubmitBtn');
        const submitText = document.getElementById('toggleSubmitText');

        form.action = `/pg/guards/${id}/toggle`;
        nameSpan.textContent = name;
        badgeSpan.textContent = badge;

        if (isActive) {
            // Mau menonaktifkan petugas
            title.textContent = 'Konfirmasi Nonaktifkan Petugas';
            actionVerb.textContent = 'menonaktifkan';
            actionVerb.className = 'font-bold text-rose-600';
            
            iconContainer.className = 'w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl shadow-sm bg-rose-50 border border-rose-100 text-rose-600';
            infoContainer.className = 'text-[11px] mt-3 py-2 px-3 rounded-xl inline-flex items-center text-left border bg-rose-50/70 border-rose-100 text-rose-700';
            infoText.textContent = 'Petugas yang dinonaktifkan tidak akan dapat login ke pos jaga gerbang.';
            
            submitBtn.className = 'w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition shadow-sm flex items-center justify-center space-x-1.5 cursor-pointer';
            submitText.textContent = 'Ya, Nonaktifkan';
        } else {
            // Mau mengaktifkan kembali petugas
            title.textContent = 'Konfirmasi Aktifkan Petugas';
            actionVerb.textContent = 'mengaktifkan kembali';
            actionVerb.className = 'font-bold text-emerald-700';
            
            iconContainer.className = 'w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl shadow-sm bg-emerald-50 border border-emerald-100 text-emerald-600';
            infoContainer.className = 'text-[11px] mt-3 py-2 px-3 rounded-xl inline-flex items-center text-left border bg-emerald-50/70 border-emerald-100 text-emerald-700';
            infoText.textContent = 'Petugas yang diaktifkan akan dapat kembali login dan bertugas di pos jaga gerbang.';
            
            submitBtn.className = 'w-full py-2.5 px-4 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs transition shadow-sm flex items-center justify-center space-x-1.5 cursor-pointer';
            submitText.textContent = 'Ya, Aktifkan';
        }

        modal.classList.remove('hidden');
    }

    function closeToggleStatusModal() {
        const modal = document.getElementById('toggleStatusModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function openEditGuardModal(guard) {
        const modal = document.getElementById('editGuardModal');
        const form = document.getElementById('editGuardForm');
        
        form.action = `/pg/guards/${guard.id}`;
        document.getElementById('editBadgeId').value = guard.badge_id || ('SEC-' + String(guard.id).padStart(3, '0'));
        document.getElementById('editName').value = guard.name || '';
        document.getElementById('editTeam').value = guard.team || 1;
        const editGateInput = document.getElementById('editGateId');
        if (editGateInput) editGateInput.value = guard.gate_id || '';
        document.getElementById('editPassword').value = '';

        modal.classList.remove('hidden');
    }

    function closeEditGuardModal() {
        document.getElementById('editGuardModal').classList.add('hidden');
    }

    // Pencarian Server-side Otomatis (Global ke seluruh database, bukan hanya halaman 1)
    const searchInput = document.getElementById('guardSearchInput');
    const guardFilterForm = document.getElementById('guardFilterForm');
    let searchDebounce = null;

    // Fokuskan kursor di ujung teks input jika ada kata kunci pencarian aktif
    if (searchInput && searchInput.value) {
        searchInput.focus();
        const len = searchInput.value.length;
        searchInput.setSelectionRange(len, len);
    }

    searchInput?.addEventListener('input', function() {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(function() {
            guardFilterForm?.submit();
        }, 500);
    });
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
            closeEditGuardModal();
            closeToggleStatusModal();
            closeDeleteGuardModal();
            document.getElementById('addGuardModal')?.classList.add('hidden');
        }
    });
</script>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (DATA SATPAM)                                -->
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
                    <p class="text-xs text-slate-500">Data Satpam & Regu Jaga</p>
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
                Selamat datang di menu <strong>Data Satpam</strong>! Halaman ini adalah pusat pengelolaan profil petugas keamanan pos gerbang di PT Kerinci Merangin Hidro. Di halaman ini Anda dapat:
            </p>

            <div class="space-y-3">
                <!-- Fitur 1 (Hijau) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-emerald-200/50">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Registrasi Petugas Satpam Baru</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Mendaftarkan personel baru melalui tombol '+ Tambah Petugas Satpam' dengan memasukkan ID Badge unik, nama lengkap, dan kredensial login.
                        </p>
                    </div>
                </div>

                <!-- Fitur 2 (Biru) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-blue-200/50">
                        <i class="fa-solid fa-users-gear"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Penetapan Regu Tugas Jaga</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Mengatur jadwal pembagian regu piket (Regu 1, 2, atau 3) dan penempatan pos jaga gerbang untuk memastikan kepatuhan giliran kerja.
                        </p>
                    </div>
                </div>

                <!-- Fitur 3 (Amber) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-amber-200/50">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Pembaruan Data & Reset Password</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Klik tombol aksi 'Edit' pada tabel untuk memperbarui nama, memindahkan regu jaga, atau mengatur ulang kata sandi petugas jika diperlukan.
                        </p>
                    </div>
                </div>

                <!-- Fitur 4 (Rose) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-rose-100/70 text-rose-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-rose-200/50">
                        <i class="fa-solid fa-filter"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Filter Regu, Penempatan & Reset Cepat</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Saring baris data berdasarkan Regu 1-3 atau pos penempatan gerbang spesifik, dan kembalikan seluruh daftar data semula secara instan melalui opsi 'Reset Filter'.
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
