@extends('layouts.app')

@section('title', 'Manajemen Pengguna - Super Admin RKM')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2.5">
                <span class="w-2.5 h-7 rounded-full bg-indigo-600 inline-block"></span>
                Manajemen Pengguna Sistem
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Kelola akun pengguna, ganti kata sandi, dan tetapkan role wewenang pada sistem RKM-KMH.
            </p>
        </div>
        <div>
            <button type="button" 
                    onclick="openCreateUserModal()" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                <i class="fa-solid fa-user-plus text-sm"></i>
                <span>Tambah Pengguna Baru</span>
            </button>
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between text-emerald-800 text-xs font-semibold">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-center justify-between text-rose-800 text-xs font-semibold">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs">
            <div class="flex items-center gap-2 font-bold mb-1">
                <i class="fa-solid fa-circle-xmark text-rose-600"></i>
                <span>Terdapat kesalahan pada input Anda:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-rose-700">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Card Filter & Pencarian -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-sm">
        <form id="usersFilterForm" method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
            <input type="hidden" name="sort" id="userSortInput" value="{{ request('sort', 'latest') }}">

            <div class="flex-1 relative">
                <button type="submit" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600 transition text-xs" title="Cari pengguna">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Cari nama, NIK, atau email pengguna..." 
                       class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white transition">
            </div>

            <div class="w-full sm:w-48">
                <select name="role" onchange="document.getElementById('usersFilterForm').submit()" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white transition cursor-pointer">
                    <option value="">-- Semua Role --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', $role->name)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Tombol Menu Urutkan (Sorting Popover) -->
            <div class="relative flex items-center gap-2" id="sortDropdownContainer">
                @php
                    $currentSort = request('sort', 'latest');
                    $sortLabels = [
                        'latest' => 'Terbaru',
                        'oldest' => 'Terlama',
                        'name_asc' => 'Nama (A-Z)',
                        'name_desc' => 'Nama (Z-A)',
                    ];
                    $activeSortLabel = $sortLabels[$currentSort] ?? 'Terbaru';
                    $isCustomSort = $currentSort !== 'latest';
                @endphp

                <button type="button" 
                        id="sortDropdownBtn" 
                        onclick="toggleSortDropdown()" 
                        class="w-full sm:w-auto px-3.5 py-2 {{ $isCustomSort ? 'bg-indigo-50 border-indigo-200 text-indigo-700 shadow-sm' : 'bg-slate-800 hover:bg-slate-900 text-white' }} border rounded-xl text-xs font-bold transition flex items-center justify-between sm:justify-start gap-2">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-arrow-down-wide-short {{ $isCustomSort ? 'text-indigo-600' : 'text-slate-300' }}"></i>
                        <span>Urutkan: <strong class="{{ $isCustomSort ? 'text-indigo-800 font-black' : 'text-white' }}">{{ $activeSortLabel }}</strong></span>
                    </div>
                    <i id="sortDropdownChevron" class="fa-solid fa-chevron-down text-[10px] {{ $isCustomSort ? 'text-indigo-500' : 'text-slate-300' }} transition-transform duration-200"></i>
                </button>

                <!-- Menu Popover Dropdown Urutkan -->
                <div id="sortDropdownMenu" 
                     class="hidden absolute right-0 top-full mt-2 w-56 bg-white border border-slate-200 rounded-2xl shadow-xl z-30 py-2 divide-y divide-slate-100 animate-scale-in">
                    <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        Kriteria Pengurutan
                    </div>
                    <div class="py-1">
                        <button type="button" 
                                onclick="applyUserSort('latest')" 
                                class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between hover:bg-slate-50 transition {{ $currentSort === 'latest' ? 'text-indigo-600 font-bold bg-indigo-50/50' : 'text-slate-700 font-medium' }}">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left text-slate-400 text-[11px]"></i>
                                <span>Terdaftar Terbaru</span>
                            </span>
                            @if($currentSort === 'latest')
                                <i class="fa-solid fa-check text-indigo-600 text-[10px]"></i>
                            @endif
                        </button>
                        <button type="button" 
                                onclick="applyUserSort('oldest')" 
                                class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between hover:bg-slate-50 transition {{ $currentSort === 'oldest' ? 'text-indigo-600 font-bold bg-indigo-50/50' : 'text-slate-700 font-medium' }}">
                            <span class="flex items-center gap-2">
                                <i class="fa-regular fa-clock text-slate-400 text-[11px]"></i>
                                <span>Terdaftar Terlama</span>
                            </span>
                            @if($currentSort === 'oldest')
                                <i class="fa-solid fa-check text-indigo-600 text-[10px]"></i>
                            @endif
                        </button>
                        <button type="button" 
                                onclick="applyUserSort('name_asc')" 
                                class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between hover:bg-slate-50 transition {{ $currentSort === 'name_asc' ? 'text-indigo-600 font-bold bg-indigo-50/50' : 'text-slate-700 font-medium' }}">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-arrow-down-a-z text-slate-400 text-[11px]"></i>
                                <span>Nama Pengguna (A &rarr; Z)</span>
                            </span>
                            @if($currentSort === 'name_asc')
                                <i class="fa-solid fa-check text-indigo-600 text-[10px]"></i>
                            @endif
                        </button>
                        <button type="button" 
                                onclick="applyUserSort('name_desc')" 
                                class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between hover:bg-slate-50 transition {{ $currentSort === 'name_desc' ? 'text-indigo-600 font-bold bg-indigo-50/50' : 'text-slate-700 font-medium' }}">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-arrow-down-z-a text-slate-400 text-[11px]"></i>
                                <span>Nama Pengguna (Z &rarr; A)</span>
                            </span>
                            @if($currentSort === 'name_desc')
                                <i class="fa-solid fa-check text-indigo-600 text-[10px]"></i>
                            @endif
                        </button>
                    </div>
                </div>

                @if(request()->anyFilled(['search', 'role']) || request('sort', 'latest') !== 'latest')
                    <a href="{{ route('admin.users.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition flex items-center gap-1.5" title="Reset Filter & Urutan">
                        <i class="fa-solid fa-arrow-rotate-left"></i>
                        <span class="hidden sm:inline">Reset</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Data Pengguna -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Pengguna</th>
                        <th class="py-3 px-4">Email</th>
                        <th class="py-3 px-4">Role RBAC</th>
                        <th class="py-3 px-4">Terdaftar</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($users as $user)
                        @php
                            $primaryRole = $user->roles->first()?->name ?? $user->role;
                            $badgeStyle = match($primaryRole) {
                                'super_admin' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'hse_admin' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'qma_admin' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'pg_admin' => 'bg-purple-50 text-purple-700 border-purple-200',
                                'gate_officer' => 'bg-amber-50 text-amber-700 border-amber-200',
                                default => 'bg-slate-100 text-slate-600 border-slate-200',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    @if($user->photo_url)
                                        <img src="{{ $user->photo_url }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-2xs flex-shrink-0">
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 font-black flex items-center justify-center text-xs flex-shrink-0">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-bold text-slate-900 text-xs">{{ $user->name }}</p>
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                            <span class="font-mono text-slate-600 font-medium">NIK: <span>{{ $user->username }}</span></span>
                                            @if($user->employee && ($user->employee->position || $user->employee->unit_kerja))
                                                <span>&bull;</span>
                                                <span class="text-slate-500 font-medium">{{ $user->employee->position ?: $user->employee->unit_kerja }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-slate-600 font-normal">
                                {{ $user->email }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $badgeStyle }} capitalize">
                                        {{ str_replace('_', ' ', $primaryRole) }}
                                    </span>
                                    @if($user->permissions->count() > 0)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-indigo-700 bg-indigo-50/90 px-2 py-0.5 rounded-md border border-indigo-100" title="{{ $user->permissions->pluck('name')->implode(', ') }}">
                                            <i class="fa-solid fa-key text-[9px]"></i>
                                            +{{ $user->permissions->count() }} Izin Khusus
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4 text-slate-500 text-[11px]">
                                {{ $user->created_at->format('d M Y') }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" 
                                            onclick='openEditUserModal(@json($user), "{{ $primaryRole }}", @json($user->permissions->pluck("name")))' 
                                            class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 flex items-center justify-center transition" 
                                            title="Edit Pengguna">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>

                                    @if($user->id !== auth()->id() && $user->username !== 'superadmin')
                                        <button type="button" 
                                                onclick='openDeleteUserModal({{ $user->id }}, "{{ addslashes($user->name) }}")' 
                                                class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 flex items-center justify-center transition" 
                                                title="Hapus Pengguna">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-user-slash text-2xl mb-2 text-slate-300 block"></i>
                                Tidak ada data pengguna yang sesuai kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: TAMBAH PENGGUNA BARU -->
<!-- ========================================================================= -->
<div id="createUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl overflow-hidden animate-scale-in">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-indigo-600"></i>
                Tambah Pengguna Baru
            </h3>
            <button type="button" onclick="closeCreateUserModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf

            <!-- Upload Foto Profil (Opsional) -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Foto Profil Pengguna (Opsional)</label>
                <div class="flex items-center space-x-3">
                    <div id="addUserPhotoPreviewContainer" class="w-12 h-12 rounded-full border-2 border-dashed border-slate-300 bg-slate-50 flex items-center justify-center overflow-hidden flex-shrink-0 shadow-2xs">
                        <img id="addUserPhotoPreview" src="" alt="Pratinjau" class="w-full h-full object-cover hidden">
                        <i id="addUserPhotoPlaceholder" class="fa-solid fa-camera text-slate-400 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <input type="file" 
                               name="photo" 
                               id="addUserPhotoInput" 
                               accept="image/jpeg,image/png,image/jpg,image/webp" 
                               onchange="previewUserImage(this, 'addUserPhotoPreview', 'addUserPhotoPlaceholder')"
                               class="block w-full text-xs text-slate-500 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        <p class="text-[10px] text-slate-400 mt-0.5">Otomatis dikompresi hemat storage (Maks. 10MB).</p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                <input type="text" name="name" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white" placeholder="Contoh: Ahmad Rizky">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">NIK (Nomor Induk Karyawan) *</label>
                    <input type="text" name="username" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white font-mono" placeholder="Contoh: KMH-2026-101">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email *</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white" placeholder="ahmad@kmh.co.id">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Password *</label>
                <input type="password" name="password" required minlength="6" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white" placeholder="Minimal 6 karakter">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Role RBAC *</label>
                <select name="role" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white">
                    <option value="">-- Pilih Role --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ ucwords(str_replace('_', ' ', $role->name)) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Section: Data Profil Karyawan & Penugasan (Selaras Master HSE) -->
            <div class="pt-3 border-t border-slate-100 space-y-3">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                        <i class="fa-solid fa-id-badge text-indigo-600"></i>
                        <span>Data Profil Karyawan & Penugasan</span>
                    </label>
                    <span class="text-[10px] text-slate-400 font-medium">(Opsional)</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Unit Kerja</label>
                        <select name="unit_kerja" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white text-slate-700">
                            <option value="">-- Pilih Unit Kerja --</option>
                            <option value="Pembangkit">Pembangkit</option>
                            <option value="Bendungan">Bendungan</option>
                            <option value="Teknik">Teknik</option>
                            <option value="Umum">Umum</option>
                            <option value="Logistik">Logistik</option>
                            <option value="HSE">HSE</option>
                            <option value="QMA">QMA</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Divisi</label>
                        <input type="text" name="division" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white text-slate-700" placeholder="Contoh: Operasi / Keuangan">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Posisi / Jabatan</label>
                        <input type="text" name="position" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white text-slate-700" placeholder="Contoh: Driver / Staff Finance">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Operator / Driver</label>
                        <select name="is_operator" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white text-slate-700">
                            <option value="0">TIDAK (Bukan Driver)</option>
                            <option value="1">YA (Aktif Mengemudi)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeCreateUserModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT PENGGUNA -->
<!-- ========================================================================= -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl overflow-hidden animate-scale-in">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-user-pen text-indigo-600"></i>
                Edit Data Pengguna
            </h3>
            <button type="button" onclick="closeEditUserModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <form id="editUserForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf

            <!-- Upload / Ganti Foto Profil -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Foto Profil Pengguna</label>
                <div class="flex items-center space-x-3">
                    <div id="editUserPhotoPreviewContainer" class="w-12 h-12 rounded-full border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden flex-shrink-0 shadow-2xs">
                        <img id="editUserPhotoPreview" src="" alt="Foto" class="w-full h-full object-cover hidden">
                        <i id="editUserPhotoPlaceholder" class="fa-solid fa-user text-slate-400 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <input type="file" 
                               name="photo" 
                               id="editUserPhotoInput" 
                               accept="image/jpeg,image/png,image/jpg,image/webp" 
                               onchange="previewUserImage(this, 'editUserPhotoPreview', 'editUserPhotoPlaceholder')"
                               class="block w-full text-xs text-slate-500 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        <p class="text-[10px] text-slate-400 mt-0.5">Pilih file baru jika ingin mengganti foto profil.</p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                <input type="text" id="edit_name" name="name" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">NIK (Nomor Induk Karyawan) *</label>
                    <input type="text" id="edit_username" name="username" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email *</label>
                    <input type="email" id="edit_email" name="email" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Ganti Password <span class="text-slate-400 font-normal">(Kosongkan jika tidak ingin mengubah)</span>
                </label>
                <input type="password" name="password" minlength="6" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white" placeholder="Masukkan password baru">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Role RBAC *</label>
                <select id="edit_role" name="role" required onchange="updatePermissionCheckboxes()" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white">
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ ucwords(str_replace('_', ' ', $role->name)) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Section: Data Profil Karyawan & Penugasan (Selaras Master HSE) -->
            <div class="pt-3 border-t border-slate-100 space-y-3">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                        <i class="fa-solid fa-id-badge text-indigo-600"></i>
                        <span>Data Profil Karyawan & Penugasan</span>
                    </label>
                    <span class="text-[10px] text-slate-400 font-medium">(Opsional)</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Unit Kerja</label>
                        <select id="edit_unit_kerja" name="unit_kerja" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white text-slate-700">
                            <option value="">-- Pilih Unit Kerja --</option>
                            <option value="Pembangkit">Pembangkit</option>
                            <option value="Bendungan">Bendungan</option>
                            <option value="Teknik">Teknik</option>
                            <option value="Umum">Umum</option>
                            <option value="Logistik">Logistik</option>
                            <option value="HSE">HSE</option>
                            <option value="QMA">QMA</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Divisi</label>
                        <input type="text" id="edit_division" name="division" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white text-slate-700" placeholder="Contoh: Operasi / Keuangan">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Posisi / Jabatan</label>
                        <input type="text" id="edit_position" name="position" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white text-slate-700" placeholder="Contoh: Driver / Staff Finance">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Operator / Driver</label>
                        <select id="edit_is_operator" name="is_operator" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white text-slate-700">
                            <option value="0">TIDAK (Bukan Driver)</option>
                            <option value="1">YA (Aktif Mengemudi)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Accordion: Izin Tambahan Khusus (Direct Permissions) -->
            <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50">
                <button type="button" 
                        onclick="toggleDirectPermsAccordion()" 
                        class="w-full px-4 py-2.5 bg-slate-100/70 hover:bg-slate-100 flex items-center justify-between text-xs font-bold text-slate-700 transition">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-key text-indigo-600"></i>
                        <span>Izin Tambahan Khusus (Direct Permissions)</span>
                    </span>
                    <i id="directPermsChevron" class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200"></i>
                </button>

                <div id="directPermsContainer" class="hidden p-4 space-y-4 max-h-64 overflow-y-auto bg-white border-t border-slate-200 text-xs">
                    <!-- Panduan Visual Matriks Izin -->
                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-2.5 text-[11px] text-slate-600">
                        <i class="fa-solid fa-circle-info text-indigo-600 text-sm mt-0.5 flex-shrink-0"></i>
                        <div class="leading-relaxed">
                            <span class="font-bold text-slate-800 block mb-0.5">Panduan Wewenang Pengguna:</span>
                            Wewenang dengan tanda <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">✓ Bawaan Role</span> otomatis aktif dari Role utama yang dipilih di atas. Anda dapat mencentang kotak lainnya untuk memberikan <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">+ Izin Khusus</span> tambahan secara individual.
                        </div>
                    </div>

                    @foreach($modules as $moduleKey => $module)
                        <div class="space-y-1.5 pb-2.5 border-b border-slate-100 last:border-0">
                            <p class="font-bold text-[11px] text-indigo-950 flex items-center gap-1.5">
                                <i class="{{ $module['icon'] }} text-[10px] text-indigo-600"></i>
                                <span>{{ $module['name'] }}</span>
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 pl-3">
                                @foreach($module['permissions'] as $permKey => $permMeta)
                                    <label class="perm-item-wrapper flex items-start justify-between gap-2 text-[11px] text-slate-700 cursor-pointer select-none p-1.5 rounded-lg border border-transparent transition">
                                        <div class="flex items-start gap-2 min-w-0">
                                            <input type="checkbox" 
                                                   name="direct_permissions[]" 
                                                   value="{{ $permKey }}" 
                                                   id="edit_perm_{{ str_replace('.', '_', $permKey) }}"
                                                   onchange="onDirectPermCheckboxChange(this)"
                                                   class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 user-direct-perm-checkbox flex-shrink-0">
                                            <div class="min-w-0">
                                                <span class="font-medium text-slate-800 transition block truncate">
                                                    {{ $permMeta['label'] }}
                                                </span>
                                                <span class="font-mono text-[9px] text-slate-400 block">{{ $permKey }}</span>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 pt-0.5">
                                            <span id="badge_edit_perm_{{ str_replace('.', '_', $permKey) }}" class="hidden"></span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeEditUserModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: HAPUS PENGGUNA -->
<!-- ========================================================================= -->
<div id="deleteUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full shadow-2xl overflow-hidden p-6 text-center animate-scale-in">
        <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3 text-xl">
            <i class="fa-solid fa-trash-can"></i>
        </div>
        <h3 class="text-sm font-bold text-slate-800 mb-1">Hapus Pengguna</h3>
        <p class="text-xs text-slate-500 mb-5">
            Apakah Anda yakin ingin menghapus pengguna <strong id="deleteUserName" class="text-slate-800"></strong>? Tindakan ini tidak dapat dibatalkan.
        </p>

        <form id="deleteUserForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex items-center justify-center gap-2.5">
                <button type="button" onclick="closeDeleteUserModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    Ya, Hapus Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const rolePermissionsMap = @json($rolePermissionsMap ?? []);
    let currentUserDirectPerms = [];

    function previewUserImage(input, previewId, placeholderId) {
        const preview = document.getElementById(previewId);
        const placeholder = document.getElementById(placeholderId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (preview) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                if (placeholder) {
                    placeholder.classList.add('hidden');
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function openCreateUserModal() {
        document.getElementById('createUserModal').classList.remove('hidden');
        document.documentElement.classList.add('modal-open');
    }

    function closeCreateUserModal() {
        document.getElementById('createUserModal').classList.add('hidden');
        document.documentElement.classList.remove('modal-open');
        const input = document.getElementById('addUserPhotoInput');
        const preview = document.getElementById('addUserPhotoPreview');
        const placeholder = document.getElementById('addUserPhotoPlaceholder');
        if (input) input.value = '';
        if (preview) {
            preview.src = '';
            preview.classList.add('hidden');
        }
        if (placeholder) {
            placeholder.classList.remove('hidden');
        }
    }

    function toggleDirectPermsAccordion() {
        const container = document.getElementById('directPermsContainer');
        const chevron = document.getElementById('directPermsChevron');
        if (container) {
            container.classList.toggle('hidden');
        }
        if (chevron) {
            chevron.classList.toggle('rotate-180');
        }
    }

    function updatePermissionCheckboxes() {
        const roleSelect = document.getElementById('edit_role');
        const selectedRole = roleSelect ? roleSelect.value : '';
        const rolePerms = (rolePermissionsMap && rolePermissionsMap[selectedRole]) ? rolePermissionsMap[selectedRole] : [];

        document.querySelectorAll('.user-direct-perm-checkbox').forEach(cb => {
            const permKey = cb.value;
            const safeKey = permKey.replace(/\./g, '_');
            const badge = document.getElementById('badge_edit_perm_' + safeKey);
            const wrapper = cb.closest('.perm-item-wrapper');

            if (rolePerms.includes(permKey)) {
                // Izin merupakan bawaan role utama
                cb.checked = true;
                cb.disabled = true;
                cb.title = "Izin bawaan dari role " + selectedRole;
                if (badge) {
                    badge.className = "inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200";
                    badge.textContent = "✓ Bawaan Role";
                    badge.classList.remove('hidden');
                }
                if (wrapper) {
                    wrapper.classList.add('bg-emerald-50/50');
                    wrapper.classList.remove('bg-indigo-50/50');
                }
            } else {
                // Bukan bawaan role, bisa diaktifkan/dinonaktifkan sebagai izin khusus
                cb.disabled = false;
                cb.title = "";
                const isDirect = currentUserDirectPerms.includes(permKey);
                cb.checked = isDirect;
                if (badge) {
                    if (isDirect) {
                        badge.className = "inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200";
                        badge.textContent = "+ Izin Khusus";
                        badge.classList.remove('hidden');
                    } else {
                        badge.className = "hidden";
                        badge.textContent = "";
                    }
                }
                if (wrapper) {
                    if (isDirect) {
                        wrapper.classList.add('bg-indigo-50/50');
                        wrapper.classList.remove('bg-emerald-50/50');
                    } else {
                        wrapper.classList.remove('bg-emerald-50/50', 'bg-indigo-50/50');
                    }
                }
            }
        });
    }

    function onDirectPermCheckboxChange(cb) {
        if (cb.disabled) return;
        const permKey = cb.value;
        const safeKey = permKey.replace(/\./g, '_');
        const badge = document.getElementById('badge_edit_perm_' + safeKey);
        const wrapper = cb.closest('.perm-item-wrapper');

        if (cb.checked) {
            if (!currentUserDirectPerms.includes(permKey)) {
                currentUserDirectPerms.push(permKey);
            }
            if (badge) {
                badge.className = "inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200";
                badge.textContent = "+ Izin Khusus";
                badge.classList.remove('hidden');
            }
            if (wrapper) {
                wrapper.classList.add('bg-indigo-50/50');
                wrapper.classList.remove('bg-emerald-50/50');
            }
        } else {
            currentUserDirectPerms = currentUserDirectPerms.filter(p => p !== permKey);
            if (badge) {
                badge.className = "hidden";
                badge.textContent = "";
            }
            if (wrapper) {
                wrapper.classList.remove('bg-indigo-50/50');
            }
        }
    }

    function openEditUserModal(user, primaryRole, userDirectPerms) {
        document.getElementById('editUserForm').action = `/admin/users/${user.id}`;
        document.getElementById('edit_name').value = user.name;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = primaryRole;
        const editGateEl = document.getElementById('edit_gate_id');
        if (editGateEl) editGateEl.value = user.gate_id || '';

        const emp = user.employee || {};
        const editUnitKerja = document.getElementById('edit_unit_kerja');
        if (editUnitKerja) editUnitKerja.value = emp.unit_kerja || '';
        const editDivision = document.getElementById('edit_division');
        if (editDivision) editDivision.value = emp.division || '';
        const editPosition = document.getElementById('edit_position');
        if (editPosition) editPosition.value = emp.position || '';
        const editIsOperator = document.getElementById('edit_is_operator');
        if (editIsOperator) editIsOperator.value = (emp.is_operator ? '1' : '0');

        const editPhotoPreview = document.getElementById('editUserPhotoPreview');
        const editPhotoPlaceholder = document.getElementById('editUserPhotoPlaceholder');
        const editPhotoInput = document.getElementById('editUserPhotoInput');
        if (editPhotoInput) editPhotoInput.value = '';
        if (user.photo_url) {
            if (editPhotoPreview) {
                editPhotoPreview.src = user.photo_url;
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

        currentUserDirectPerms = Array.isArray(userDirectPerms) ? [...userDirectPerms] : [];
        updatePermissionCheckboxes();

        document.getElementById('editUserModal').classList.remove('hidden');
        document.documentElement.classList.add('modal-open');
    }

    function closeEditUserModal() {
        document.getElementById('editUserModal').classList.add('hidden');
        document.documentElement.classList.remove('modal-open');
        const editPhotoInput = document.getElementById('editUserPhotoInput');
        const editPhotoPreview = document.getElementById('editUserPhotoPreview');
        const editPhotoPlaceholder = document.getElementById('editUserPhotoPlaceholder');
        if (editPhotoInput) editPhotoInput.value = '';
        if (editPhotoPreview) {
            editPhotoPreview.src = '';
            editPhotoPreview.classList.add('hidden');
        }
        if (editPhotoPlaceholder) editPhotoPlaceholder.classList.remove('hidden');
    }

    function openDeleteUserModal(userId, userName) {
        document.getElementById('deleteUserForm').action = `/admin/users/${userId}`;
        document.getElementById('deleteUserName').textContent = userName;

        document.getElementById('deleteUserModal').classList.remove('hidden');
        document.documentElement.classList.add('modal-open');
    }

    function closeDeleteUserModal() {
        document.getElementById('deleteUserModal').classList.add('hidden');
        document.documentElement.classList.remove('modal-open');
    }

    function toggleSortDropdown() {
        const menu = document.getElementById('sortDropdownMenu');
        const chevron = document.getElementById('sortDropdownChevron');
        if (menu) {
            menu.classList.toggle('hidden');
        }
        if (chevron) {
            chevron.classList.toggle('rotate-180');
        }
    }

    function applyUserSort(sortValue) {
        const sortInput = document.getElementById('userSortInput');
        const form = document.getElementById('usersFilterForm');
        if (sortInput && form) {
            sortInput.value = sortValue;
            form.submit();
        }
    }

    document.addEventListener('click', function(e) {
        const container = document.getElementById('sortDropdownContainer');
        const menu = document.getElementById('sortDropdownMenu');
        const chevron = document.getElementById('sortDropdownChevron');
        if (container && menu && !container.contains(e.target)) {
            menu.classList.add('hidden');
            if (chevron) {
                chevron.classList.remove('rotate-180');
            }
        }
    });
</script>
@endsection
