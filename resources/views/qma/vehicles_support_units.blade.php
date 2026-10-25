@extends('layouts.app')

@section('title', 'Unit Fisik ' . $category->code . ' - Admin QMA | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Judul & Tombol Aksi (Pola justify-between antara judul kiri & grup tombol kanan) -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-2 mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                    MANAJEMEN UNIT FISIK
                </span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                {{ $category->unit_category ?? 'Alat Berat' }}
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                {{ $category->brand_type }}{{ $category->model_number ? ' • Model: ' . $category->model_number : '' }}
            </p>
        </div>

        <!-- Tombol Aksi Kanan -->
        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 sm:gap-2.5 w-full sm:w-auto">
            <a href="{{ route('qma.vehicles.support') }}" 
               class="flex-1 sm:flex-none flex items-center justify-center space-x-2 text-xs font-bold text-slate-700 hover:text-slate-900 bg-white border border-gray-200 rounded-xl px-3 sm:px-4 py-2.5 shadow-sm transition hover:bg-gray-50 cursor-pointer">
                <i class="fa-solid fa-arrow-left text-xs text-slate-500"></i>
                <span>Kembali</span>
            </a>

            <!-- Dropdown Menu Ekspor Format it.allrole.web.id -->
            <div class="relative inline-block flex-1 sm:flex-none" id="exportSupportUnitsDropdownContainer">
                <button type="button" 
                        onclick="toggleSupportUnitsExportMenu(event)" 
                        id="btnSupportUnitsExportTrigger"
                        class="w-full sm:w-auto flex items-center justify-center space-x-2 border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                    <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                    <span class="truncate">Ekspor Unit</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
                </button>
                <div id="exportMenuSupportUnits" style="display: none;" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 z-50 overflow-hidden">
                    <a href="{{ route('qma.vehicles.support.units.export_pdf', array_merge(['id' => $category->id], request()->query())) }}" target="_blank" class="flex items-center px-4 py-3 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#1b5e20] font-bold transition">
                        <i class="fa-regular fa-file-pdf text-rose-600 text-sm mr-2.5"></i>
                        <span>Format PDF (Cetak)</span>
                    </a>
                    <div class="h-px bg-slate-100 mx-2"></div>
                    <a href="{{ route('qma.vehicles.support.units.export_excel', array_merge(['id' => $category->id], request()->query())) }}" class="flex items-center px-4 py-3 text-xs text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i class="fa-regular fa-file-excel text-emerald-600 text-sm mr-2.5"></i>
                        <span>Format Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            @can('qma.vehicles.create')
            <button onclick="openAddUnitModal()" 
                    class="w-full sm:w-auto flex-1 sm:flex-none flex items-center justify-center space-x-2 bg-[#1b5e20] hover:bg-[#144919] text-white px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span class="truncate">Tambah Unit</span>
            </button>
            @endcan
        </div>
    </div>



    {{-- Alert Error / Validasi Form (Pesan sukses telah ditangani oleh Toast Notification global di layout) --}}
    @if(session('error'))
        <div class="p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
            <span class="font-semibold">{{ session('error') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs">
            <div class="font-bold mb-1 flex items-center space-x-1">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Gagal memproses data:</span>
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Kotak Pencarian & Filter Status Unit Fisik -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <form method="GET" action="{{ route('qma.vehicles.support.units', $category->id) }}" class="relative w-full max-w-sm sm:max-w-md">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" 
                   id="unitSearchInput"
                   name="search"
                   value="{{ request('search') }}" 
                   placeholder="Cari kode unit fisik, lokasi, SILO..." 
                   class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 shadow-sm transition">
        </form>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Filter Status Dropdown -->
            <form method="GET" action="{{ route('qma.vehicles.support.units', $category->id) }}" class="flex items-center space-x-2">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                <select name="status" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] shadow-sm cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="Operation" {{ request('status') === 'Operation' ? 'selected' : '' }}>Operation</option>
                    <option value="Standby" {{ request('status') === 'Standby' ? 'selected' : '' }}>Standby</option>
                    <option value="Breakdown" {{ request('status') === 'Breakdown' ? 'selected' : '' }}>Breakdown</option>
                    <option value="Maintenance" {{ request('status') === 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
            </form>

            <div id="unitResetWrapper" class="flex items-center">
                @if(request('search') || request('status'))
                    <a id="unitResetBtn" href="{{ route('qma.vehicles.support.units', $category->id) }}" class="text-xs text-rose-600 hover:text-rose-800 font-semibold flex items-center space-x-1 whitespace-nowrap ml-1">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i>
                        <span>Reset Filter</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabel Data Unit Fisik (Header Navy Solid #06213f) -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4">KODE UNIT</th>
                        <th class="py-3.5 px-4">NO. SERI / RANGKA</th>
                        <th class="py-3.5 px-4 text-center">TAHUN</th>
                        <th class="py-3.5 px-4 text-center">NO. POLISI</th>
                        <th class="py-3.5 px-4">LOKASI KERJA</th>
                        <th class="py-3.5 px-4 text-right">ODOMETER (HM/KM)</th>
                        <th class="py-3.5 px-4 text-center">DOKUMEN IZIN</th>
                        <th class="py-3.5 px-4 text-center">STATUS</th>
                        <th class="py-3.5 px-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody id="unitsTableBody" class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($units as $index => $u)
                        <tr class="unit-row hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                {{ $units->firstItem() + $index }}
                            </td>

                            <td class="py-3.5 px-4 font-bold text-slate-900 font-mono text-xs whitespace-nowrap">
                                <div class="flex items-center space-x-2.5">
                                    @if($u->photo_path)
                                        <img src="{{ $u->photo_url }}" alt="{{ $u->code }}" class="w-8 h-8 rounded-lg object-cover border border-slate-200 shadow-2xs flex-shrink-0 cursor-pointer" onclick='openDetailModal(@json($u))'>
                                    @else
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 flex-shrink-0">
                                            <i class="fa-solid fa-truck-pickup text-[10px]"></i>
                                        </div>
                                    @endif
                                    <span class="bg-blue-50 text-blue-800 px-2.5 py-1 rounded-lg border border-blue-200 font-bold">
                                        {{ $u->code }}
                                    </span>
                                    @if($u->is_timesheet_mobile)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200" title="Unit ini aktif di Timesheet Mobilisasi Karyawan">
                                            <i class="fa-solid fa-route mr-1"></i>Mobile
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3.5 px-4 font-mono text-xs text-slate-700 whitespace-nowrap">
                                @if($u->serial_number)
                                    <span class="font-semibold">{{ $u->serial_number }}</span>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center text-xs text-slate-700 font-medium whitespace-nowrap">
                                @if($u->production_year)
                                    <span>{{ $u->production_year }}</span>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center font-mono text-xs whitespace-nowrap">
                                @if($u->license_plate)
                                    <span class="bg-slate-100 text-slate-800 px-2 py-0.5 rounded border border-slate-200 font-semibold">{{ $u->license_plate }}</span>
                                @else
                                    <span class="text-slate-300 font-sans font-normal">-</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-slate-600">
                                <div class="font-medium text-slate-800">
                                    @if($u->location)
                                        <i class="fa-solid fa-location-dot text-[9px] mr-1 text-rose-500"></i>{{ $u->location }}
                                    @else
                                        <span class="text-slate-400 italic">Belum ditentukan</span>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-800 whitespace-nowrap">
                                {{ number_format($u->current_km, 0, ',', '.') }}
                                <span class="text-[10px] text-slate-400 font-sans font-normal">HM/KM</span>
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="flex flex-col items-center space-y-1">
                                    @if($u->silo_document)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200" title="SILO: {{ $u->silo_document }}">
                                            <i class="fa-solid fa-certificate mr-1 text-[9px] text-emerald-600"></i> SILO Ada
                                        </span>
                                    @else
                                        <span class="text-[10px] text-slate-400 italic">Tanpa SILO</span>
                                    @endif

                                    @if($u->kir_document)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-mono bg-slate-100 text-slate-600 border border-slate-200">
                                            KIR: {{ $u->kir_document }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($u->status === 'Operation')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Operation
                                    </span>
                                @elseif($u->status === 'Standby')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Standby
                                    </span>
                                @elseif($u->status === 'Breakdown')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Breakdown
                                    </span>
                                @elseif($u->status === 'Maintenance')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        Maintenance
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                                        {{ $u->status }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <a href="{{ route('p2h.form', ['code' => $u->code]) }}" 
                                       target="_blank"
                                       class="inline-flex items-center space-x-1 text-[#1b5e20] hover:text-[#144918] hover:bg-emerald-50 px-2 py-1 rounded-lg transition"
                                       title="Buka Form P2H Unit Ini">
                                        <i class="fa-solid fa-file-pen text-sm"></i>
                                    </a>
                                    <button onclick='openDetailModal(@json($u))' 
                                            class="inline-flex items-center space-x-1 text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-2 py-1 rounded-lg transition"
                                            title="Lihat Detail Unit">
                                        <i class="fa-regular fa-eye text-sm"></i>
                                    </button>
                                    <button onclick='openEditModal(@json($u))' 
                                            class="inline-flex items-center space-x-1 text-amber-600 hover:text-amber-800 hover:bg-amber-50 px-2 py-1 rounded-lg transition"
                                            title="Edit Data Unit">
                                        <i class="fa-regular fa-pen-to-square text-sm"></i>
                                    </button>
                                    <button type="button" 
                                            onclick="openDeleteModal('{{ $u->id }}', '{{ $u->code }}')"
                                            class="inline-flex items-center space-x-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-2 py-1 rounded-lg transition"
                                            title="Hapus Unit Fisik">
                                        <i class="fa-regular fa-trash-can text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="serverUnitEmptyRow">
                            <td colspan="10" class="py-12 px-4 text-center text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
                                @if(request('search'))
                                    <p class="text-sm font-semibold text-slate-700">
                                        Data "<span class="text-rose-600 font-bold">{{ request('search') }}</span>" tidak ditemukan di tabel
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        Pastikan kata kunci yang dicari sudah benar.
                                    </p>
                                    <div class="mt-4">
                                        <a href="{{ route('qma.vehicles.support.units', array_merge(['id' => $category->id], request()->except('search', 'page'))) }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                            <span>Hapus Pencarian</span>
                                        </a>
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                                            <i class="fa-solid fa-cubes text-xl"></i>
                                        </div>
                                        <p class="font-semibold text-slate-700 text-sm">Belum ada unit fisik untuk kategori ini</p>
                                        <p class="text-xs text-slate-400 mt-1 max-w-sm">
                                            Kategori ini belum memiliki armada fisik terdaftar. Silakan klik tombol di bawah untuk menambahkan unit fisik pertama.
                                        </p>
                                        <button onclick="openAddUnitModal()" class="mt-4 bg-[#1b5e20] hover:bg-[#144919] text-white px-4 py-2 rounded-xl text-xs font-bold transition inline-flex items-center space-x-2">
                                            <i class="fa-solid fa-plus text-xs"></i>
                                            <span>Tambah Unit Baru</span>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="unitsPaginationContainer">
            @if($units->hasPages())
                <div class="px-6 py-4 border-t border-slate-200">
                    {{ $units->links() }}
                </div>
            @endif
        </div>
    </div>

</div>

<!-- Modal Tambah Unit Baru (Sesuai Referensi Gambar media_1792028135493_30b2da2e.png) -->
<div id="addUnitModal" class="hidden fixed inset-0 z-50 overflow-y-auto" style="display: none;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" aria-hidden="true" onclick="closeAddUnitModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative z-10 w-full max-w-lg bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all border border-slate-200 p-6">
            <!-- Judul Modal -->
            <h3 class="text-xl font-extrabold text-slate-900 tracking-tight mb-5" id="modal-title">
                Tambah Unit Baru
            </h3>

            <form method="POST" action="{{ route('qma.vehicles.support.units.store', $category->id) }}" id="addUnitForm" enctype="multipart/form-data" class="space-y-4">
                @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Foto Unit Fisik (Opsional)</label>
                        <div class="flex items-center space-x-3.5 p-3 rounded-2xl border border-slate-200 bg-slate-50/50">
                            <div id="addPhotoPreviewContainer" class="w-14 h-14 rounded-xl border-2 border-dashed border-slate-300 bg-white flex items-center justify-center overflow-hidden flex-shrink-0 shadow-2xs">
                                <img id="addPhotoPreview" src="" alt="Pratinjau" class="w-full h-full object-cover hidden">
                                <i id="addPhotoPlaceholder" class="fa-solid fa-camera text-slate-400 text-base"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <input type="file" 
                                       name="photo" 
                                       id="addPhotoInput" 
                                       accept="image/jpeg,image/png,image/jpg,image/webp" 
                                       onchange="previewUnitImage(this, 'addPhotoPreview', 'addPhotoPlaceholder')"
                                       class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-[#1b5e20] hover:file:bg-emerald-100 cursor-pointer">
                                <p class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, WebP (Maks. 10MB). Resolusi otomatis dikompresi hemat storage.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Aset Induk (Readonly) Sesuai Mockup -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Aset Induk
                        </label>
                        <input type="text" 
                               readonly
                               value="{{ $category->code }} - {{ $category->brand_type ?? $category->unit_category }}" 
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-600 font-medium focus:outline-none cursor-not-allowed">
                    </div>

                    <!-- Kode Unit * (Prefix Box + Input Urut Hijau Border Sesuai Mockup) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Kode Unit <span class="text-rose-500">*</span>
                        </label>
                        <div class="flex rounded-xl overflow-hidden border-2 border-emerald-700 focus-within:ring-2 focus-within:ring-emerald-600/20 transition shadow-2xs">
                            <span class="inline-flex items-center px-3.5 py-2 bg-slate-100 text-slate-700 text-xs font-bold font-mono border-r border-slate-200 select-none">
                                {{ $category->code }}-
                            </span>
                            <input type="text" 
                                   name="unit_number"
                                   id="unitNumberInput"
                                   required
                                   value="{{ $nextUnitNumber }}"
                                   placeholder="Contoh: 001" 
                                   class="w-full text-xs px-3.5 py-2.5 bg-white text-slate-900 font-mono font-bold placeholder-slate-400 focus:outline-none">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">
                            Kode lengkap: <span id="fullCodePreview" class="font-mono font-bold text-slate-700">{{ $nextUnitCode }}</span>
                        </p>
                    </div>

                    <!-- Nomor Seri / Rangka & Nomor Polisi / Plat -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                No. Seri / Rangka
                            </label>
                            <input type="text" 
                                   name="serial_number"
                                   placeholder="Contoh: VIN/SN12345" 
                                   class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono focus:outline-none focus:border-emerald-600">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Nomor Polisi / Plat
                            </label>
                            <input type="text" 
                                   name="license_plate"
                                   placeholder="Contoh: BH 1234 XX" 
                                   class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono uppercase focus:outline-none focus:border-emerald-600">
                        </div>
                    </div>

                    <!-- Tahun Produksi & Status Operasi Awal -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Tahun Produksi
                            </label>
                            <input type="number" 
                                   name="production_year"
                                   min="1980"
                                   max="{{ date('Y') + 1 }}"
                                   placeholder="Contoh: 2021" 
                                   class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Status Operasional <span class="text-rose-500">*</span>
                            </label>
                            <select name="status" required class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-emerald-600">
                                <option value="Operation" selected>Operation (Siap Operasi)</option>
                                <option value="Standby">Standby (Tersedia)</option>
                                <option value="Breakdown">Breakdown (Rusak)</option>
                                <option value="Maintenance">Maintenance (Perawatan)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Odometer Awal (HM/KM) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Odometer Awal (HM/KM)
                            </label>
                            <input type="number" 
                                   name="current_km"
                                   step="0.01"
                                   min="0"
                                   value="0"
                                   placeholder="0" 
                                   class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Lokasi Penempatan
                            </label>
                            <select name="location" 
                                    class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-medium focus:outline-none focus:border-emerald-600">
                                <option value="" disabled {{ empty($category->location) ? 'selected' : '' }}>-- Pilih Lokasi Penempatan --</option>
                                @foreach($masterLocations as $loc)
                                    <option value="{{ $loc }}" {{ (old('location') ?? $category->location) === $loc ? 'selected' : '' }}>
                                        {{ $loc }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Dokumen SILO & KIR -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                No Dokumen SILO
                            </label>
                            <input type="text" 
                                   name="silo_document"
                                   placeholder="Nomor SILO (opsional)" 
                                   class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                No Dokumen KIR
                            </label>
                            <input type="text" 
                                   name="kir_document"
                                   placeholder="Nomor KIR (opsional)" 
                                   class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600">
                        </div>
                    </div>

                    <!-- Pengaturan Timesheet Mobilisasi & Izin SIMPER -->
                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" name="is_timesheet_mobile" id="add_is_timesheet_mobile" value="1"
                                   class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer"
                                   onchange="toggleSimperReq('add')">
                            <label for="add_is_timesheet_mobile" class="text-xs font-bold text-slate-800 cursor-pointer">
                                Izinkan Unit untuk Timesheet Mobilisasi Karyawan & Gerbang Pos Satpam
                            </label>
                        </div>
                        <div id="add_simper_container" class="hidden">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Wajib Jenis SIMPER untuk Driver / Operator
                            </label>
                            <select name="required_simper_type_id" class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-medium focus:outline-none focus:border-emerald-600">
                                <option value="">-- Standar (Sesuai Kategori / Default) --</option>
                                @foreach($simperTypes ?? [] as $st)
                                    <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->code }})</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1">Driver yang mobilisasi unit ini wajib memiliki izin SIMPER aktif yang sesuai.</p>
                        </div>
                    </div>

                    <div class="pt-4 flex items-center justify-end space-x-3">
                        <button type="button" 
                                onclick="closeAddUnitModal()" 
                                class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-5 py-2.5 text-xs font-bold text-white bg-[#1b5e20] hover:bg-[#144919] rounded-xl transition shadow-sm cursor-pointer">
                            Simpan Unit
                        </button>
                    </div>
                </form>
        </div>
    </div>
</div>

<!-- Modal Edit Data Unit Fisik -->
<div id="editModal" class="hidden fixed inset-0 z-50 overflow-y-auto" style="display: none;" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeEditModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative z-10 w-full max-w-lg bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all border border-slate-200 p-6">
            <h3 class="text-xl font-extrabold text-slate-900 tracking-tight mb-5">
                Edit Data Unit Fisik
            </h3>
                <form id="editUnitForm" method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="category" value="unit_support">
                    <input type="hidden" name="brand_type" id="edit_brand_type">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Foto Unit Fisik</label>
                        <div class="flex items-center space-x-3.5 p-3 rounded-2xl border border-slate-200 bg-slate-50/50">
                            <div id="editPhotoPreviewContainer" class="w-14 h-14 rounded-xl border border-slate-200 bg-white flex items-center justify-center overflow-hidden flex-shrink-0 shadow-2xs">
                                <img id="editPhotoPreview" src="" alt="Foto Unit" class="w-full h-full object-cover hidden">
                                <i id="editPhotoPlaceholder" class="fa-solid fa-truck-pickup text-slate-400 text-base"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <input type="file" 
                                       name="photo" 
                                       id="editPhotoInput" 
                                       accept="image/jpeg,image/png,image/jpg,image/webp" 
                                       onchange="previewUnitImage(this, 'editPhotoPreview', 'editPhotoPlaceholder')"
                                       class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-[#1b5e20] hover:file:bg-emerald-100 cursor-pointer">
                                <p class="text-[10px] text-slate-400 mt-1">Pilih foto baru jika ingin mengganti. Otomatis dikompresi hemat storage.</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Kode Unit <span class="text-rose-500">*</span></label>
                        <input type="text" name="code" id="edit_code" required class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-100 text-slate-700 font-mono font-bold focus:outline-none" readonly>
                    </div>

                    <!-- Nomor Seri / Rangka & Nomor Polisi / Plat -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">No. Seri / Rangka</label>
                            <input type="text" name="serial_number" id="edit_serial_number" placeholder="Contoh: VIN/SN12345" class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono focus:outline-none focus:border-emerald-600">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor Polisi / Plat</label>
                            <input type="text" name="license_plate" id="edit_license_plate" placeholder="Contoh: BH 1234 XX" class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono uppercase focus:outline-none focus:border-emerald-600">
                        </div>
                    </div>

                    <!-- Tahun Produksi & Status Operasi -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Tahun Produksi</label>
                            <input type="number" name="production_year" id="edit_production_year" min="1980" max="{{ date('Y') + 1 }}" placeholder="Contoh: 2021" class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Status Operasi <span class="text-rose-500">*</span></label>
                            <select name="status" id="edit_status" required class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white font-semibold text-slate-800 focus:outline-none focus:border-emerald-600">
                                <option value="Operation">Operation</option>
                                <option value="Standby">Standby</option>
                                <option value="Breakdown">Breakdown</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Odometer (HM/KM) <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" name="current_km" id="edit_current_km" required class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono focus:outline-none focus:border-emerald-600">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Lokasi Penempatan</label>
                            <select name="location" id="edit_location" class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-medium focus:outline-none focus:border-emerald-600">
                                <option value="">-- Pilih Lokasi Penempatan --</option>
                                @foreach($masterLocations as $loc)
                                    <option value="{{ $loc }}">{{ $loc }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Dokumen SILO</label>
                            <input type="text" name="silo_document" id="edit_silo" class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Dokumen KIR</label>
                            <input type="text" name="kir_document" id="edit_kir" class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600">
                        </div>
                    </div>

                    <!-- Pengaturan Timesheet Mobilisasi & Izin SIMPER -->
                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" name="is_timesheet_mobile" id="edit_is_timesheet_mobile" value="1"
                                   class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer"
                                   onchange="toggleSimperReq('edit')">
                            <label for="edit_is_timesheet_mobile" class="text-xs font-bold text-slate-800 cursor-pointer">
                                Izinkan Unit untuk Timesheet Mobilisasi Karyawan & Gerbang Pos Satpam
                            </label>
                        </div>
                        <div id="edit_simper_container" class="hidden">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Wajib Jenis SIMPER untuk Driver / Operator
                            </label>
                            <select name="required_simper_type_id" id="edit_required_simper_type_id" class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-medium focus:outline-none focus:border-emerald-600">
                                <option value="">-- Standar (Sesuai Kategori / Default) --</option>
                                @foreach($simperTypes ?? [] as $st)
                                    <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->code }})</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1">Driver yang mobilisasi unit ini wajib memiliki izin SIMPER aktif yang sesuai.</p>
                        </div>
                    </div>

                    <div class="pt-4 flex items-center justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-[#1b5e20] hover:bg-[#144919] rounded-xl transition shadow-sm cursor-pointer">Simpan Perubahan</button>
                    </div>
                </form>
        </div>
    </div>
</div>

<!-- Modal Detail Unit Fisik -->
<div id="detailModal" class="hidden fixed inset-0 z-50 overflow-y-auto" style="display: none;" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeDetailModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative z-10 w-full max-w-md bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all border border-slate-200 p-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">DETAIL UNIT FISIK</span>
                    <h3 class="text-xl font-extrabold text-slate-900" id="detail_code">-</h3>
                </div>
                <button onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="py-4 space-y-3 text-xs">
                <!-- Foto Unit Fisik di Detail Modal -->
                <div id="detail_photo_container" class="w-full h-44 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden relative hidden mb-3">
                    <img id="detail_photo" src="" alt="Foto Unit Fisik" class="w-full h-full object-cover">
                </div>

                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-400">Kategori Induk:</span>
                    <span class="font-bold text-slate-800">{{ $category->code }} - {{ $category->unit_category }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-400">Merek & Tipe:</span>
                    <span class="font-semibold text-slate-800">{{ $category->brand_type }}</span>
                </div>
                @if($category->model_number)
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-400">Nomor Model:</span>
                        <span class="font-mono font-medium text-slate-800">{{ $category->model_number }}</span>
                    </div>
                @endif
                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-400">No. Seri / Rangka:</span>
                    <span id="detail_serial_number" class="font-mono font-medium text-slate-800">-</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-400">Nomor Polisi / Plat:</span>
                    <span id="detail_plate" class="font-mono font-medium text-slate-800">-</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-400">Tahun Produksi:</span>
                    <span id="detail_year" class="font-medium text-slate-800">-</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-400">Status Operasi:</span>
                    <span id="detail_status" class="font-bold">-</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-400">Odometer:</span>
                    <span id="detail_km" class="font-mono font-bold text-slate-800">-</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-400">Lokasi Kerja:</span>
                    <span id="detail_location" class="font-medium text-slate-800">-</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-400">Dokumen SILO:</span>
                    <span id="detail_silo" class="font-medium text-slate-800">-</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-400">Dokumen KIR:</span>
                    <span id="detail_kir" class="font-medium text-slate-800">-</span>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end">
                <button onclick="closeDetailModal()" class="px-5 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hapus Unit Fisik -->
<div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto" style="display: none;" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeDeleteModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative z-10 w-full max-w-sm bg-white rounded-2xl text-center overflow-hidden shadow-2xl transform transition-all border border-slate-200 p-6">
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-triangle-exclamation text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-2">Hapus Unit Fisik?</h3>
            <p class="text-xs text-slate-500 mb-6">
                Apakah Anda yakin ingin menghapus unit <span id="deleteUnitCode" class="font-mono font-bold text-slate-800"></span>? Data yang sudah dihapus tidak dapat dipulihkan.
            </p>
            <form id="deleteForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="flex items-center justify-center space-x-3">
                    <button type="button" onclick="closeDeleteModal()" class="px-5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition shadow-sm cursor-pointer">Ya, Hapus Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const categoryCode = "{{ $category->code }}";

    function toggleSimperReq(prefix) {
        const chk = document.getElementById(prefix + '_is_timesheet_mobile');
        const container = document.getElementById(prefix + '_simper_container');
        if (chk && container) {
            if (chk.checked) {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }
    }

    function previewUnitImage(input, imgId, placeholderId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(imgId);
                const placeholder = document.getElementById(placeholderId);
                if (img) {
                    img.src = e.target.result;
                    img.classList.remove('hidden');
                }
                if (placeholder) {
                    placeholder.classList.add('hidden');
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function openAddUnitModal() {
        const modal = document.getElementById('addUnitModal');
        modal.classList.remove('hidden');
        modal.style.display = 'block';
        const addPhotoPreview = document.getElementById('addPhotoPreview');
        const addPhotoPlaceholder = document.getElementById('addPhotoPlaceholder');
        const addPhotoInput = document.getElementById('addPhotoInput');
        if (addPhotoInput) addPhotoInput.value = '';
        if (addPhotoPreview) {
            addPhotoPreview.src = '';
            addPhotoPreview.classList.add('hidden');
        }
        if (addPhotoPlaceholder) {
            addPhotoPlaceholder.classList.remove('hidden');
        }
        const addMobileChk = document.getElementById('add_is_timesheet_mobile');
        if (addMobileChk) addMobileChk.checked = false;
        toggleSimperReq('add');
        document.getElementById('unitNumberInput').focus();
    }

    function closeAddUnitModal() {
        const modal = document.getElementById('addUnitModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    // Live preview kode lengkap
    document.getElementById('unitNumberInput')?.addEventListener('input', function(e) {
        let val = e.target.value.trim();
        if (val) {
            document.getElementById('fullCodePreview').textContent = categoryCode + '-' + val;
        } else {
            document.getElementById('fullCodePreview').textContent = '-';
        }
    });

    function openEditModal(unit) {
        const form = document.getElementById('editUnitForm');
        form.action = "{{ url('qma/vehicles') }}/" + unit.id;
        document.getElementById('edit_code').value = unit.code;
        document.getElementById('edit_brand_type').value = unit.brand_type || "{{ $category->brand_type }}";
        document.getElementById('edit_serial_number').value = unit.serial_number || '';
        document.getElementById('edit_license_plate').value = unit.license_plate || '';
        document.getElementById('edit_production_year').value = unit.production_year || '';
        document.getElementById('edit_status').value = unit.status || 'Operation';
        document.getElementById('edit_current_km').value = unit.current_km || 0;
        const locSelect = document.getElementById('edit_location');
        if (locSelect) {
            if (unit.location) {
                let found = Array.from(locSelect.options).some(opt => opt.value === unit.location);
                if (!found) {
                    const newOpt = document.createElement('option');
                    newOpt.value = unit.location;
                    newOpt.textContent = unit.location;
                    locSelect.appendChild(newOpt);
                }
                locSelect.value = unit.location;
            } else {
                locSelect.value = '';
            }
        }
        document.getElementById('edit_silo').value = unit.silo_document || '';
        document.getElementById('edit_kir').value = unit.kir_document || '';

        const editPhotoPreview = document.getElementById('editPhotoPreview');
        const editPhotoPlaceholder = document.getElementById('editPhotoPlaceholder');
        const editPhotoInput = document.getElementById('editPhotoInput');
        if (editPhotoInput) editPhotoInput.value = '';
        if (unit.photo_url) {
            if (editPhotoPreview) {
                editPhotoPreview.src = unit.photo_url;
                editPhotoPreview.classList.remove('hidden');
            }
            if (editPhotoPlaceholder) editPhotoPlaceholder.classList.add('hidden');
        } else {
            if (editPhotoPreview) {
                editPhotoPreview.src = '';
                editPhotoPreview.classList.add('hidden');
            }
            if (editPhotoPlaceholder) editPhotoPlaceholder.classList.remove('hidden');
        }

        const isMobileChk = document.getElementById('edit_is_timesheet_mobile');
        if (isMobileChk) {
            isMobileChk.checked = !!(unit.is_timesheet_mobile == 1 || unit.is_timesheet_mobile === true);
        }
        const simperSelect = document.getElementById('edit_required_simper_type_id');
        if (simperSelect) {
            simperSelect.value = unit.required_simper_type_id || '';
        }
        toggleSimperReq('edit');

        const modal = document.getElementById('editModal');
        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }

    function closeEditModal() {
        const modal = document.getElementById('editModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    function openDetailModal(unit) {
        document.getElementById('detail_code').textContent = unit.code;
        document.getElementById('detail_serial_number').textContent = unit.serial_number || 'Tidak ada';
        document.getElementById('detail_plate').textContent = unit.license_plate || 'Tidak ada';
        document.getElementById('detail_year').textContent = unit.production_year || 'Tidak ada';
        document.getElementById('detail_status').textContent = unit.status;
        document.getElementById('detail_km').textContent = Number(unit.current_km).toLocaleString('id-ID') + ' HM/KM';
        document.getElementById('detail_location').textContent = unit.location || '-';
        document.getElementById('detail_silo').textContent = unit.silo_document || 'Tidak ada';
        document.getElementById('detail_kir').textContent = unit.kir_document || 'Tidak ada';

        const detailPhotoContainer = document.getElementById('detail_photo_container');
        const detailPhoto = document.getElementById('detail_photo');
        if (unit.photo_url) {
            if (detailPhoto) detailPhoto.src = unit.photo_url;
            if (detailPhotoContainer) detailPhotoContainer.classList.remove('hidden');
        } else {
            if (detailPhoto) detailPhoto.src = '';
            if (detailPhotoContainer) detailPhotoContainer.classList.add('hidden');
        }

        const modal = document.getElementById('detailModal');
        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }

    function closeDetailModal() {
        const modal = document.getElementById('detailModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    function openDeleteModal(id, code) {
        const form = document.getElementById('deleteForm');
        form.action = "{{ url('qma/vehicles') }}/" + id;
        document.getElementById('deleteUnitCode').textContent = code;
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    // =========================================================================
    // REAL-TIME CLIENT-SIDE SEARCH WITH MULTI-FIELD MATCHING & EMPTY STATE
    // =========================================================================
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('unitSearchInput');
        const tableBody = document.getElementById('unitsTableBody');
        const resetWrapper = document.getElementById('unitResetWrapper');
        const paginationContainer = document.getElementById('unitsPaginationContainer');
        const serverEmptyRow = document.getElementById('serverUnitEmptyRow');
        const hasServerFilters = {{ request()->anyFilled(['status']) ? 'true' : 'false' }};

        if (searchInput && tableBody) {
            searchInput.addEventListener('input', function (e) {
                const rawValue = e.target.value;
                const query = rawValue.toLowerCase().trim();
                const rows = tableBody.querySelectorAll('tr.unit-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (paginationContainer) {
                    paginationContainer.style.display = query.length > 0 ? 'none' : '';
                }

                const existingEmptyRow = document.getElementById('clientUnitEmptyRow');
                if (existingEmptyRow) existingEmptyRow.remove();

                if (serverEmptyRow) {
                    serverEmptyRow.style.display = query.length > 0 ? 'none' : '';
                }

                if (visibleCount === 0 && query.length > 0) {
                    const emptyTr = document.createElement('tr');
                    emptyTr.id = 'clientUnitEmptyRow';
                    emptyTr.innerHTML = `
                        <td colspan="10" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
                            <p class="text-sm font-semibold text-slate-700">
                                Data "<span class="text-rose-600 font-bold">${escapeHtml(rawValue)}</span>" tidak ditemukan di tabel
                            </p>
                            <p class="text-xs text-slate-400 mt-1">
                                Pastikan kata kunci yang dicari sudah benar.
                            </p>
                            <div class="mt-4">
                                <button type="button" 
                                        onclick="const inp = document.getElementById('unitSearchInput'); if(inp){ inp.value=''; inp.dispatchEvent(new Event('input')); inp.focus(); }" 
                                        class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                    <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                    <span>Hapus Pencarian</span>
                                </button>
                            </div>
                        </td>
                    `;
                    tableBody.appendChild(emptyTr);
                }

                if (resetWrapper) {
                    let resetBtn = document.getElementById('unitResetBtn');
                    if (query.length > 0 || hasServerFilters) {
                        if (!resetBtn) {
                            resetBtn = document.createElement('a');
                            resetBtn.id = 'unitResetBtn';
                            resetBtn.href = "{{ route('qma.vehicles.support.units', $category->id) }}";
                            resetBtn.className = 'text-xs text-rose-600 hover:text-rose-800 font-semibold flex items-center space-x-1 whitespace-nowrap ml-1';
                            resetBtn.innerHTML = '<i class="fa-solid fa-rotate-left text-[11px]"></i><span>Reset Filter</span>';
                            resetWrapper.appendChild(resetBtn);
                        }
                    } else if (!hasServerFilters && resetBtn) {
                        resetBtn.remove();
                    }
                }
            });

            // Trigger saat awal memuat halaman jika ada nilai pencarian dari SSR
            if (searchInput.value.trim() !== '') {
                searchInput.dispatchEvent(new Event('input'));
            }
        }
    });

    // Dropdown Menu Ekspor Heavy Equipment Kategori Ini
    function toggleSupportUnitsExportMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('exportMenuSupportUnits');
        if (menu) {
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }
    }

    document.addEventListener('click', function(event) {
        const container = document.getElementById('exportSupportUnitsDropdownContainer');
        const menu = document.getElementById('exportMenuSupportUnits');
        if (container && !container.contains(event.target)) {
            if (menu) {
                menu.style.display = 'none';
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const exportMenu = document.getElementById('exportMenuSupportUnits');
            if (exportMenu) exportMenu.style.display = 'none';
        }
    });
</script>
@endsection
