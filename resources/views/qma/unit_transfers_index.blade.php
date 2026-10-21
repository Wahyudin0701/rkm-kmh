@extends('layouts.app')

@section('title', 'Transfer Unit Alat Berat - Admin QMA | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Judul & Tombol Aksi -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-1.5 mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                    MANAJEMEN UNIT
                </span>
                <button type="button" 
                        onclick="openGuideModal()" 
                        class="w-4 h-4 rounded-full bg-slate-200/90 hover:bg-slate-300 text-slate-500 hover:text-slate-800 text-[9px] inline-flex items-center justify-center font-bold transition shadow-2xs cursor-pointer focus:outline-none" 
                        title="Panduan Transfer Unit Alat Berat">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Transfer Unit Alat Berat
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Pencatatan resmi pemindahan lokasi alat berat, armada truk pengangkut, dan penerbitan Berita Acara Serah Terima (BAST).
            </p>
        </div>

        <!-- Tombol Aksi Kanan -->
        <div class="flex items-center space-x-2.5 w-full sm:w-auto">
            <!-- Dropdown Menu Ekspor -->
            <div class="relative inline-block" id="exportDropdownContainer">
                <button type="button" 
                        onclick="toggleExportMenu(event)" 
                        id="btnExportTrigger"
                        class="flex items-center justify-center space-x-2 border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3.5 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                    <i class="fa-solid fa-file-export text-emerald-700 text-sm"></i>
                    <span class="hidden sm:inline">Ekspor Data</span>
                    <span class="sm:hidden">Ekspor</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
                </button>
                <div id="exportMenu" style="display: none;" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 z-50 overflow-hidden">
                    <a href="{{ route('qma.unit_transfers.export_pdf', request()->query()) }}" target="_blank" class="flex items-center px-4 py-3 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#1b5e20] font-bold transition">
                        <i class="fa-regular fa-file-pdf text-rose-600 text-sm mr-2.5"></i>
                        <span>Format PDF (Cetak)</span>
                    </a>
                    <div class="h-px bg-slate-100 mx-2"></div>
                    <a href="{{ route('qma.unit_transfers.export_excel', request()->query()) }}" class="flex items-center px-4 py-3 text-xs text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i class="fa-regular fa-file-excel text-emerald-600 text-sm mr-2.5"></i>
                        <span>Format Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            <!-- Tombol Buat Transfer Unit Baru -->
            @canany(['qma.vehicles.create', 'qma.vehicles.edit'])
            <a href="{{ route('qma.unit_transfers.create') }}" 
               class="inline-flex items-center justify-center space-x-2 bg-[#1b5e20] hover:bg-[#144919] text-white px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer whitespace-nowrap">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Buat Transfer Unit</span>
            </a>
            @endcanany
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if(session('success'))
    <div class="flex items-center space-x-3 p-4 bg-emerald-50/90 border border-emerald-200 rounded-2xl text-emerald-800 text-xs sm:text-sm font-semibold shadow-2xs">
        <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-check"></i>
        </div>
        <div class="flex-1">
            {{ session('success') }}
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="flex items-center space-x-3 p-4 bg-rose-50/90 border border-rose-200 rounded-2xl text-rose-800 text-xs sm:text-sm font-semibold shadow-2xs">
        <div class="w-7 h-7 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="flex-1">
            {{ session('error') }}
        </div>
    </div>
    @endif

    <!-- KPI Ringkasan Pemindahan Unit -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- 1. Total Transfer -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Pemindahan</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($totalTransfers, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-slate-400 font-medium">Semua Dokumen BAST</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 text-lg">
                <i class="fa-solid fa-truck-ramp-box"></i>
            </div>
        </div>

        <!-- 2. Pemindahan Bulan Ini -->
        <div class="bg-white p-4 rounded-2xl border border-blue-200/80 shadow-xs flex items-center justify-between bg-gradient-to-br from-white to-blue-50/30">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-blue-700">Bulan Ini</p>
                <h3 class="text-2xl font-black text-blue-800 mt-1">{{ number_format($thisMonthCount, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-blue-600 font-semibold">{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-blue-700 text-lg">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
        </div>

        <!-- 3. Di Dalam Site -->
        <div class="bg-white p-4 rounded-2xl border border-emerald-200/80 shadow-xs flex items-center justify-between bg-gradient-to-br from-white to-emerald-50/30">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Tujuan In-Site</p>
                <h3 class="text-2xl font-black text-emerald-800 mt-1">{{ number_format($insideCount, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-emerald-600 font-semibold">Area Proyek KMH</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-700 text-lg">
                <i class="fa-solid fa-map-pin"></i>
            </div>
        </div>

        <!-- 4. Di Luar Site -->
        <div class="bg-white p-4 rounded-2xl border border-amber-200/80 shadow-xs flex items-center justify-between bg-gradient-to-br from-white to-amber-50/30">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Tujuan Out-Site</p>
                <h3 class="text-2xl font-black text-amber-800 mt-1">{{ number_format($outsideCount, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-amber-600 font-semibold">Workshop/Luar Proyek</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-700 text-lg">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('qma.unit_transfers.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-center">
            <!-- Search Text -->
            <div class="relative md:col-span-2">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Cari No BAST, Unit, Lokasi, Driver, Pelapor..." 
                       class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
            </div>

            <!-- Filter Alat Berat -->
            <div>
                <select name="vehicle_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-700">
                    <option value="all">Semua Alat Berat</option>
                    @foreach($heavyUnits as $hu)
                        <option value="{{ $hu->id }}" {{ request('vehicle_id') == $hu->id ? 'selected' : '' }}>
                            {{ $hu->code }} - {{ $hu->brand_type }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Status Area Tujuan -->
            <div>
                <select name="to_site_status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-700">
                    <option value="all" {{ request('to_site_status') === 'all' ? 'selected' : '' }}>Semua Area Tujuan</option>
                    <option value="inside_site" {{ request('to_site_status') === 'inside_site' ? 'selected' : '' }}>Di Dalam Site</option>
                    <option value="outside_site" {{ request('to_site_status') === 'outside_site' ? 'selected' : '' }}>Di Luar Site</option>
                </select>
            </div>

            <!-- Tombol Filter & Reset -->
            <div class="flex items-center space-x-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition shadow-sm cursor-pointer text-center">
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'vehicle_id', 'to_site_status', 'start_date', 'end_date']))
                <a href="{{ route('qma.unit_transfers.index') }}" class="px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition whitespace-nowrap" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Riwayat Transfer Unit -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-3.5 text-center w-12">No</th>
                        <th class="py-3 px-3.5">Tanggal & No Transfer</th>
                        <th class="py-3 px-3.5">Berita Acara (BAST)</th>
                        <th class="py-3 px-3.5">Unit Alat Berat</th>
                        <th class="py-3 px-3.5">Perpindahan Lokasi</th>
                        <th class="py-3 px-3.5">Armada & Supir Pengangkut</th>
                        <th class="py-3 px-3.5">Pelapor</th>
                        <th class="py-3 px-3.5 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($transfers as $index => $t)
                    <tr class="hover:bg-slate-50/80 transition">
                        <!-- 1. No -->
                        <td class="py-3.5 px-3.5 text-center text-slate-400 font-medium">
                            {{ $transfers->firstItem() + $index }}
                        </td>

                        <!-- 2. Tanggal & No Transfer -->
                        <td class="py-3.5 px-3.5 whitespace-nowrap">
                            <div class="font-bold text-slate-900">
                                {{ $t->transfer_date ? $t->transfer_date->translatedFormat('d M Y') : '-' }}
                            </div>
                            <span class="inline-block mt-0.5 font-mono text-[10px] text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">
                                {{ $t->transfer_no }}
                            </span>
                        </td>

                        <!-- 3. Berita Acara -->
                        <td class="py-3.5 px-3.5 min-w-[200px]">
                            <div class="font-bold text-slate-900 leading-tight">
                                {{ $t->document_number }}
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-1" title="{{ $t->document_title }}">
                                {{ $t->document_title }}
                            </div>
                            @if($t->document_file_path)
                            <a href="{{ $t->document_file_url }}" target="_blank" class="inline-flex items-center space-x-1 text-[10px] font-bold text-emerald-700 hover:text-emerald-900 mt-1">
                                <i class="fa-solid fa-paperclip"></i>
                                <span>Lihat Lampiran</span>
                            </a>
                            @endif
                        </td>

                        <!-- 4. Unit Alat Berat -->
                        <td class="py-3.5 px-3.5 whitespace-nowrap">
                            <div class="flex items-center space-x-2.5">
                                @if($t->vehicle && $t->vehicle->photo_url)
                                <img src="{{ $t->vehicle->photo_url }}" alt="{{ $t->vehicle->code }}" class="w-9 h-9 rounded-xl object-cover border border-slate-200 shrink-0">
                                @else
                                <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center shrink-0 border border-slate-200">
                                    <i class="fa-solid fa-truck-monster text-sm"></i>
                                </div>
                                @endif
                                <div>
                                    <div class="font-black text-slate-900 tracking-tight">
                                        {{ $t->vehicle?->code ?? '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        {{ $t->vehicle?->brand_type ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- 5. Lokasi Asal -> Lokasi Tujuan -->
                        <td class="py-3.5 px-3.5">
                            <div class="flex items-center space-x-1.5 text-xs">
                                <span class="text-slate-600 font-medium">{{ $t->from_location }}</span>
                                <i class="fa-solid fa-arrow-right text-[10px] text-emerald-600"></i>
                                <span class="font-extrabold text-[#1b5e20]">{{ $t->to_location }}</span>
                            </div>
                            <div class="mt-1">
                                @if($t->isToInsideSite())
                                <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>Di Dalam Site</span>
                                </span>
                                @else
                                <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    <span>Di Luar Site</span>
                                </span>
                                @endif
                            </div>
                        </td>

                        <!-- 6. Armada & Supir Pengangkut -->
                        <td class="py-3.5 px-3.5 whitespace-nowrap">
                            <div class="font-semibold text-slate-800">
                                <i class="fa-solid fa-truck-moving text-slate-400 mr-1 text-[11px]"></i>
                                {{ $t->transporter_unit_name ?: ($t->transporterVehicle?->code ?? 'Tanpa Pengangkut') }}
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                <i class="fa-solid fa-id-badge text-slate-400 mr-1 text-[11px]"></i>
                                {{ $t->transporter_driver_name ?: ($t->transporterDriver?->name ?? '-') }}
                            </div>
                        </td>

                        <!-- 7. Pelapor -->
                        <td class="py-3.5 px-3.5 whitespace-nowrap">
                            <div class="font-bold text-slate-800">
                                {{ $t->reported_by }}
                            </div>
                            <div class="text-[10px] text-slate-400">
                                Petugas QMA
                            </div>
                        </td>

                        <!-- 8. Aksi -->
                        <td class="py-3.5 px-3.5 text-center whitespace-nowrap">
                            <div class="inline-flex items-center space-x-1.5">
                                <!-- Tombol Lihat Detail BAST -->
                                <a href="{{ route('qma.unit_transfers.show', $t->id) }}" 
                                   class="p-2 rounded-xl text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/80 transition shadow-2xs" 
                                   title="Lihat Berita Acara & Detail">
                                    <i class="fa-solid fa-file-invoice text-xs"></i>
                                </a>

                                <!-- Tombol Hapus (Khusus jika ada izin) -->
                                @can('qma.vehicles.delete')
                                <button type="button" 
                                        onclick="openDeleteModal('{{ $t->id }}', '{{ e($t->document_number) }}', '{{ e($t->vehicle?->code ?? 'Unit') }}')" 
                                        class="p-2 rounded-xl text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200/80 transition shadow-2xs cursor-pointer" 
                                        title="Hapus Data Transfer">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400 text-2xl">
                                <i class="fa-solid fa-truck-ramp-box"></i>
                            </div>
                            <p class="text-sm font-bold text-slate-700">Belum Ada Data Pemindahan Unit</p>
                            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                Belum ada transaksi pemindahan alat berat yang tercatat. Silakan klik tombol "Buat Transfer Unit" untuk membuat Berita Acara baru.
                            </p>
                            @canany(['qma.vehicles.create', 'qma.vehicles.edit'])
                            <div class="mt-4">
                                <a href="{{ route('qma.unit_transfers.create') }}" class="inline-flex items-center space-x-1.5 px-4 py-2 bg-[#1b5e20] hover:bg-[#144919] text-white rounded-xl text-xs font-bold transition shadow-sm">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Buat Transfer Unit Baru</span>
                                </a>
                            </div>
                            @endcanany
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($transfers->hasPages())
        <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
            {{ $transfers->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Konfirmasi Hapus Transfer Unit -->
<div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto" style="display: none;" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeDeleteModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative z-10 w-full max-w-sm bg-white rounded-2xl text-center overflow-hidden shadow-2xl transform transition-all border border-slate-200 p-6">
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-triangle-exclamation text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-2">Hapus Data Transfer?</h3>
            <p class="text-xs text-slate-500 mb-6">
                Apakah Anda yakin ingin menghapus data transfer untuk unit <span id="deleteUnitCode" class="font-bold text-slate-800 font-mono"></span> dengan Berita Acara <span id="deleteDocNo" class="font-bold text-slate-800 font-mono"></span>?
            </p>
            <form id="deleteForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="flex items-center justify-center space-x-3">
                    <button type="button" onclick="closeDeleteModal()" class="px-5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition shadow-sm cursor-pointer">
                        Ya, Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Panduan Halaman -->
<div id="guideModal" class="hidden fixed inset-0 z-50 overflow-y-auto" style="display: none;" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeGuideModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative z-10 w-full max-w-lg bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all border border-slate-200 p-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                <h3 class="text-base font-extrabold text-slate-900 flex items-center space-x-2">
                    <i class="fa-solid fa-circle-question text-emerald-700"></i>
                    <span>Panduan Fitur Transfer Unit Alat Berat</span>
                </h3>
                <button type="button" onclick="closeGuideModal()" class="text-slate-400 hover:text-slate-600 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="mt-4 space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>
                    <strong>Transfer Unit Alat Berat</strong> adalah modul khusus untuk mencatat pergerakan dan relokasi unit-unit berat (Excavator, Crane, Dozer, Vibro, Truk Mixer, dsb.) di area proyek PT Kerinci Merangin Hidro.
                </p>
                <div class="bg-emerald-50 border border-emerald-200 p-3 rounded-xl text-emerald-900">
                    <p class="font-bold mb-1">Keunggulan Integrasi Otomatis:</p>
                    <ul class="list-disc pl-4 space-y-1 text-[11px]">
                        <li>Setiap pemindahan akan otomatis mengupdate <strong>Lokasi Terkini</strong> dan <strong>Status Site</strong> alat berat di menu Posisi Unit.</li>
                        <li>Sistem otomatis mencatat jejak audit pergerakan di <strong>Riwayat Posisi Unit</strong> dengan referensi Berita Acara (BAST).</li>
                        <li>Dapat mencantumkan truk/mobil pengangkut armada kita beserta supir yang bertugas membawanya.</li>
                    </ul>
                </div>
                <p>
                    Setelah disimpan, Berita Acara dapat langsung dicetak (print) atau diunduh dengan tata letak dokumen resmi lengkap dengan kolom tanda tangan serah terima.
                </p>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="button" onclick="closeGuideModal()" class="px-4 py-2 bg-slate-900 text-white font-bold text-xs rounded-xl hover:bg-slate-800 transition">
                    Mengerti
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleExportMenu(e) {
        e.stopPropagation();
        const menu = document.getElementById('exportMenu');
        menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
    }

    document.addEventListener('click', function(e) {
        const container = document.getElementById('exportDropdownContainer');
        const menu = document.getElementById('exportMenu');
        if (container && !container.contains(e.target)) {
            if (menu) menu.style.display = 'none';
        }
    });

    function openDeleteModal(id, docNo, code) {
        const form = document.getElementById('deleteForm');
        form.action = "{{ url('qma/unit-transfers') }}/" + id;
        document.getElementById('deleteUnitCode').textContent = code;
        document.getElementById('deleteDocNo').textContent = docNo;
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    function openGuideModal() {
        const modal = document.getElementById('guideModal');
        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }

    function closeGuideModal() {
        const modal = document.getElementById('guideModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
</script>
@endsection
