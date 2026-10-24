@extends('layouts.app')

@section('title', 'Berita Acara Transfer Unit - ' . $transfer->document_number . ' | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full max-w-4xl mx-auto space-y-6 pb-16">

    <!-- Top Navigation & Action Buttons (Hidden when printing) -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 print:hidden">
        <div>
            <div class="flex items-center space-x-2 text-xs font-bold text-slate-400 mb-1">
                <a href="{{ route('qma.unit_transfers.index') }}" class="text-[#1b5e20] hover:underline">
                    TRANSFER UNIT
                </a>
                <span>/</span>
                <span class="text-slate-600 font-mono">{{ $transfer->transfer_no }}</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Berita Acara Transfer Alat Berat
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Surat bukti serah terima pemindahan alat berat resmi di lingkungan PT Kerinci Merangin Hidro.
            </p>
        </div>

        <div class="flex items-center space-x-2.5 w-full sm:w-auto">
            <a href="{{ route('qma.unit_transfers.index') }}" 
               class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-2xs">
                <i class="fa-solid fa-arrow-left mr-1.5"></i>
                <span>Kembali</span>
            </a>

            <!-- Tombol Cetak / Print -->
            <button type="button" 
                    onclick="window.print()" 
                    class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-sm inline-flex items-center space-x-1.5 cursor-pointer">
                <i class="fa-solid fa-print"></i>
                <span>Cetak BAST</span>
            </button>

            <!-- Tombol Buat Baru -->
            @canany(['qma.vehicles.create', 'qma.vehicles.edit'])
            <a href="{{ route('qma.unit_transfers.create') }}" 
               class="px-4 py-2 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white text-xs font-bold transition shadow-sm inline-flex items-center space-x-1.5">
                <i class="fa-solid fa-plus"></i>
                <span>Transfer Baru</span>
            </a>
            @endcanany
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if(session('success'))
    <div class="flex items-center space-x-3 p-4 bg-emerald-50/90 border border-emerald-200 rounded-2xl text-emerald-800 text-xs sm:text-sm font-semibold shadow-2xs print:hidden">
        <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-check"></i>
        </div>
        <div class="flex-1">
            {{ session('success') }}
        </div>
    </div>
    @endif

    <!-- ================================================================= -->
    <!-- DOKUMEN RESMI BERITA ACARA SERAH TERIMA & PEMINDAHAN (BAST) -->
    <!-- ================================================================= -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-md p-6 sm:p-10 space-y-8 text-slate-800 print:shadow-none print:border-none print:p-0">

        <!-- KOP SURAT RESMI PERUSAHAAN -->
        <div class="border-b-2 border-slate-900 pb-4 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="{{ asset('images/logo-kerinci.png') }}" 
                     alt="PT Kerinci Merangin Hidro" 
                     class="h-12 sm:h-14 w-auto object-contain shrink-0">
                <div>
                    <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-tight uppercase">
                        PT KERINCI MERANGIN HIDRO
                    </h3>
                    <p class="text-xs font-semibold text-slate-600">
                        Kerinci Hydropower Project (350 MW) &bull; Batang Merangin, Kerinci
                    </p>
                    <p class="text-[11px] font-bold text-emerald-800 tracking-wider uppercase mt-0.5">
                        DIVISI QUALITY & MAINTENANCE ASSET (QMA)
                    </p>
                </div>
            </div>
            <div class="text-right hidden sm:block">
                <span class="inline-block px-3 py-1 bg-slate-100 rounded-lg font-mono text-xs font-extrabold text-slate-800 border border-slate-200">
                    {{ $transfer->transfer_no }}
                </span>
                <p class="text-[10px] text-slate-400 mt-1">Dokumen Resmi Proyek</p>
            </div>
        </div>

        <!-- JUDUL & NOMOR BERITA ACARA -->
        <div class="text-center space-y-1.5">
            <h2 class="text-lg sm:text-xl font-black text-slate-900 uppercase tracking-wide">
                BERITA ACARA SERAH TERIMA & PEMINDAHAN ALAT BERAT
            </h2>
            <p class="font-mono text-xs sm:text-sm font-bold text-slate-700">
                Nomor: {{ $transfer->document_number }}
            </p>
            <div class="inline-block mt-1 px-3 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                Perihal: {{ $transfer->document_title }}
            </div>
        </div>

        <!-- PARAGRAF PEMBUKA -->
        <p class="text-xs sm:text-sm leading-relaxed text-slate-700 text-justify">
            Pada hari ini, <strong>{{ $transfer->transfer_date ? $transfer->transfer_date->translatedFormat('l, d F Y') : '-' }}</strong>, telah dilaksanakan pemindahan dan serah terima unit alat berat/support unit di lingkungan operasional PT Kerinci Merangin Hidro dengan rincian sebagai berikut:
        </p>

        <!-- TABEL RINCIAN 1: UNIT ALAT BERAT YANG DIPINDAHKAN -->
        <div class="space-y-2">
            <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-[#1b5e20]"></span>
                <span>A. Rincian Unit Alat Berat</span>
            </h4>
            <div class="rounded-xl border border-slate-200 overflow-hidden text-xs">
                <table class="w-full text-left">
                    <tbody class="divide-y divide-slate-100">
                        <tr class="bg-slate-50/50">
                            <td class="py-2.5 px-4 font-bold text-slate-600 w-1/3">Kode Unit</td>
                            <td class="py-2.5 px-4 font-black text-slate-900 font-mono text-sm">{{ $transfer->vehicle?->code ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-4 font-bold text-slate-600">Kategori & Jenis Unit</td>
                            <td class="py-2.5 px-4 font-semibold text-slate-800">
                                {{ $transfer->vehicle?->unit_category ?? ($transfer->vehicle?->parent?->brand_type ?? 'Heavy Equipment') }}
                                @if($transfer->vehicle?->unit_type)
                                    &bull; {{ $transfer->vehicle?->unit_type }}
                                @endif
                            </td>
                        </tr>
                        <tr class="bg-slate-50/50">
                            <td class="py-2.5 px-4 font-bold text-slate-600">Merek / Model / Tipe</td>
                            <td class="py-2.5 px-4 text-slate-800">{{ $transfer->vehicle?->brand_type ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-4 font-bold text-slate-600">Nomor Seri / Plat</td>
                            <td class="py-2.5 px-4 font-mono text-slate-700">
                                {{ $transfer->vehicle?->serial_number ?: ($transfer->vehicle?->license_plate ?: '-') }}
                            </td>
                        </tr>
                        <tr class="bg-slate-50/50">
                            <td class="py-2.5 px-4 font-bold text-slate-600">Lokasi Asal (Sebelumnya)</td>
                            <td class="py-2.5 px-4 font-medium text-slate-700">
                                {{ $transfer->from_location }} 
                                <span class="text-[10px] text-slate-400">({{ $transfer->from_site_status_label }})</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-4 font-bold text-slate-600">Lokasi Tujuan Baru</td>
                            <td class="py-2.5 px-4 font-extrabold text-[#1b5e20] text-sm flex items-center space-x-2">
                                <span>{{ $transfer->to_location }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $transfer->isToInsideSite() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $transfer->to_site_status_label }}
                                </span>
                            </td>
                        </tr>
                        @if($transfer->description)
                        <tr class="bg-slate-50/50">
                            <td class="py-2.5 px-4 font-bold text-slate-600">Keperluan Pemindahan</td>
                            <td class="py-2.5 px-4 text-slate-700 leading-relaxed">{{ $transfer->description }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABEL RINCIAN 2: ARMADA & SUPIR PENGANGKUT (TRANSPORTER) -->
        <div class="space-y-2">
            <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                <span>B. Rincian Armada & Supir Pengangkut</span>
            </h4>
            <div class="rounded-xl border border-slate-200 overflow-hidden text-xs">
                <table class="w-full text-left">
                    <tbody class="divide-y divide-slate-100">
                        <tr class="bg-slate-50/50">
                            <td class="py-2.5 px-4 font-bold text-slate-600 w-1/3">Kendaraan Pengangkut</td>
                            <td class="py-2.5 px-4 font-bold text-slate-900">
                                {{ $transfer->transporter_unit_name ?: ($transfer->transporterVehicle?->code ?? 'Tanpa Truk Pengangkut (Mobilisasi Mandiri)') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-4 font-bold text-slate-600">Nama Pengemudi / Driver</td>
                            <td class="py-2.5 px-4 font-semibold text-slate-800">
                                {{ $transfer->transporter_driver_name ?: ($transfer->transporterDriver?->name ?? '-') }}
                            </td>
                        </tr>
                        @if($transfer->transporter_description)
                        <tr class="bg-slate-50/50">
                            <td class="py-2.5 px-4 font-bold text-slate-600">Catatan Pengangkutan</td>
                            <td class="py-2.5 px-4 text-slate-700 leading-relaxed">{{ $transfer->transporter_description }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- LAMPIRAN BERKAS JIKA ADA (Hidden on print if wanted, or shown) -->
        @if($transfer->document_file_path)
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between print:hidden">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-[#1b5e20] flex items-center justify-center font-bold text-base">
                    <i class="fa-solid fa-file-pdf"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-800">Berkas Lampiran Surat / Scan Dokumen</p>
                    <p class="text-[11px] text-slate-500">Tersimpan di sistem penyimpanan dokumen proyek.</p>
                </div>
            </div>
            <a href="{{ $transfer->document_file_url }}" target="_blank" class="px-3.5 py-1.5 bg-[#1b5e20] hover:bg-[#144919] text-white text-xs font-bold rounded-xl transition shadow-sm inline-flex items-center space-x-1">
                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                <span>Buka Berkas</span>
            </a>
        </div>
        @endif

        <!-- PARAGRAF PENUTUP -->
        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed text-justify">
            Demikian Berita Acara ini dibuat dengan sebenarnya dalam rangkap yang sah untuk dipergunakan sebagaimana mestinya dalam tertib administrasi aset dan operasional alat berat proyek Kerinci Merangin Hidro.
        </p>

        <!-- KOLOM TANDA TANGAN (4 PIHAK) -->
        <div class="pt-6">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center text-xs">
                <!-- 1. Yang Menyerahkan (Driver / Pengangkut) -->
                <div class="flex flex-col justify-between min-h-[140px] p-2 border border-slate-200 rounded-xl bg-slate-50/40">
                    <p class="font-bold text-slate-600 text-[11px]">Yang Menyerahkan / Pengangkut,</p>
                    <div class="py-6">
                        <span class="text-[10px] text-slate-300 italic">(Tanda Tangan)</span>
                    </div>
                    <div>
                        <p class="font-bold text-slate-900 border-t border-slate-300 pt-1">
                            {{ $transfer->transporter_driver_name ?: 'Driver Pengangkut' }}
                        </p>
                        <p class="text-[10px] text-slate-400">Pengemudi / Operator</p>
                    </div>
                </div>

                <!-- 2. Yang Melaporkan (Petugas QMA) -->
                <div class="flex flex-col justify-between min-h-[140px] p-2 border border-slate-200 rounded-xl bg-slate-50/40">
                    <p class="font-bold text-slate-600 text-[11px]">Yang Melaporkan,</p>
                    <div class="py-6">
                        <span class="text-[10px] text-slate-300 italic">(Tanda Tangan)</span>
                    </div>
                    <div>
                        <p class="font-bold text-slate-900 border-t border-slate-300 pt-1">
                            {{ $transfer->reported_by }}
                        </p>
                        <p class="text-[10px] text-slate-400">Petugas QMA Field</p>
                    </div>
                </div>

                <!-- 3. Yang Menerima (Penanggung Jawab Lokasi Baru) -->
                <div class="flex flex-col justify-between min-h-[140px] p-2 border border-slate-200 rounded-xl bg-slate-50/40">
                    <p class="font-bold text-slate-600 text-[11px]">Yang Menerima,</p>
                    <div class="py-6">
                        <span class="text-[10px] text-slate-300 italic">(Tanda Tangan)</span>
                    </div>
                    <div>
                        <p class="font-bold text-slate-900 border-t border-slate-300 pt-1">
                            ( .................................... )
                        </p>
                        <p class="text-[10px] text-slate-400">PIC {{ $transfer->to_location }}</p>
                    </div>
                </div>

                <!-- 4. Mengetahui (Superintendent / Site Manager) -->
                <div class="flex flex-col justify-between min-h-[140px] p-2 border border-slate-200 rounded-xl bg-slate-50/40">
                    <p class="font-bold text-slate-600 text-[11px]">Mengetahui,</p>
                    <div class="py-6">
                        <span class="text-[10px] text-slate-300 italic">(Tanda Tangan)</span>
                    </div>
                    <div>
                        <p class="font-bold text-slate-900 border-t border-slate-300 pt-1">
                            ( .................................... )
                        </p>
                        <p class="text-[10px] text-slate-400">Superintendent QMA</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    @media print {
        header, nav, aside, footer, #openDrawerBtn, #userProfileContainer, .print\:hidden {
            display: none !important;
        }
        body {
            background: white !important;
            color: black !important;
            font-size: 11pt !important;
        }
        .max-w-4xl {
            max-width: 100% !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
    }
</style>
@endsection
