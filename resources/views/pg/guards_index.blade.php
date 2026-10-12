@extends('layouts.app')

@section('title', 'Master Data Satpam - Admin Private Guard')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight flex items-center">
                <i class="fa-solid fa-user-shield text-emerald-600 mr-2.5"></i> Master Data Satpam (Private Guard)
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Kelola data petugas keamanan yang bertugas melakukan verifikasi kendaraan di pos gerbang proyek.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Form Tambah Satpam Baru -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm h-fit">
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 flex items-center">
                <i class="fa-solid fa-user-plus text-emerald-600 mr-2"></i> Tambah Petugas Satpam
            </h3>
            <form action="{{ route('pg.guards.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">
                        Nama Lengkap Satpam <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" required placeholder="Contoh: Budi Santoso" class="w-full text-xs rounded-lg border-slate-300 border p-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                </div>
                <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-2.5 px-4 rounded-lg shadow-sm transition">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Data Satpam
                </button>
            </form>
        </div>

        <!-- Tabel Daftar Satpam -->
        <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Daftar Petugas Satpam Aktif & Nonaktif
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                    <thead class="bg-slate-100 font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4 text-center">No</th>
                            <th class="py-3 px-4">Nama Lengkap</th>
                            <th class="py-3 px-4 text-center">Status Piket</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($guards as $index => $g)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-4 text-center text-slate-400">{{ $index + 1 }}</td>
                                <td class="py-3 px-4 font-bold text-slate-800">{{ $g->name }}</td>
                                <td class="py-3 px-4 text-center">
                                    @if($g->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <form action="{{ route('pg.guards.toggle', $g->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-[11px] font-medium text-slate-600 hover:text-slate-900 underline">
                                            {{ $g->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400">Belum ada data petugas Satpam.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
