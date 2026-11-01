@extends('layouts.app')

@section('title', 'Matriks Peran & Hak Akses Pengguna - Super Admin | PT Kerinci Merangin Hidro')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2.5">
                <span class="w-2.5 h-7 rounded-full bg-indigo-600 inline-block"></span>
                <span>Matriks Role & Hak Akses Pengguna</span>
                <!-- Tooltip Super Admin Priviledge -->
                <div class="relative group/tip inline-flex">
                    <button type="button" 
                            class="w-6 h-6 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs transition cursor-pointer" 
                            title="Informasi Hak Akses Super Administrator">
                        <i class="fa-solid fa-crown text-[10px]"></i>
                    </button>
                    <div class="absolute left-0 sm:left-1/2 sm:-translate-x-1/2 top-full mt-2 hidden group-hover/tip:block z-50 w-72 sm:w-80 p-3.5 bg-slate-900 text-white rounded-xl shadow-xl text-xs font-normal pointer-events-none transition duration-150">
                        <div class="flex items-start gap-2.5">
                            <i class="fa-solid fa-crown text-amber-400 text-xs mt-0.5 flex-shrink-0"></i>
                            <div>
                                <strong class="font-bold text-white block mb-0.5">Hak Akses Penuh Super Administrator</strong>
                                <span class="text-slate-300 text-[11px] leading-relaxed block">
                                    Akun dengan peran <strong>Super Administrator</strong> secara otomatis memiliki akses penuh ke seluruh menu dan modul sistem tanpa batasan. Pilihan centang di bawah digunakan untuk mengatur batasan akses peran staf operasional lainnya.
                                </span>
                            </div>
                        </div>
                        <div class="absolute -top-1 left-3 sm:left-1/2 sm:-translate-x-1/2 w-2 h-2 bg-slate-900 rotate-45"></div>
                    </div>
                </div>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Atur wewenang dan batasan menu untuk setiap jabatan atau peran pengguna di lingkungan PT Kerinci Merangin Hidro.
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" 
                    onclick="openCreateRoleModal()" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                <i class="fa-solid fa-plus text-sm"></i>
                <span>Tambah Role Baru</span>
            </button>
            <a href="{{ route('admin.users.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold shadow-sm transition">
                <i class="fa-solid fa-users text-indigo-600 text-sm"></i>
                <span>Kelola Pengguna</span>
            </a>
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

    <!-- Selector Tab Role Interaktif (Compact & User Friendly) -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                <i class="fa-solid fa-id-badge text-indigo-600"></i>
                <span>Pilih Peran Pengguna (Role):</span>
            </span>
            <span class="text-[11px] text-slate-400">Pilih salah satu peran untuk melihat dan mengatur izinnya</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            @foreach($roles as $role)
                @php
                    $isCoreRole = in_array($role->name, ['super_admin', 'hse_admin', 'qma_admin', 'pg_admin', 'gate_officer']);
                    $dotColor = match($role->name) {
                        'super_admin' => 'bg-indigo-600',
                        'hse_admin' => 'bg-emerald-600',
                        'qma_admin' => 'bg-blue-600',
                        'pg_admin' => 'bg-purple-600',
                        'gate_officer' => 'bg-amber-600',
                        default => 'bg-slate-700'
                    };
                @endphp
                <button type="button" 
                        onclick="switchRoleTab({{ $role->id }})" 
                        id="tab-btn-{{ $role->id }}" 
                        class="role-tab-btn text-left p-3.5 rounded-2xl border transition duration-200 relative group flex flex-col justify-between {{ $loop->first ? 'border-indigo-600 bg-indigo-50/60 ring-2 ring-indigo-500/20 shadow-sm' : 'border-slate-200/80 bg-white hover:bg-slate-50 hover:border-slate-300' }}">
                    <div class="w-full">
                        <div class="flex items-center justify-between mb-2">
                            <span class="w-2.5 h-2.5 rounded-full {{ $dotColor }}"></span>
                            @if(!$isCoreRole)
                                <span onclick="event.stopPropagation(); openDeleteRoleModal({{ $role->id }}, '{{ $role->name }}')" 
                                      class="text-slate-300 hover:text-rose-600 p-1 -mr-1 transition" 
                                      title="Hapus Role Kustom">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </span>
                            @else
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Peran Utama</span>
                            @endif
                        </div>
                        <h2 class="text-xs font-bold text-slate-800 capitalize leading-snug">
                            {{ str_replace('_', ' ', $role->name) }}
                        </h2>
                        <p class="text-[10px] text-slate-400 font-mono mt-0.5 truncate">{{ $role->name }}</p>
                    </div>

                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] w-full">
                        <span class="text-slate-500">Izin Akses:</span>
                        @if($role->name === 'super_admin')
                            <span class="font-black text-indigo-700 flex items-center gap-1">
                                <i class="fa-solid fa-crown text-[9px] text-amber-500"></i>
                                <span>Penuh</span>
                            </span>
                        @else
                            <span class="font-bold text-indigo-700 role-perm-badge-count" data-role-id="{{ $role->id }}">
                                {{ $role->permissions->count() }} Izin
                            </span>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Kontainer Panel Hak Akses Per Role (Hanya Role Aktif yang Ditampilkan) -->
    <div class="space-y-6">
        @foreach($roles as $role)
            @php
                $rolePermNames = $role->permissions->pluck('name')->toArray();
                $isSuperAdmin = $role->name === 'super_admin';
            @endphp
            <div id="role-panel-{{ $role->id }}" 
                 class="role-panel bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden {{ $loop->first ? '' : 'hidden' }}">
                
                <!-- Panel Header Sticky/Top Bar -->
                <div class="px-5 py-4 bg-slate-50/80 border-b border-slate-200/70 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-start sm:items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-base font-extrabold text-slate-800 capitalize">
                                    Pengaturan Hak Akses: {{ str_replace('_', ' ', $role->name) }}
                                </h2>
                                @if($isSuperAdmin)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-200 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-crown text-[9px]"></i>
                                        <span>BYPASS PENUH</span>
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                        Peran: <code class="font-mono">{{ $role->name }}</code>
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                @if($isSuperAdmin)
                                    Peran ini adalah tingkat tertinggi sistem. Seluruh hak akses otomatis aktif secara permanen.
                                @else
                                    Centang fitur atau menu yang boleh diakses dan dijalankan oleh pengguna dengan peran ini.
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Tombol Aksi & Simpan -->
                    <div class="flex items-center gap-2 self-end md:self-auto flex-wrap">
                        @if(!$isSuperAdmin)
                            <button type="button" 
                                    onclick="toggleAllRolePermissions({{ $role->id }}, true)" 
                                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
                                    title="Centang semua hak akses untuk peran ini">
                                <i class="fa-solid fa-check-double text-indigo-600"></i>
                                <span>Pilih Semua</span>
                            </button>
                            <button type="button" 
                                    onclick="toggleAllRolePermissions({{ $role->id }}, false)" 
                                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
                                    title="Hilangkan semua centang untuk peran ini">
                                <i class="fa-solid fa-rotate-left text-slate-500"></i>
                                <span>Kosongkan</span>
                            </button>
                            <button type="submit" 
                                    form="form-role-{{ $role->id }}" 
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-600/20 transition">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Simpan Perubahan</span>
                            </button>
                        @endif
                    </div>
                </div>

                @if($isSuperAdmin)
                    <!-- Notifikasi Super Admin Bypass -->
                    <div class="m-5 p-4 rounded-xl bg-amber-50/70 border border-amber-200/80 flex items-start gap-3 text-amber-900 text-xs">
                        <i class="fa-solid fa-circle-exclamation text-amber-600 text-base mt-0.5 flex-shrink-0"></i>
                        <div>
                            <strong class="font-bold block">Hak Akses Super Administrator Tidak Terbatas</strong>
                            <p class="text-amber-800 text-[11px] mt-0.5 leading-relaxed">
                                Sesuai arsitektur keamanan Spatie RBAC, pengguna dengan peran <code>super_admin</code> memiliki hak <em>Gate::before bypass</em> yang mengizinkan akses ke seluruh API, controller, rute, dan menu tanpa perlu diverifikasi satu per satu.
                            </p>
                        </div>
                    </div>
                @else
                    <!-- Search Filter Bar per Role -->
                    <div class="px-5 pt-4 pb-2 flex flex-col sm:flex-row items-center justify-between gap-3 border-b border-slate-100">
                        <div class="relative w-full sm:w-80">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" 
                                   id="search-role-{{ $role->id }}" 
                                   oninput="filterRolePermissions({{ $role->id }}, this.value)" 
                                   placeholder="Cari izin / nama menu (misal: p2h, simper, gate)..." 
                                   class="w-full pl-9 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:border-indigo-500 transition">
                            <button type="button" 
                                    onclick="clearRoleSearch({{ $role->id }})" 
                                    id="clear-search-{{ $role->id }}" 
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 hidden text-xs">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </button>
                        </div>
                        <div class="text-[11px] text-slate-500 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Total Terpilih:</span>
                            <strong class="font-bold text-slate-800" id="selected-stat-{{ $role->id }}">{{ count($rolePermNames) }}</strong>
                            <span class="text-slate-400">izin aktif</span>
                        </div>
                    </div>
                @endif

                <!-- Form Modul & Permission Checklists -->
                <form id="form-role-{{ $role->id }}" 
                      action="{{ route('admin.roles.permissions.update', $role->id) }}" 
                      method="POST" 
                      class="p-5">
                    @csrf

                    <div id="module-grid-{{ $role->id }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach($modules as $moduleKey => $module)
                            @php
                                $totalInModule = count($module['permissions']);
                                $activeInModule = 0;
                                foreach($module['permissions'] as $pKey => $pMeta) {
                                    if ($isSuperAdmin || in_array($pKey, $rolePermNames)) {
                                        $activeInModule++;
                                    }
                                }
                            @endphp
                            <div class="perm-module-card bg-slate-50/70 border border-slate-200/70 rounded-xl p-4 flex flex-col justify-between space-y-3 transition" 
                                 data-module-key="{{ $moduleKey }}">
                                <div>
                                    <!-- Header Modul -->
                                    <div class="flex items-center justify-between pb-2.5 border-b border-slate-200/60 mb-2.5">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                                                <i class="{{ $module['icon'] }}"></i>
                                            </div>
                                            <h3 class="text-xs font-bold text-slate-800 leading-snug">
                                                {{ $module['name'] }}
                                            </h3>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-slate-500 bg-white px-2 py-0.5 rounded-md border border-slate-200/80 module-counter" 
                                                  id="mod-count-{{ $role->id }}-{{ $moduleKey }}">
                                                {{ $activeInModule }}/{{ $totalInModule }}
                                            </span>

                                            @if(!$isSuperAdmin)
                                                <div class="flex items-center space-x-1">
                                                    <button type="button" 
                                                            onclick="toggleModuleCheckboxes({{ $role->id }}, '{{ $moduleKey }}', true)" 
                                                            class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 px-1 py-0.5 hover:bg-indigo-50 rounded transition" 
                                                            title="Centang semua di modul ini">
                                                        Semua
                                                    </button>
                                                    <span class="text-slate-300 text-[10px]">|</span>
                                                    <button type="button" 
                                                            onclick="toggleModuleCheckboxes({{ $role->id }}, '{{ $moduleKey }}', false)" 
                                                            class="text-[10px] font-bold text-slate-500 hover:text-slate-700 px-1 py-0.5 hover:bg-slate-200/50 rounded transition" 
                                                            title="Batalkan centang modul ini">
                                                        Batal
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Daftar Permission -->
                                    <div class="space-y-1.5">
                                        @foreach($module['permissions'] as $permKey => $permMeta)
                                            @php
                                                $isChecked = $isSuperAdmin || in_array($permKey, $rolePermNames);
                                            @endphp
                                            <label class="perm-item flex items-start gap-2.5 text-xs text-slate-700 cursor-pointer select-none group p-2 rounded-lg hover:bg-white hover:shadow-xs border border-transparent hover:border-slate-200/60 transition {{ $isChecked ? 'bg-indigo-50/20' : '' }}" 
                                                   data-search-text="{{ strtolower($permMeta['label'] . ' ' . $permMeta['description'] . ' ' . $permKey . ' ' . $module['name']) }}">
                                                <input type="checkbox" 
                                                       name="permissions[]" 
                                                       value="{{ $permKey }}" 
                                                       data-module="{{ $moduleKey }}"
                                                       onchange="onPermissionChange({{ $role->id }}, '{{ $moduleKey }}')"
                                                       {{ $isChecked ? 'checked' : '' }} 
                                                       {{ $isSuperAdmin ? 'disabled' : '' }}
                                                       class="perm-checkbox-{{ $role->id }} mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 transition">
                                                <div class="flex-1 min-w-0">
                                                    <span class="text-xs font-bold text-slate-800 group-hover:text-indigo-600 transition block">
                                                        {{ $permMeta['label'] }}
                                                    </span>
                                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                                        {{ $permMeta['description'] }}
                                                    </p>
                                                    <span class="font-mono text-[9px] text-slate-400 mt-0.5 block truncate">
                                                        {{ $permKey }}
                                                    </span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Empty Search Result State -->
                    <div id="empty-search-{{ $role->id }}" class="hidden py-12 text-center">
                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-base">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <h4 class="text-xs font-bold text-slate-700">Tidak ada hak akses yang cocok</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Coba gunakan kata kunci pencarian yang lain.</p>
                        <button type="button" onclick="clearRoleSearch({{ $role->id }})" class="mt-3 px-3 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-xs font-bold transition">
                            Reset Pencarian
                        </button>
                    </div>

                    @if(!$isSuperAdmin)
                        <!-- Bottom Action Bar -->
                        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                            <p class="text-xs text-slate-400">
                                Pastikan klik tombol <strong>Simpan Perubahan</strong> untuk menerapkan hak akses ke pengguna terkait.
                            </p>
                            <button type="submit" 
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-600/20 transition">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Simpan Perubahan</span>
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        @endforeach
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: TAMBAH ROLE BARU -->
<!-- ========================================================================= -->
<div id="createRoleModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full shadow-2xl overflow-hidden animate-scale-in">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-shield-plus text-indigo-600"></i>
                Tambah Role Baru
            </h3>
            <button type="button" onclick="closeCreateRoleModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <form action="{{ route('admin.roles.store') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Role *</label>
                <input type="text" name="name" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white" placeholder="Contoh: auditor_internal">
                <p class="text-[10px] text-slate-400 mt-1">Gunakan huruf kecil atau underscore (contoh: staff_logistik).</p>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeCreateRoleModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    Simpan Role
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: HAPUS ROLE -->
<!-- ========================================================================= -->
<div id="deleteRoleModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full shadow-2xl overflow-hidden p-6 text-center animate-scale-in">
        <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3 text-xl">
            <i class="fa-solid fa-trash-can"></i>
        </div>
        <h3 class="text-sm font-bold text-slate-800 mb-1">Hapus Role Kustom</h3>
        <p class="text-xs text-slate-500 mb-5">
            Apakah Anda yakin ingin menghapus role <strong id="deleteRoleName" class="text-slate-800"></strong>?
        </p>

        <form id="deleteRoleForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex items-center justify-center gap-2.5">
                <button type="button" onclick="closeDeleteRoleModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    Ya, Hapus Role
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Tab switching antar Role
    function switchRoleTab(roleId) {
        // Sembunyikan semua panel role
        document.querySelectorAll('.role-panel').forEach(panel => {
            panel.classList.add('hidden');
        });

        // Non-aktifkan semua styling tombol tab
        document.querySelectorAll('.role-tab-btn').forEach(btn => {
            btn.classList.remove('border-indigo-600', 'bg-indigo-50/60', 'ring-2', 'ring-indigo-500/20', 'shadow-sm');
            btn.classList.add('border-slate-200/80', 'bg-white');
        });

        // Tampilkan panel role terpilih
        const activePanel = document.getElementById(`role-panel-${roleId}`);
        if (activePanel) {
            activePanel.classList.remove('hidden');
        }

        // Aktifkan styling tab terpilih
        const activeBtn = document.getElementById(`tab-btn-${roleId}`);
        if (activeBtn) {
            activeBtn.classList.remove('border-slate-200/80', 'bg-white');
            activeBtn.classList.add('border-indigo-600', 'bg-indigo-50/60', 'ring-2', 'ring-indigo-500/20', 'shadow-sm');
        }
    }

    // Toggle semua checkbox untuk role tertentu
    function toggleAllRolePermissions(roleId, checked) {
        const checkboxes = document.querySelectorAll(`.perm-checkbox-${roleId}`);
        checkboxes.forEach(cb => {
            if (!cb.disabled) {
                cb.checked = checked;
            }
        });
        updateRoleStats(roleId);
    }

    // Toggle checkbox per modul
    function toggleModuleCheckboxes(roleId, moduleKey, checked) {
        const checkboxes = document.querySelectorAll(`#form-role-${roleId} input[data-module="${moduleKey}"]`);
        checkboxes.forEach(cb => {
            if (!cb.disabled) {
                cb.checked = checked;
            }
        });
        onPermissionChange(roleId, moduleKey);
    }

    // Handler ketika ada checkbox yang berubah
    function onPermissionChange(roleId, moduleKey) {
        // Update counter per modul
        const modCheckboxes = document.querySelectorAll(`#form-role-${roleId} input[data-module="${moduleKey}"]`);
        const checkedInMod = Array.from(modCheckboxes).filter(cb => cb.checked).length;
        const totalInMod = modCheckboxes.length;
        
        const counterEl = document.getElementById(`mod-count-${roleId}-${moduleKey}`);
        if (counterEl) {
            counterEl.textContent = `${checkedInMod}/${totalInMod}`;
        }

        updateRoleStats(roleId);
    }

    // Update counter total hak akses aktif untuk suatu role
    function updateRoleStats(roleId) {
        const allCheckboxes = document.querySelectorAll(`.perm-checkbox-${roleId}`);
        const totalChecked = Array.from(allCheckboxes).filter(cb => cb.checked).length;

        // Update badge di header panel
        const statEl = document.getElementById(`selected-stat-${roleId}`);
        if (statEl) {
            statEl.textContent = totalChecked;
        }

        // Update badge di tombol tab atas
        const tabBadge = document.querySelector(`.role-perm-badge-count[data-role-id="${roleId}"]`);
        if (tabBadge) {
            tabBadge.textContent = `${totalChecked} Izin`;
        }

        // Update semua counter modul
        const modules = new Set();
        allCheckboxes.forEach(cb => {
            const m = cb.getAttribute('data-module');
            if (m) modules.add(m);
        });

        modules.forEach(m => {
            const modCheckboxes = document.querySelectorAll(`#form-role-${roleId} input[data-module="${m}"]`);
            const checkedInMod = Array.from(modCheckboxes).filter(cb => cb.checked).length;
            const totalInMod = modCheckboxes.length;
            const counterEl = document.getElementById(`mod-count-${roleId}-${m}`);
            if (counterEl) {
                counterEl.textContent = `${checkedInMod}/${totalInMod}`;
            }
        });
    }

    // Live Search Filter Hak Akses
    function filterRolePermissions(roleId, rawKeyword) {
        const keyword = (rawKeyword || '').toLowerCase().trim();
        const clearBtn = document.getElementById(`clear-search-${roleId}`);
        if (clearBtn) {
            clearBtn.classList.toggle('hidden', keyword.length === 0);
        }

        const panel = document.getElementById(`role-panel-${roleId}`);
        if (!panel) return;

        const moduleCards = panel.querySelectorAll('.perm-module-card');
        let totalVisibleCards = 0;

        moduleCards.forEach(card => {
            const items = card.querySelectorAll('.perm-item');
            let visibleInCard = 0;

            items.forEach(item => {
                const text = item.getAttribute('data-search-text') || '';
                if (!keyword || text.includes(keyword)) {
                    item.classList.remove('hidden');
                    visibleInCard++;
                } else {
                    item.classList.add('hidden');
                }
            });

            if (visibleInCard > 0) {
                card.classList.remove('hidden');
                totalVisibleCards++;
            } else {
                card.classList.add('hidden');
            }
        });

        const emptyState = document.getElementById(`empty-search-${roleId}`);
        const moduleGrid = document.getElementById(`module-grid-${roleId}`);
        if (emptyState && moduleGrid) {
            if (totalVisibleCards === 0 && keyword.length > 0) {
                emptyState.classList.remove('hidden');
                moduleGrid.classList.add('hidden');
            } else {
                emptyState.classList.add('hidden');
                moduleGrid.classList.remove('hidden');
            }
        }
    }

    function clearRoleSearch(roleId) {
        const input = document.getElementById(`search-role-${roleId}`);
        if (input) {
            input.value = '';
            filterRolePermissions(roleId, '');
            input.focus();
        }
    }

    // Modal Manajemen Role
    function openCreateRoleModal() {
        document.getElementById('createRoleModal').classList.remove('hidden');
        document.documentElement.classList.add('modal-open');
    }

    function closeCreateRoleModal() {
        document.getElementById('createRoleModal').classList.add('hidden');
        document.documentElement.classList.remove('modal-open');
    }

    function openDeleteRoleModal(roleId, roleName) {
        document.getElementById('deleteRoleForm').action = `/admin/roles/${roleId}`;
        document.getElementById('deleteRoleName').textContent = roleName;

        document.getElementById('deleteRoleModal').classList.remove('hidden');
        document.documentElement.classList.add('modal-open');
    }

    function closeDeleteRoleModal() {
        document.getElementById('deleteRoleModal').classList.add('hidden');
        document.documentElement.classList.remove('modal-open');
    }
</script>
@endsection
