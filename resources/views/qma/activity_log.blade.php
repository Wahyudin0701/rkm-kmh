@extends('layouts.app')

@section('title', 'Log Aktivitas Kendaraan QMA - 10 Kolom')

@section('content')
<div class="space-y-6">
    <!-- Header Judul & Total Catatan (Persis Format Standar Manajemen Sistem) -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20] block mb-1">
                MANAJEMEN SISTEM
            </span>
            <div class="flex items-center space-x-3">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Log Aktivitas Kendaraan
                </h2>
                <span class="w-6 h-6 rounded-full bg-slate-200/90 text-slate-500 text-xs inline-flex items-center justify-center font-bold shadow-sm" title="Catatan komprehensif 10 kolom pergerakan armada kendaraan dengan validasi pos gerbang">
                    <i class="fa-solid fa-info"></i>
                </span>
            </div>
        </div>

        <div class="flex items-center space-x-3 w-full sm:w-auto">
            <span class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-sm">
                <i class="fa-solid fa-clipboard-check mr-2 text-emerald-600"></i> Total: {{ $activities->total() }} Catatan
            </span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('qma.activity.log') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Mulai Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full text-xs rounded-lg border-slate-300 border px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full text-xs rounded-lg border-slate-300 border px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Pos Gerbang</label>
                <select name="gate_id" class="w-full text-xs rounded-lg border-slate-300 border px-3 py-2">
                    <option value="">Semua Gerbang</option>
                    @foreach($gates as $g)
                        <option value="{{ $g->id }}" {{ request('gate_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Arah Akses</label>
                <select name="direction" class="w-full text-xs rounded-lg border-slate-300 border px-3 py-2">
                    <option value="">Semua Arah</option>
                    <option value="in" {{ request('direction') === 'in' ? 'selected' : '' }}>MASUK (In)</option>
                    <option value="out" {{ request('direction') === 'out' ? 'selected' : '' }}>KELUAR (Out)</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold py-2 px-3 rounded-lg transition">
                    <i class="fa-solid fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('qma.activity.log') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold py-2 px-3 rounded-lg border border-slate-300 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Tabel 10 Kolom Log Aktivitas QMA -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                <thead class="bg-slate-800 text-white font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-3 text-center">1. Tanggal</th>
                        <th class="py-3 px-3 text-center">2. Kode LV</th>
                        <th class="py-3 px-3">3. Unit</th>
                        <th class="py-3 px-3">4. Penanggung Jawab</th>
                        <th class="py-3 px-3">5. Driver</th>
                        <th class="py-3 px-3 text-center">6. Gate</th>
                        <th class="py-3 px-3 text-center">7. Akses</th>
                        <th class="py-3 px-3 text-right">8. KM Sebelumnya</th>
                        <th class="py-3 px-3 text-right">9. KM Berjalan</th>
                        <th class="py-3 px-3">10. Aktifitas</th>
                        <th class="py-3 px-3 text-center">Status PG</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700 bg-white">
                    @forelse($activities as $act)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-3 text-center font-medium text-slate-600 whitespace-nowrap">
                                {{ $act->recorded_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-slate-900 whitespace-nowrap">
                                <span class="bg-slate-100 px-2 py-0.5 rounded border border-slate-300 font-mono">
                                    {{ $act->vehicle->code }}
                                </span>
                            </td>
                            <td class="py-3 px-3 font-medium text-slate-800">
                                <div>{{ $act->vehicle->brand_type }}</div>
                                <span class="text-[10px] text-amber-700 font-mono">{{ $act->vehicle->license_plate ?? '-' }}</span>
                            </td>
                            <td class="py-3 px-3 text-slate-600">
                                {{ $act->vehicle->ownership ?? '-' }}
                            </td>
                            <td class="py-3 px-3 font-semibold text-slate-900">
                                {{ $act->driver->name }}
                                <div class="text-[10px] text-slate-400 font-normal">{{ $act->driver->division ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $act->gate->name }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                @if($act->direction === 'in')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <i class="fa-solid fa-arrow-right-to-bracket mr-1"></i> MASUK
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        <i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> KELUAR
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-medium text-slate-600 whitespace-nowrap">
                                {{ number_format($act->previous_km, 0, ',', '.') }} KM
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-emerald-600 whitespace-nowrap">
                                +{{ number_format($act->distance, 0, ',', '.') }} KM
                                <div class="text-[10px] text-slate-400 font-normal">Akhir: {{ number_format($act->current_km, 0, ',', '.') }}</div>
                            </td>
                            <td class="py-3 px-3 text-slate-600 max-w-xs truncate" title="{{ $act->purpose }}">
                                {{ $act->purpose }}
                            </td>
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                @if($act->validation_status === 'approved')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-300" title="Divalidasi oleh: {{ $act->securityGuard->name ?? 'Satpam' }}">
                                        <i class="fa-solid fa-circle-check mr-1"></i> Disetujui
                                    </span>
                                @elseif($act->validation_status === 'rejected')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-300" title="Alasan: {{ $act->rejection_note }}">
                                        <i class="fa-solid fa-circle-xmark mr-1"></i> Ditolak
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-300 animate-pulse">
                                        <i class="fa-solid fa-clock mr-1"></i> Menunggu PG
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-inbox text-3xl mb-2 block"></i>
                                Belum ada catatan aktivitas kendaraan yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 bg-slate-50 border-t border-slate-200">
            {{ $activities->links() }}
        </div>
    </div>
</div>
@endsection
