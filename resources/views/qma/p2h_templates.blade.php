@extends('layouts.app')

@section('title', 'Master Template P2H - Admin QMA')

@section('content')
<div class="w-full space-y-6">
    <!-- Header Judul (Tanpa Card Pembungkus Sesuai Referensi) -->
    <div>
        <div class="flex items-center space-x-1.5 mb-1">
            <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                MANAJEMEN SISTEM
            </span>
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            Master Template P2H Heavy Equipment
        </h2>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Daftar checksheet resmi pemeriksaan pra-harian untuk setiap kategori unit alat berat di proyek PLTA Merangin.
        </p>
    </div>

    <!-- Kotak Pencarian & Reset Filter -->
    <form method="GET" action="{{ route('qma.p2h.templates') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <div class="relative w-full max-w-sm sm:max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" 
                   id="templateSearchInput"
                   name="search" 
                   value="{{ request('search') }}" 
                   placeholder="Cari nama template alat (Excavator, Crane, Vibro) atau kode..." 
                   class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 shadow-sm transition">
        </div>

        <div id="templateResetContainer">
            @if(request('search'))
                <a href="{{ route('qma.p2h.templates') }}" 
                   id="templateResetBtn" 
                   class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center space-x-1 transition px-3 py-2 rounded-xl hover:bg-rose-50 border border-transparent hover:border-rose-100">
                    <i class="fa-solid fa-rotate-left text-[11px]"></i>
                    <span>Reset Filter</span>
                </a>
            @endif
        </div>
    </form>

    <!-- Tabel Master Template P2H Heavy Equipment (Tampilan Tabel Bukan Card) -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden" id="templatesTableContainer">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4 text-center w-32">KODE TEMPLATE</th>
                        <th class="py-3.5 px-4">NAMA TEMPLATE ALAT</th>
                        <th class="py-3.5 px-4 text-center">STATUS</th>
                        <th class="py-3.5 px-4 text-center">BUTIR CEK</th>
                        <th class="py-3.5 px-4 text-center">UNIT TERKAIT</th>
                        <th class="py-3.5 px-4 text-center">RIWAYAT P2H</th>
                        <th class="py-3.5 px-4 text-center w-52">AKSI</th>
                    </tr>
                </thead>
                <tbody id="templatesTableBody" class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($templates as $index => $tmpl)
                        <tr class="template-row hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                {{ $index + 1 }}
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold text-slate-900 font-mono text-xs whitespace-nowrap">
                                <span class="bg-blue-50 text-blue-800 px-2.5 py-1 rounded-lg border border-blue-200">
                                    {{ $tmpl->code }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 text-sm leading-snug">{{ $tmpl->name }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5 line-clamp-1 max-w-lg">{{ $tmpl->description }}</div>
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($tmpl->items_count > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-circle-check mr-1 text-[9px]"></i> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fa-solid fa-triangle-exclamation mr-1 text-[9px]"></i> Template Baru (Kosong)
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold {{ $tmpl->items_count > 0 ? 'bg-slate-100 text-slate-800 border border-slate-200' : 'bg-slate-50 text-slate-400' }}">
                                    {{ $tmpl->items_count }} Butir
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="font-bold text-slate-800">{{ $tmpl->vehicles_count }}</span>
                                <span class="text-[10px] text-slate-400">Unit</span>
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="font-bold text-slate-800">{{ $tmpl->records_count }}</span>
                                <span class="text-[10px] text-slate-400">Laporan</span>
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <a href="{{ route('qma.p2h.templates.show', $tmpl->id) }}" 
                                   class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition shadow-2xs {{ $tmpl->items_count > 0 ? 'bg-[#1b5e20] hover:bg-[#144919] text-white' : 'bg-emerald-50 hover:bg-emerald-100 text-[#1b5e20] border border-emerald-200' }}"
                                   title="Buka & Kelola Pertanyaan Template {{ $tmpl->name }}">
                                    <i class="fa-solid fa-list-check text-[11px]"></i>
                                    <span>{{ $tmpl->items_count > 0 ? 'Lihat & Kelola Pertanyaan' : 'Kelola & Isi Pertanyaan' }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr id="serverTemplateEmptyRow">
                            <td colspan="8" class="py-12 px-4 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl shadow-xs mx-auto mb-3">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </div>
                                @if(request('search'))
                                    <h4 class="text-sm font-bold text-slate-800">
                                        Data "<span class="text-rose-600 font-bold">{{ request('search') }}</span>" tidak ditemukan di tabel
                                    </h4>
                                    <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">
                                        Pastikan kata kunci yang dicari sudah benar.
                                    </p>
                                    <div class="mt-4">
                                        <a href="{{ route('qma.p2h.templates') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                            <span>Hapus Pencarian</span>
                                        </a>
                                    </div>
                                @else
                                    <div class="text-sm font-bold text-slate-700">Belum Ada Template</div>
                                    <p class="text-xs text-slate-400 mt-1">Belum ada template checksheet P2H yang terdaftar.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('templateSearchInput');
    const tableBody = document.getElementById('templatesTableBody');
    const resetContainer = document.getElementById('templateResetContainer');
    const serverEmptyRow = document.getElementById('serverTemplateEmptyRow');

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function (e) {
            const rawValue = e.target.value;
            const query = rawValue.toLowerCase().trim();
            const rows = tableBody.querySelectorAll('tr.template-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const match = text.includes(query);
                row.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            // Sembunyikan baris SSR empty jika sedang mengetik
            if (serverEmptyRow) {
                serverEmptyRow.style.display = query.length > 0 ? 'none' : '';
            }

            // Hapus baris empty client jika ada sebelumnya
            const existingClientEmpty = document.getElementById('clientTemplateEmptyRow');
            if (existingClientEmpty) {
                existingClientEmpty.remove();
            }

            // Tampilkan pesan jika tidak ada data yang cocok
            if (visibleCount === 0 && query.length > 0) {
                const emptyTr = document.createElement('tr');
                emptyTr.id = 'clientTemplateEmptyRow';
                emptyTr.innerHTML = `
                    <td colspan="8" class="py-12 px-4 text-center text-slate-400">
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl shadow-xs mx-auto mb-3">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-800">
                            Data "<span class="text-rose-600 font-bold">${escapeHtml(rawValue)}</span>" tidak ditemukan di tabel
                        </h4>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">
                            Pastikan kata kunci yang dicari sudah benar.
                        </p>
                        <div class="mt-4">
                            <button type="button" 
                                    onclick="const inp = document.getElementById('templateSearchInput'); if(inp){ inp.value=''; inp.dispatchEvent(new Event('input')); inp.focus(); }" 
                                    class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                <span>Hapus Pencarian</span>
                            </button>
                        </div>
                    </td>
                `;
                tableBody.appendChild(emptyTr);
            }

            // Tampilkan atau sembunyikan tombol Reset Filter secara real-time
            if (resetContainer) {
                let resetBtn = document.getElementById('templateResetBtn');
                if (query.length > 0) {
                    if (!resetBtn) {
                        resetBtn = document.createElement('a');
                        resetBtn.href = "{{ route('qma.p2h.templates') }}";
                        resetBtn.id = 'templateResetBtn';
                        resetBtn.className = 'text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center space-x-1 transition px-3 py-2 rounded-xl hover:bg-rose-50 border border-transparent hover:border-rose-100';

                        const resetIcon = document.createElement('i');
                        resetIcon.className = 'fa-solid fa-rotate-left text-[11px]';
                        const resetText = document.createElement('span');
                        resetText.textContent = 'Reset Filter';

                        resetBtn.appendChild(resetIcon);
                        resetBtn.appendChild(resetText);
                        resetContainer.appendChild(resetBtn);
                    }
                } else {
                    if (resetBtn && !{{ request()->filled('search') ? 'true' : 'false' }}) {
                        resetBtn.remove();
                    }
                }
            }
        });
    }
});
</script>
@endsection
