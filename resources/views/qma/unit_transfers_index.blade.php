@extends('layouts.app')

@section('title', 'Transfer Unit Alat Berat - Admin QMA | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-4 sm:space-y-5">

    <!-- Header Judul & Tombol Aksi -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <div class="flex items-center space-x-1.5 mb-0.5">
                <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                    MANAJEMEN SISTEM
                </span>
                <button type="button" 
                        onclick="openGuideModal()" 
                        class="w-3.5 h-3.5 rounded-full bg-slate-200/90 hover:bg-slate-300 text-slate-500 hover:text-slate-800 text-[8px] inline-flex items-center justify-center font-bold transition shadow-2xs cursor-pointer focus:outline-none" 
                        title="Panduan Transfer Unit Alat Berat">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Transfer Unit Alat Berat
            </h2>
        </div>

        <!-- Tombol Aksi Kanan -->
        <div class="flex items-center gap-2 sm:gap-2.5 w-full sm:w-auto">
            <!-- Dropdown Menu Ekspor Format it.allrole.web.id -->
            <div class="relative inline-block w-full sm:w-auto flex-1 sm:flex-none" id="exportDropdownContainer">
                <button type="button" 
                        onclick="toggleExportMenu(event)" 
                        id="btnExportTrigger"
                        class="w-full sm:w-auto border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3 sm:px-3.5 py-2 rounded-xl text-xs font-bold flex items-center justify-center space-x-1.5 transition shadow-2xs cursor-pointer">
                    <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                    <span class="truncate">Ekspor Data</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-0.5"></i>
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

            @canany(['qma.vehicles.create', 'qma.vehicles.edit'])
            <a href="{{ route('qma.unit_transfers.create') }}" 
               class="flex-1 sm:flex-none flex items-center justify-center space-x-1.5 bg-[#1b5e20] hover:bg-[#144919] text-white px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer whitespace-nowrap">
                <i class="fa-solid fa-truck-moving text-xs"></i>
                <span>Transfer Unit Baru</span>
            </a>
            @endcanany
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if(session('success'))
    <div class="flex items-center space-x-3 p-3.5 bg-emerald-50/90 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-semibold shadow-2xs">
        <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 text-xs">
            <i class="fa-solid fa-check"></i>
        </div>
        <div class="flex-1">
            {{ session('success') }}
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="flex items-center space-x-3 p-3.5 bg-rose-50/90 border border-rose-200 rounded-xl text-rose-800 text-xs font-semibold shadow-2xs">
        <div class="w-6 h-6 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 text-xs">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="flex-1">
            {{ session('error') }}
        </div>
    </div>
    @endif

    <!-- KPI Ringkasan Pemindahan Unit (Ramping & Sleek) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
        <!-- 1. Total Transfer -->
        <div class="bg-white p-3 sm:p-3.5 rounded-xl border border-slate-200/80 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total BAST</p>
                <h3 class="text-lg sm:text-xl font-black text-slate-800 mt-0.5">{{ number_format($totalTransfers, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-slate-400 font-medium">Semua Dokumen</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-xs shrink-0">
                <i class="fa-solid fa-file-contract"></i>
            </div>
        </div>

        <!-- 2. Pemindahan Bulan Ini -->
        <div class="bg-white p-3 sm:p-3.5 rounded-xl border border-blue-200/80 shadow-2xs flex items-center justify-between bg-gradient-to-br from-white to-blue-50/20">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-blue-700">Bulan Ini</p>
                <h3 class="text-lg sm:text-xl font-black text-blue-800 mt-0.5">{{ number_format($thisMonthCount, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-blue-600 font-semibold">{{ \Carbon\Carbon::now()->translatedFormat('M Y') }}</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs shrink-0">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
        </div>

        <!-- 3. Di Dalam Site -->
        <div class="bg-white p-3 sm:p-3.5 rounded-xl border border-emerald-200/80 shadow-2xs flex items-center justify-between bg-gradient-to-br from-white to-emerald-50/20">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Tujuan In-Site</p>
                <h3 class="text-lg sm:text-xl font-black text-emerald-800 mt-0.5">{{ number_format($insideCount, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-emerald-600 font-semibold">Area Proyek</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs shrink-0">
                <i class="fa-solid fa-location-dot"></i>
            </div>
        </div>

        <!-- 4. Di Luar Site -->
        <div class="bg-white p-3 sm:p-3.5 rounded-xl border border-amber-200/80 shadow-2xs flex items-center justify-between bg-gradient-to-br from-white to-amber-50/20">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Tujuan Out-Site</p>
                <h3 class="text-lg sm:text-xl font-black text-amber-800 mt-0.5">{{ number_format($outsideCount, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-amber-600 font-semibold">Workshop/Luar</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs shrink-0">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </div>
        </div>
    </div>

    <!-- Kotak Pencarian & Filter Ramping (Inline Format Referensi HSE) -->
    <form method="GET" action="{{ route('qma.unit_transfers.index') }}" id="transferFilterForm" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 sm:gap-3">
        <!-- Search Text (Kiri) -->
        <div class="relative w-full sm:max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" 
                   name="search" 
                   id="transferSearchInput"
                   value="{{ request('search') }}" 
                   placeholder="Cari No BAST, unit, lokasi, pengangkut, pelapor..." 
                   class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition">
        </div>

        <!-- Filter Dropdown (Kanan) -->
        <div class="flex flex-wrap items-center gap-2 sm:gap-2.5 w-full sm:w-auto">
            <!-- Filter Alat Berat -->
            <select name="vehicle_id" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] shadow-sm transition max-w-[200px] truncate">
                <option value="all">Semua Alat Berat</option>
                @foreach($heavyUnits as $hu)
                    <option value="{{ $hu->id }}" {{ request('vehicle_id') == $hu->id ? 'selected' : '' }}>
                        {{ $hu->code }} - {{ $hu->brand_type }}
                    </option>
                @endforeach
            </select>

            <!-- Filter Status Area Tujuan -->
            <select name="to_site_status" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] shadow-sm transition">
                <option value="all" {{ request('to_site_status') === 'all' ? 'selected' : '' }}>Semua Area Tujuan</option>
                <option value="inside_site" {{ request('to_site_status') === 'inside_site' ? 'selected' : '' }}>Di Dalam Site</option>
                <option value="outside_site" {{ request('to_site_status') === 'outside_site' ? 'selected' : '' }}>Di Luar Site</option>
            </select>

            @if(request()->anyFilled(['search', 'vehicle_id', 'to_site_status', 'start_date', 'end_date']))
            <a href="{{ route('qma.unit_transfers.index') }}" class="px-2.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition whitespace-nowrap" title="Reset Filter">
                <i class="fa-solid fa-rotate-left mr-1"></i>Reset
            </a>
            @endif
        </div>
    </form>

    <!-- Tabel Riwayat Transfer Unit -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <!-- Header Tabel Navy Gelap Solid #06213f -->
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-3.5 text-center w-10">NO</th>
                        <th class="py-3 px-3.5 whitespace-nowrap">DOKUMEN & TANGGAL</th>
                        <th class="py-3 px-3.5 whitespace-nowrap">UNIT ALAT BERAT</th>
                        <th class="py-3 px-3.5 whitespace-nowrap">RUTE PEMINDAHAN</th>
                        <th class="py-3 px-3.5 whitespace-nowrap">PENGANGKUT & SUPIR</th>
                        <th class="py-3 px-3.5 whitespace-nowrap">PELAPOR</th>
                        <th class="py-3 px-3.5 text-center whitespace-nowrap w-24">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($transfers as $index => $t)
                    <tr class="hover:bg-slate-50/80 transition">
                        <!-- 1. No -->
                        <td class="py-2.5 px-3.5 text-center text-slate-400 font-medium">
                            {{ $transfers->firstItem() + $index }}
                        </td>

                        <!-- 2. Dokumen BAST & Tanggal Transfer -->
                        <td class="py-2.5 px-3.5 whitespace-nowrap">
                            <div class="flex items-center space-x-1.5">
                                @if($t->document_number)
                                    <span class="font-bold text-slate-900 font-mono text-xs">
                                        {{ $t->document_number }}
                                    </span>
                                    @if($t->document_file_path)
                                    <a href="{{ $t->document_file_url }}" target="_blank" class="text-emerald-700 hover:text-emerald-900 text-xs" title="Lihat Lampiran Berkas">
                                        <i class="fa-solid fa-paperclip"></i>
                                    </a>
                                    @endif
                                @else
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-slate-600 bg-slate-100 border border-slate-200">
                                        Internal Site
                                    </span>
                                @endif
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1.5 font-medium">
                                <span>{{ $t->transfer_date ? $t->transfer_date->translatedFormat('d M Y') : '-' }}</span>
                                <span class="text-slate-300">&bull;</span>
                                <span class="font-mono text-[10px] text-slate-400">{{ $t->transfer_no }}</span>
                            </div>
                        </td>

                        <!-- 3. Unit Alat Berat -->
                        <td class="py-2.5 px-3.5 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                @if($t->vehicle && $t->vehicle->photo_url)
                                <img src="{{ $t->vehicle->photo_url }}" alt="{{ $t->vehicle->code }}" class="w-8 h-8 rounded-lg object-cover border border-slate-200 shrink-0">
                                @else
                                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center shrink-0 border border-slate-200 text-xs">
                                    <i class="fa-solid fa-truck-monster"></i>
                                </div>
                                @endif
                                <div>
                                    <div class="font-black text-slate-900 tracking-tight text-xs">
                                        {{ $t->vehicle?->code ?? '-' }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 max-w-[150px] truncate" title="{{ $t->vehicle?->brand_type ?? '-' }}">
                                        {{ $t->vehicle?->brand_type ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- 4. Rute Pemindahan -->
                        <td class="py-2.5 px-3.5">
                            <div class="flex items-center space-x-1.5 text-xs whitespace-nowrap">
                                <span class="text-slate-600 font-medium">{{ $t->from_location }}</span>
                                <i class="fa-solid fa-arrow-right text-[10px] text-slate-400"></i>
                                <span class="font-bold text-[#1b5e20]">{{ $t->to_location }}</span>
                            </div>
                            <div class="mt-0.5">
                                @if($t->isToInsideSite())
                                <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>Di Dalam Site</span>
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    <span>Di Luar Site</span>
                                </span>
                                @endif
                            </div>
                        </td>

                        <!-- 5. Armada & Supir Pengangkut -->
                        <td class="py-2.5 px-3.5 whitespace-nowrap">
                            <div class="font-semibold text-slate-800 text-xs flex items-center gap-1.5">
                                <i class="fa-solid fa-truck-moving text-slate-400 text-[10px]"></i>
                                <span>{{ $t->transporter_unit_name ?: ($t->transporterVehicle?->code ?? 'Tanpa Pengangkut') }}</span>
                                @if($t->transporter_type === 'external' || (!$t->transporter_vehicle_id && $t->transporter_unit_name))
                                    <span class="text-[9px] px-1.5 py-0.2 rounded font-bold bg-amber-50 text-amber-700 border border-amber-200">Luar</span>
                                @endif
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1.5">
                                <span>{{ $t->transporter_driver_name ?: ($t->transporterDriver?->name ?? '-') }}</span>
                                @if($t->transporter_origin_location)
                                <span class="text-[10px] text-slate-400">| Asal: {{ $t->transporter_origin_location }}</span>
                                @endif
                            </div>
                        </td>

                        <!-- 6. Pelapor -->
                        <td class="py-2.5 px-3.5 whitespace-nowrap">
                            <div class="font-bold text-slate-800 text-xs">
                                {{ $t->reported_by }}
                            </div>
                            <div class="text-[10px] text-slate-400">
                                QMA
                            </div>
                        </td>

                        <!-- 7. Aksi -->
                        <td class="py-2.5 px-3.5 text-center whitespace-nowrap">
                            <div class="inline-flex items-center space-x-1">
                                <!-- Tombol Lihat Detail BAST -->
                                <a href="{{ route('qma.unit_transfers.show', $t->id) }}" 
                                   class="p-1.5 rounded-lg text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/80 transition" 
                                   title="Lihat Berita Acara & Detail">
                                    <i class="fa-solid fa-file-invoice text-xs"></i>
                                </a>

                                <!-- Tombol Hapus (Khusus jika ada izin) -->
                                @can('qma.vehicles.delete')
                                <button type="button" 
                                        onclick="openDeleteModal('{{ $t->id }}', '{{ e($t->document_number) }}', '{{ e($t->vehicle?->code ?? 'Unit') }}')" 
                                        class="p-1.5 rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200/80 transition cursor-pointer" 
                                        title="Hapus Data Transfer">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 sm:py-10 text-center text-slate-400">
                            <div class="w-11 h-11 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400 text-lg">
                                <i class="fa-solid fa-truck-ramp-box"></i>
                            </div>
                            <p class="text-xs font-bold text-slate-700">Belum Ada Data Pemindahan Unit</p>
                            <p class="text-[11px] text-slate-400 mt-0.5 max-w-sm mx-auto">
                                Belum ada transaksi pemindahan alat berat yang tercatat. Silakan klik tombol di bawah untuk membuat Berita Acara baru.
                            </p>
                            @canany(['qma.vehicles.create', 'qma.vehicles.edit'])
                            <div class="mt-3">
                                <a href="{{ route('qma.unit_transfers.create') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 bg-[#1b5e20] hover:bg-[#144919] text-white rounded-xl text-xs font-bold transition shadow-2xs">
                                    <i class="fa-solid fa-plus text-xs"></i>
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
