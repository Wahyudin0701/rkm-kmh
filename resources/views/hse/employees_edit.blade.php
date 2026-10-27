@extends('layouts.app')

@section('title', 'Kelola SIMPER - ' . $employee->name)

@section('content')
<div class="w-full space-y-6">
    <!-- Breadcrumb & Navigasi Kembali -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('hse.dashboard') }}" class="hover:text-emerald-700 transition">Dashboard HSE</a>
                <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
                <a href="{{ route('hse.employees.index') }}" class="hover:text-emerald-700 transition">Data SIMPER</a>
                <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
                <span class="text-slate-800 font-bold">Kelola SIMPER</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <span>Kelola SIMPER Personil</span>
                @if($employee->isSukon())
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        Sukon
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        Karyawan Internal
                    </span>
                @endif
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('hse.employees.index') }}" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-2xs">
                <i class="fa-solid fa-arrow-left text-[11px]"></i>
                <span>Kembali ke Daftar</span>
            </a>
            @can('hse.employees.delete')
            <button type="button" onclick="openDeleteEmployeeModal()" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition shadow-2xs cursor-pointer">
                <i class="fa-regular fa-trash-can text-xs"></i>
                <span>Hapus Karyawan</span>
            </button>
            @endcan
        </div>
    </div>

    <!-- Form Utama Edit Data SIMPER Personil -->
    <form action="{{ route('hse.employees.update', $employee->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- KOLOM KIRI: Profil & Status Personil (5 Kolom) -->
            <div class="lg:col-span-5 space-y-5">
                <!-- Card 1: Data Identitas & Foto -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">PROFIL PERSONIL</span>
                        <span class="text-[10px] font-semibold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-full">
                            <i class="fa-solid fa-lock text-[9px] mr-1 text-slate-400"></i>Master Super Admin
                        </span>
                    </div>

                    <!-- Foto Profil -->
                    <div class="flex items-center space-x-3.5 p-3 rounded-xl border border-slate-200 bg-slate-50/50">
                        <div class="w-14 h-14 rounded-full border border-slate-200 bg-white flex items-center justify-center overflow-hidden flex-shrink-0 shadow-2xs">
                            @if($employee->photo_url)
                                <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full bg-emerald-50 text-[#1b5e20] flex items-center justify-center text-lg font-bold">
                                    {{ strtoupper(substr($employee->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Ganti Foto Profil</label>
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/jpg,image/webp" 
                                   class="block w-full text-[11px] text-slate-500 file:mr-2.5 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-emerald-50 file:text-[#1b5e20] hover:file:bg-emerald-100 cursor-pointer">
                        </div>
                    </div>

                    <!-- Atribut Identitas Readonly -->
                    <div class="grid grid-cols-2 gap-3 text-xs bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                        <div>
                            <span class="text-slate-400 block text-[11px] font-medium">NIK:</span>
                            <span class="font-bold text-slate-900 font-mono text-sm">{{ $employee->nik }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px] font-medium">Nama Lengkap:</span>
                            <span class="font-bold text-slate-900 text-sm">{{ $employee->name }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px] font-medium">Unit Kerja:</span>
                            <span class="font-semibold text-slate-700">{{ $employee->unit_kerja ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px] font-medium">Divisi / Jabatan:</span>
                            <span class="font-semibold text-slate-700">{{ $employee->division ?? '-' }} / {{ $employee->position ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Pengaturan Status & Tipe Driver -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block pb-2 border-b border-slate-100">
                        PENGATURAN STATUS HSE
                    </span>

                    <!-- Jenis Personil / Karyawan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Jenis Personil / Karyawan <span class="text-rose-500">*</span>
                        </label>
                        <select name="employee_type" id="viewEmployeeType" onchange="toggleViewCustomEmployeeType(this.value)" required 
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                            <option value="Karyawan" {{ $employee->isKaryawan() ? 'selected' : '' }}>Karyawan (Internal KMH)</option>
                            <option value="Sukon" {{ $employee->isSukon() ? 'selected' : '' }}>Sukon (Subkontraktor)</option>
                            @if(isset($employeeTypes))
                                @foreach($employeeTypes as $t)
                                    @if($t !== 'Karyawan' && $t !== 'Sukon')
                                        <option value="{{ $t }}" {{ $employee->employee_type === $t ? 'selected' : '' }}>{{ $t }}</option>
                                    @endif
                                @endforeach
                            @endif
                            <option value="__CUSTOM__">+ Ketik Jenis Baru...</option>
                        </select>
                        <div id="viewCustomEmployeeTypeWrapper" class="hidden mt-2">
                            <input type="text" name="custom_employee_type" id="viewCustomEmployeeTypeInput" placeholder="Ketik jenis personil baru..." 
                                   class="w-full text-xs px-3.5 py-2 rounded-xl border border-emerald-300 bg-emerald-50/40 text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20]">
                        </div>
                    </div>

                    <!-- Unit Kerja (Fleksibel) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Unit Kerja <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="unit_kerja" list="unitKerjaSuggestions" required 
                               value="{{ old('unit_kerja', $employee->unit_kerja) }}"
                               placeholder="Contoh: Umum, HSE, dll"
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                    </div>

                    <!-- Status Operator / Driver -->
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Operator / Driver <span class="text-rose-500">*</span>
                        </label>
                        <select name="is_operator" id="viewOperator" onchange="toggleViewSimperInput(this.value)" required 
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                            <option value="1" {{ $employee->is_operator ? 'selected' : '' }}>YA (Aktif Mengemudi)</option>
                            <option value="0" {{ !$employee->is_operator ? 'selected' : '' }}>TIDAK (Bukan Driver)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: Izin SIMPER yang Dimiliki (7 Kolom - Luas & Rapi) -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <!-- Header Card SIMPER -->
                    <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 tracking-tight flex items-center gap-2">
                                <i class="fa-solid fa-id-card text-emerald-700"></i>
                                <span>Izin SIMPER yang Dimiliki</span>
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">Centang jenis SIMPER dan tentukan nomor serta masa berlakunya.</p>
                        </div>
                        <span class="text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg font-mono">
                            {{ $availableSimperTypes->count() }} Jenis Tersedia
                        </span>
                    </div>

                    <!-- Body Checklist SIMPER -->
                    <div id="viewSimperContainer" class="p-5 space-y-3.5">
                        <input type="hidden" name="simper_expired_at" id="viewPrimarySimperDate" value="{{ $employee->simper_expired_at?->format('Y-m-d') }}">

                        @forelse($availableSimperTypes as $st)
                            @php
                                $existingSimper = $employee->simpers->firstWhere('simper_type_id', $st->id);
                                $isChecked = !is_null($existingSimper);
                                $simperNum = $existingSimper ? $existingSimper->simper_number : ('SMP-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT));
                                $simperExp = $existingSimper && $existingSimper->expired_at ? $existingSimper->expired_at->format('Y-m-d') : ($employee->simper_expired_at?->format('Y-m-d') ?? '');
                            @endphp

                            <div class="p-4 rounded-xl border {{ $isChecked ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/50' }} hover:border-slate-300 transition">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center space-x-3 text-xs font-bold text-slate-900 cursor-pointer">
                                        <input type="checkbox" 
                                               name="simper_assignments[{{ $st->id }}][enabled]" 
                                               value="1" 
                                               id="chkSimper_{{ $st->id }}"
                                               {{ $isChecked ? 'checked' : '' }}
                                               onchange="toggleSimperRow({{ $st->id }})"
                                               class="w-4 h-4 rounded text-[#1b5e20] border-slate-300 focus:ring-emerald-500 cursor-pointer">
                                        <div>
                                            <span class="font-extrabold text-slate-900 text-xs">{{ $st->name }}</span>
                                            <span class="text-slate-400 font-mono text-[11px] ml-1">({{ $st->code }})</span>
                                        </div>
                                    </label>
                                    
                                    @if($st->coversScope('all'))
                                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-purple-50 text-purple-700 font-bold border border-purple-200">
                                            SEMUA ARMADA
                                        </span>
                                    @elseif($st->scopes === ['light_vehicle'])
                                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold border border-blue-200">
                                            LV
                                        </span>
                                    @else
                                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-amber-50 text-amber-700 font-bold border border-amber-200 max-w-[200px] truncate" title="{{ $st->category_scope_display }}">
                                            {{ strtoupper($st->category_scope_display) }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Form Fields (Nomor SIMPER & Masa Berlaku) -->
                                <div id="fieldsSimper_{{ $st->id }}" class="{{ $isChecked ? '' : 'hidden' }} grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3 pt-3 border-t border-slate-200/70">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1">
                                            Nomor Registrasi SIMPER
                                        </label>
                                        <input type="text" 
                                               name="simper_assignments[{{ $st->id }}][simper_number]" 
                                               value="{{ $simperNum }}"
                                               placeholder="Contoh: SMP-LV-001" 
                                               class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold focus:outline-none focus:border-emerald-600">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1">
                                            Masa Berlaku SIMPER
                                        </label>
                                        <input type="date" 
                                               name="simper_assignments[{{ $st->id }}][expired_at]" 
                                               value="{{ $simperExp }}"
                                               class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-emerald-600">
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic">Belum ada master jenis SIMPER yang aktif.</p>
                        @endforelse
                    </div>

                    <!-- Footer Aksi -->
                    <div class="p-5 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between">
                        <a href="{{ route('hse.employees.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                            Batal
                        </a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs transition shadow-sm inline-flex items-center space-x-2">
                            <i class="fa-solid fa-floppy-disk text-xs"></i>
                            <span>Simpan Perubahan SIMPER</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>

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
    @can('hse.employees.delete')
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
                Apakah Anda yakin ingin menghapus data karyawan / personil <strong class="text-slate-900">{{ $employee->name }}</strong> (<span class="font-bold font-mono text-slate-800 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">{{ $employee->nik }}</span>)?
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
                <form action="{{ route('hse.employees.destroy', $employee->id) }}" method="POST" class="flex-1">
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
    @endcan
</div>

<script>
    function openDeleteEmployeeModal() {
        const modal = document.getElementById('deleteEmployeeModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeDeleteEmployeeModal() {
        const modal = document.getElementById('deleteEmployeeModal');
        if (modal) modal.classList.add('hidden');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteEmployeeModal();
        }
    });
    function toggleSimperRow(id) {
        const chk = document.getElementById(`chkSimper_${id}`);
        const fields = document.getElementById(`fieldsSimper_${id}`);
        if (chk && fields) {
            if (chk.checked) {
                fields.classList.remove('hidden');
            } else {
                fields.classList.add('hidden');
            }
        }
    }

    function toggleViewCustomEmployeeType(val) {
        const wrapper = document.getElementById('viewCustomEmployeeTypeWrapper');
        const input = document.getElementById('viewCustomEmployeeTypeInput');
        if (val === '__CUSTOM__') {
            wrapper.classList.remove('hidden');
            input.focus();
        } else {
            wrapper.classList.add('hidden');
            input.value = '';
        }
    }

    function toggleViewSimperInput(val) {
        const container = document.getElementById('viewSimperContainer');
        if (val === '0') {
            container.classList.add('opacity-50', 'pointer-events-none');
        } else {
            container.classList.remove('opacity-50', 'pointer-events-none');
        }
    }
</script>
@endsection
