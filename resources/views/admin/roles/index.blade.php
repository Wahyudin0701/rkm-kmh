@extends('layouts.app')

@section('title', 'Matriks Peran & Hak Akses Pengguna - Super Admin | PT Kerinci Merangin Hidro')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2.5">
                <span class="w-2.5 h-7 rounded-full bg-indigo-600 inline-block"></span>
                <span>Matriks Peran & Hak Akses Pengguna</span>
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

    <!-- Ringkasan Role Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        @foreach($roles as $role)
            @php
                $isCoreRole = in_array($role->name, ['super_admin', 'hse_admin', 'qma_admin', 'pg_admin', 'gate_officer']);
                $badgeColor = match($role->name) {
                    'super_admin' => 'bg-indigo-600 text-white',
                    'hse_admin' => 'bg-emerald-600 text-white',
                    'qma_admin' => 'bg-blue-600 text-white',
                    'pg_admin' => 'bg-purple-600 text-white',
                    'gate_officer' => 'bg-amber-600 text-white',
                    default => 'bg-slate-700 text-white'
                };
            @endphp
            <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $badgeColor }}"></span>
                        @if(!$isCoreRole)
                            <button type="button" 
                                    onclick='openDeleteRoleModal({{ $role->id }}, "{{ $role->name }}")' 
                                    class="text-slate-400 hover:text-rose-600 transition" 
                                    title="Hapus Role Kustom">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        @else
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Peran Utama</span>
                        @endif
                    </div>
                    <h2 class="text-xs font-bold text-slate-800 capitalize">{{ str_replace('_', ' ', $role->name) }}</h2>
                    <p class="text-[10px] text-slate-400 mt-0.5 font-mono">{{ $role->name }}</p>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px]">
                    <span class="text-slate-500">Jumlah Hak Akses:</span>
                    @if($role->name === 'super_admin')
                        <div class="relative group/tip inline-flex">
                            <span class="font-bold text-indigo-700 cursor-help flex items-center gap-1">
                                <span>Akses Lengkap</span>
                                <i class="fa-solid fa-circle-info text-[10px] text-indigo-400"></i>
                            </span>
                            <div class="absolute right-0 bottom-full mb-2 hidden group-hover/tip:block z-50 w-72 p-3 bg-slate-900 text-white text-xs rounded-xl shadow-xl pointer-events-none">
                                <div class="flex items-start gap-2">
                                    <i class="fa-solid fa-crown text-amber-400 text-xs mt-0.5 flex-shrink-0"></i>
                                    <div>
                                        <strong class="font-bold text-white text-xs block">Hak Akses Penuh Super Administrator</strong>
                                        <p class="text-slate-300 text-[11px] mt-0.5 leading-relaxed">
                                            Secara otomatis memiliki akses penuh ke seluruh menu dan modul sistem tanpa batasan.
                                        </p>
                                    </div>
                                </div>
                                <div class="absolute -bottom-1 right-3 w-2 h-2 bg-slate-900 rotate-45"></div>
                            </div>
                        </div>
                    @else
                        <span class="font-bold text-indigo-700">
                            {{ $role->permissions->count() . ' Izin' }}
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Matriks Role & Permissions Table -->
    <div class="space-y-6">
        @foreach($roles as $role)
            <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 capitalize flex items-center gap-2">
                                <span>Peran: {{ str_replace('_', ' ', $role->name) }}</span>
                                @if($role->name === 'super_admin')
                                    <div class="relative group/satip inline-flex">
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-indigo-100 text-indigo-800 flex items-center gap-1 cursor-help">
                                            <i class="fa-solid fa-crown text-[8px]"></i>
                                            <span>AKSES LENGKAP</span>
                                            <i class="fa-solid fa-circle-info text-[9px] text-indigo-500"></i>
                                        </span>
                                        <div class="absolute left-0 top-full mt-2 hidden group-hover/satip:block z-50 w-72 sm:w-80 p-3 bg-slate-900 text-white text-xs rounded-xl shadow-xl pointer-events-none">
                                            <div class="flex items-start gap-2.5">
                                                <i class="fa-solid fa-crown text-amber-400 text-xs mt-0.5 flex-shrink-0"></i>
                                                <div>
                                                    <strong class="font-bold text-white text-xs block">Hak Akses Penuh Super Administrator</strong>
                                                    <p class="text-slate-300 text-[11px] mt-0.5 leading-relaxed">
                                                        Akun dengan peran <strong>Super Administrator</strong> secara otomatis memiliki akses penuh ke seluruh menu dan modul sistem tanpa batasan. Pilihan centang di bawah digunakan untuk mengatur batasan akses peran staf operasional lainnya.
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="absolute -top-1 left-4 w-2 h-2 bg-slate-900 rotate-45"></div>
                                        </div>
                                    </div>
                                @endif
                            </h2>
                            <p class="text-[11px] text-slate-400">Atur centang hak akses menu yang diizinkan untuk peran pengguna ini.</p>
                        </div>
                    </div>

                    @if($role->name !== 'super_admin')
                        <button type="submit" 
                                form="form-role-{{ $role->id }}" 
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition self-end sm:self-auto">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Simpan Hak Akses</span>
                        </button>
                    @endif
                </div>

                <form id="form-role-{{ $role->id }}" 
                      action="{{ route('admin.roles.permissions.update', $role->id) }}" 
                      method="POST" 
                      class="p-5">
                    @csrf

                    @php
                        $rolePermNames = $role->permissions->pluck('name')->toArray();
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($modules as $moduleKey => $module)
                            <div class="bg-slate-50/70 border border-slate-200/60 rounded-xl p-4 space-y-3">
                                <h3 class="text-xs font-bold text-slate-800 flex items-center justify-between pb-2 border-b border-slate-200/60">
                                    <span class="flex items-center gap-1.5">
                                        <i class="{{ $module['icon'] }} text-indigo-600 text-xs"></i>
                                        <span>{{ $module['name'] }}</span>
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-semibold">{{ count($module['permissions']) }} hak akses</span>
                                </h3>

                                <div class="space-y-2">
                                    @foreach($module['permissions'] as $permKey => $permMeta)
                                        @php
                                            $isChecked = $role->name === 'super_admin' || in_array($permKey, $rolePermNames);
                                        @endphp
                                        <label class="flex items-start gap-2.5 text-xs text-slate-700 cursor-pointer select-none group p-2 rounded-lg hover:bg-white hover:shadow-xs border border-transparent hover:border-slate-200/60 transition">
                                            <input type="checkbox" 
                                                   name="permissions[]" 
                                                   value="{{ $permKey }}" 
                                                   {{ $isChecked ? 'checked' : '' }} 
                                                   {{ $role->name === 'super_admin' ? 'disabled' : '' }}
                                                   class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 transition">
                                            <div>
                                                <span class="text-xs font-bold text-slate-800 group-hover:text-indigo-600 transition block">
                                                    {{ $permMeta['label'] }}
                                                </span>
                                                <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                                    {{ $permMeta['description'] }}
                                                </p>
                                                <span class="font-mono text-[10px] text-slate-400 mt-1 block">
                                                    {{ $permKey }}
                                                </span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
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
