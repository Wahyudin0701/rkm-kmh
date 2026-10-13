@extends('layouts.app')

@section('title', 'Master Data Satpam - Admin Private Guard')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20] block mb-1">
                MANAJEMEN SISTEM
            </span>
            <div class="flex items-center space-x-3">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Master Data Satpam (PG)
                </h2>
                <span class="w-6 h-6 rounded-full bg-slate-200/90 text-slate-500 text-xs inline-flex items-center justify-center font-bold shadow-sm" title="Kelola data petugas keamanan, regu tugas, dan akun login penempatan pos gerbang">
                    <i class="fa-solid fa-user-shield"></i>
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Data petugas keamanan, pembagian regu kerja (1, 2, 3), dan hak akses verifikasi pos gerbang.
            </p>
        </div>

        <!-- Metric Counter Badges -->
        <div class="flex items-center space-x-2 w-full sm:w-auto">
            <span class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-sm">
                <i class="fa-solid fa-users-gear mr-1.5 text-emerald-600"></i> Total: {{ $guards->count() }} Petugas
            </span>
        </div>
    </div>

    <!-- Layout Grid: Form Tambah & Tabel Satpam -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Form Tambah Petugas Satpam (Kiri - 4 Kolom) -->
        <div class="lg:col-span-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center space-x-2 pb-3 mb-4 border-b border-slate-100">
                <i class="fa-solid fa-user-plus text-[#1b5e20]"></i>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Tambah Petugas Satpam
                </h3>
            </div>

            <form action="{{ route('pg.guards.store') }}" method="POST" class="space-y-4">
                @csrf
                
                <!-- ID Satpam -->
                <div>
                    <label for="badge_id" class="block text-xs font-bold text-slate-700 mb-1">
                        ID Satpam <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           name="badge_id" 
                           id="badge_id" 
                           value="{{ old('badge_id') }}" 
                           required 
                           placeholder="Contoh: SEC-006 atau PG-06" 
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 uppercase font-mono focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                    <p class="text-[10px] text-slate-400 mt-1">ID ini akan digunakan satpam sebagai username login.</p>
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name') }}" 
                           required 
                           placeholder="Contoh: Budi Santoso" 
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Regu Satpam (1, 2, 3) -->
                <div>
                    <label for="team" class="block text-xs font-bold text-slate-700 mb-1">
                        Regu Tugas <span class="text-rose-500">*</span>
                    </label>
                    <select name="team" 
                            id="team" 
                            required 
                            class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="" disabled selected>-- Pilih Regu (1, 2, atau 3) --</option>
                        <option value="1" {{ old('team') == 1 ? 'selected' : '' }}>Regu 1</option>
                        <option value="2" {{ old('team') == 2 ? 'selected' : '' }}>Regu 2</option>
                        <option value="3" {{ old('team') == 3 ? 'selected' : '' }}>Regu 3</option>
                    </select>
                </div>

                <!-- Penempatan Pos Gerbang -->
                <div>
                    <label for="gate_id" class="block text-xs font-bold text-slate-700 mb-1">
                        Penempatan Pos Gerbang <span class="text-rose-500">*</span>
                    </label>
                    <select name="gate_id" 
                            id="gate_id" 
                            required 
                            class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="" disabled selected>-- Pilih Pos Gerbang Penempatan --</option>
                        @foreach($gates as $gate)
                            <option value="{{ $gate->id }}" {{ old('gate_id') == $gate->id ? 'selected' : '' }}>
                                {{ $gate->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Password Akun Login -->
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 mb-1">
                        Password Akun Login <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           required 
                           placeholder="Minimal 4 karakter" 
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-[#06213f] hover:bg-[#0b315b] text-white font-bold text-xs py-2.5 px-4 rounded-xl shadow-sm transition flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-floppy-disk text-[11px]"></i>
                        <span>Simpan Data Satpam</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabel Daftar Petugas Satpam (Kanan - 8 Kolom) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-id-card-clip text-slate-500"></i>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                        Daftar Petugas Satpam Terdaftar
                    </h3>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs text-left border-collapse">
                    <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4 text-center whitespace-nowrap">ID Satpam</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">Nama Lengkap</th>
                            <th class="py-3.5 px-4 text-center whitespace-nowrap">Regu</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">Penempatan</th>
                            <th class="py-3.5 px-4 text-center whitespace-nowrap">Status</th>
                            <th class="py-3.5 px-4 text-center whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 bg-white">
                        @forelse($guards as $g)
                            <tr class="hover:bg-slate-50/70 transition">
                                <!-- ID Satpam -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap font-mono">
                                    <span class="inline-block bg-slate-100 text-slate-800 font-bold px-2 py-0.5 rounded border border-slate-200">
                                        {{ $g->badge_id ?? 'SEC-' . str_pad($g->id, 3, '0', STR_PAD_LEFT) }}
                                    </span>
                                </td>

                                <!-- Nama Lengkap -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900">{{ $g->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">User: {{ $g->badge_id ?? '-' }}</div>
                                </td>

                                <!-- Regu (1, 2, 3) -->
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
                                        <i class="fa-solid fa-torii-gate mr-1.5 text-slate-400 text-[10px]"></i>
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

                                <!-- Aksi (Edit & Toggle) -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center space-x-2">
                                        <!-- Tombol Edit -->
                                        <button type="button" 
                                                onclick="openEditGuardModal({{ json_encode($g) }})" 
                                                class="px-2.5 py-1 text-[11px] font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-lg transition" 
                                                title="Edit Data / Reset Password">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <!-- Tombol Toggle Status -->
                                        <form action="{{ route('pg.guards.toggle', $g->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="px-2.5 py-1 text-[11px] font-semibold {{ $g->is_active ? 'text-rose-600 hover:bg-rose-50 border border-rose-200' : 'text-emerald-700 hover:bg-emerald-50 border border-emerald-200' }} rounded-lg transition">
                                                {{ $g->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-slate-400">
                                    <i class="fa-solid fa-user-slash text-3xl mb-2 block"></i>
                                    Belum ada data petugas Satpam terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Data Petugas Satpam -->
<div id="editGuardModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in duration-150">
        <div class="bg-[#06213f] text-white p-4 flex justify-between items-center">
            <h3 class="text-sm font-bold flex items-center">
                <i class="fa-solid fa-user-pen mr-2 text-emerald-400"></i> Edit Data Petugas Satpam
            </h3>
            <button onclick="closeEditGuardModal()" class="text-slate-400 hover:text-white transition">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <form id="editGuardForm" method="POST" action="" class="p-5 space-y-4">
            @csrf
            
            <!-- ID Satpam (Readonly) -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ID Satpam (Username Login)</label>
                <input type="text" id="editBadgeId" disabled class="w-full text-xs rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-slate-500 font-mono font-bold">
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label for="editName" class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="editName" required class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
            </div>

            <!-- Regu (1, 2, 3) -->
            <div>
                <label for="editTeam" class="block text-xs font-bold text-slate-700 mb-1">Regu Tugas <span class="text-rose-500">*</span></label>
                <select name="team" id="editTeam" required class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                    <option value="1">Regu 1</option>
                    <option value="2">Regu 2</option>
                    <option value="3">Regu 3</option>
                </select>
            </div>

            <!-- Penempatan Pos Gerbang -->
            <div>
                <label for="editGateId" class="block text-xs font-bold text-slate-700 mb-1">Penempatan Pos Gerbang <span class="text-rose-500">*</span></label>
                <select name="gate_id" id="editGateId" required class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                    @foreach($gates as $gate)
                        <option value="{{ $gate->id }}">{{ $gate->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Reset Password (Opsional) -->
            <div>
                <label for="editPassword" class="block text-xs font-bold text-slate-700 mb-1">
                    Ganti Password Baru <span class="text-slate-400 font-normal">(Kosongkan jika tidak ingin diubah)</span>
                </label>
                <input type="password" name="password" id="editPassword" placeholder="Minimal 4 karakter" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
            </div>

            <!-- Tombol Aksi Modal -->
            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeEditGuardModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-[#06213f] hover:bg-[#0b315b] rounded-xl shadow-sm transition">
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
</script>
@endsection
