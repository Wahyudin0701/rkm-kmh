@extends('layouts.app')

@section('title', 'Data Satpam - Admin Private Guard | PT Kerinci Merangin Hidro')

@section('content')
<div class="space-y-6">

    <!-- Header Judul & Tombol Aksi (Selaras dengan format Data Karyawan) -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20] block mb-1">
                MANAJEMEN SISTEM
            </span>
            <div class="flex items-center space-x-3">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Data Satpam
                </h2>
                <!-- Info Icon Badge bulat abu-abu -->
                <span class="w-6 h-6 rounded-full bg-slate-200/90 text-slate-500 text-xs inline-flex items-center justify-center font-bold shadow-sm" title="Kelola data petugas keamanan, regu tugas (1, 2, 3), dan hak akses verifikasi pos gerbang">
                    <i class="fa-solid fa-info"></i>
                </span>
            </div>
        </div>

        <!-- Tombol Aksi Kanan (+ Tambah Satpam Hijau Tua) -->
        <div class="flex items-center space-x-3 w-full sm:w-auto">
            <button type="button" 
                    onclick="document.getElementById('addGuardModal').classList.remove('hidden')" 
                    class="bg-[#1b5e20] hover:bg-[#144919] text-white px-5 py-2.5 rounded-xl text-xs font-bold flex items-center space-x-2 transition shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah Petugas Satpam</span>
            </button>
        </div>
    </div>

    <!-- Kotak Pencarian Real-time (Selaras dengan Search Bar Data Karyawan) -->
    <div class="relative w-full max-w-sm sm:max-w-md">
        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-magnifying-glass text-xs"></i>
        </span>
        <input type="text" 
               id="guardSearchInput"
               placeholder="Cari ID Satpam atau nama petugas..." 
               class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 shadow-sm transition">
    </div>

    <!-- Tabel Penuh Master Data Satpam (Header Navy Solid #06213f) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <!-- Header Tabel Navy Gelap Solid #06213f -->
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-14">NO</th>
                        <th class="py-3.5 px-4">ID SATPAM</th>
                        <th class="py-3.5 px-4">NAMA LENGKAP</th>
                        <th class="py-3.5 px-4 text-center">REGU</th>
                        <th class="py-3.5 px-4">PENEMPATAN</th>
                        <th class="py-3.5 px-4 text-center">STATUS</th>
                        <th class="py-3.5 px-4 text-center w-28">AKSI</th>
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

                            <!-- Penempatan Pos Gerbang -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $g->gate->name ?? 'Belum Ditentukan' }}
                                </span>
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
                                    <!-- Tombol Edit Oranye -->
                                    <button type="button" 
                                            onclick="openEditGuardModal({{ json_encode($g) }})" 
                                            class="text-amber-500 hover:text-amber-700 p-1.5 transition" 
                                            title="Edit Data / Reset Password">
                                        <i class="fa-regular fa-pen-to-square text-sm"></i>
                                    </button>

                                    <!-- Tombol Toggle Nonaktifkan/Aktifkan Merah/Hijau -->
                                    <form action="{{ route('pg.guards.toggle', $g->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                title="{{ $g->is_active ? 'Nonaktifkan Petugas' : 'Aktifkan Petugas' }}" 
                                                class="{{ $g->is_active ? 'text-rose-500 hover:text-rose-700' : 'text-emerald-600 hover:text-emerald-800' }} p-1.5 transition">
                                            <i class="fa-solid fa-power-off text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-user-slash text-3xl mb-2 block text-slate-300"></i>
                                Belum ada data petugas Satpam terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: TAMBAH PETUGAS SATPAM BARU                                    -->
<!-- ========================================================================= -->
<div id="addGuardModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
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

            <!-- Grid: Regu & Penempatan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
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
                </div>

                <div>
                    <label for="gate_id" class="block text-sm font-bold text-slate-800 mb-2">
                        Penempatan Pos <span class="text-rose-500">*</span>
                    </label>
                    <select name="gate_id" 
                            id="gate_id" 
                            required 
                            class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        <option value="" disabled selected>Pilih Pos Gerbang</option>
                        @foreach($gates as $gate)
                            <option value="{{ $gate->id }}" {{ old('gate_id') == $gate->id ? 'selected' : '' }}>
                                {{ $gate->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
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
<div id="editGuardModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
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

            <!-- Grid: Regu & Penempatan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
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
                </div>

                <div>
                    <label for="editGateId" class="block text-sm font-bold text-slate-800 mb-2">
                        Penempatan Pos <span class="text-rose-500">*</span>
                    </label>
                    <select name="gate_id" id="editGateId" required 
                            class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        @foreach($gates as $gate)
                            <option value="{{ $gate->id }}">{{ $gate->name }}</option>
                        @endforeach
                    </select>
                </div>
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

<script>
    function openEditGuardModal(guard) {
        const modal = document.getElementById('editGuardModal');
        const form = document.getElementById('editGuardForm');
        
        form.action = `/pg/guards/${guard.id}`;
        document.getElementById('editBadgeId').value = guard.badge_id || ('SEC-' + String(guard.id).padStart(3, '0'));
        document.getElementById('editName').value = guard.name || '';
        document.getElementById('editTeam').value = guard.team || 1;
        document.getElementById('editGateId').value = guard.gate_id || '';
        document.getElementById('editPassword').value = '';

        modal.classList.remove('hidden');
    }

    function closeEditGuardModal() {
        document.getElementById('editGuardModal').classList.add('hidden');
    }

    // Filter Pencarian Instan Real-time
    document.getElementById('guardSearchInput')?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#guardTableBody tr.guard-row');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
</script>
@endsection
