@extends('layouts.app')

@section('title', 'Review P2H Unit ' . $record->vehicle->code . ' - ' . $record->transaction_no)

@section('content')
<div class="w-full space-y-6">
    <!-- Top Action Bar -->
    <div class="flex items-center justify-between pb-1">
        <a href="{{ route('qma.p2h.index') }}" class="inline-flex items-center space-x-1.5 text-xs font-bold text-slate-600 hover:text-[#1b5e20] transition bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-sm">
            <i class="fa-solid fa-arrow-left text-[11px]"></i>
            <span>Kembali ke P2H Unit Support</span>
        </a>

        <!-- Badge Status Validasi -->
        <div>
            @if($record->isPending())
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse mr-2"></span>
                    Menunggu Approval QMA
                </span>
            @elseif($record->isApproved())
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-300">
                    <i class="fa-solid fa-check mr-1.5 text-emerald-600"></i> Disetujui (Approved)
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-800 border border-rose-300">
                    <i class="fa-solid fa-xmark mr-1.5 text-rose-600"></i> Ditolak (Rejected)
                </span>
            @endif
        </div>
    </div>

    <!-- Header Transaksi & Metadata -->
    <div class="bg-gradient-to-r from-slate-800 to-slate-900 text-white p-6 rounded-2xl shadow-sm border border-slate-700">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-700 pb-4 mb-4">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">LEMBAR PEMERIKSAAN PRA-HARIAN (P2H)</span>
                <h1 class="text-2xl font-black text-white mt-0.5">{{ $record->transaction_no }}</h1>
            </div>
            <div class="text-left sm:text-right text-xs text-slate-300">
                <span class="text-slate-400">Waktu Pemeriksaan:</span>
                <span class="font-bold text-white block text-sm">{{ $record->inspected_at->format('d F Y, H:i') }} WIB</span>
            </div>
        </div>

        <!-- 3 Kolom Info: Unit, Operator, Ringkasan HM -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
            <!-- Kolom 1: Unit Support -->
            <div class="bg-slate-800/80 p-3.5 rounded-xl border border-slate-700/80 space-y-1.5">
                <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider block">UNIT SUPPORT</span>
                <p class="text-base font-black text-white leading-tight">{{ $record->vehicle->code }}</p>
                <p class="text-slate-300 font-medium">{{ $record->vehicle->brand_type }}</p>
                <div class="pt-1 text-[11px] text-slate-400 border-t border-slate-700/60 flex items-center justify-between">
                    <span>Kategori:</span>
                    <span class="text-slate-200 font-semibold">{{ $record->vehicle->unit_category ?? ($record->template->name ?? '-') }}</span>
                </div>
                <div class="text-[11px] text-slate-400 flex items-center justify-between">
                    <span>Lokasi:</span>
                    <span class="text-slate-200 font-semibold">{{ $record->vehicle->location ?? '-' }}</span>
                </div>
            </div>

            <!-- Kolom 2: Operator -->
            <div class="bg-slate-800/80 p-3.5 rounded-xl border border-slate-700/80 space-y-1.5">
                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">OPERATOR (HSE)</span>
                <p class="text-base font-black text-white leading-tight">{{ $record->employee->name ?? '-' }}</p>
                <p class="text-slate-300 font-medium">NIK: {{ $record->employee->nik ?? '-' }}</p>
                <div class="pt-1 text-[11px] text-slate-400 border-t border-slate-700/60 flex items-center justify-between">
                    <span>Divisi:</span>
                    <span class="text-slate-200 font-semibold">{{ $record->employee->division ?? '-' }}</span>
                </div>
                <div class="text-[11px] text-slate-400 flex items-center justify-between">
                    <span>SIMPER:</span>
                    <span class="text-emerald-400 font-semibold">Aktif (s.d {{ $record->employee->simper_expired_at ? $record->employee->simper_expired_at->format('d/m/Y') : '-' }})</span>
                </div>
            </div>

            <!-- Kolom 3: Operasional Shift & HM -->
            <div class="bg-slate-800/80 p-3.5 rounded-xl border border-slate-700/80 space-y-1.5">
                <span class="text-[10px] font-bold text-blue-400 uppercase tracking-wider block">OPERASIONAL SHIFT</span>
                <p class="text-base font-black text-white leading-tight">{{ $record->shift }}</p>
                <div class="pt-1 text-[11px] text-slate-400 border-t border-slate-700/60 flex items-center justify-between">
                    <span>Hour Meter (HM):</span>
                    <span class="text-emerald-300 font-extrabold text-sm">{{ number_format($record->start_hm, 1, ',', '.') }} HM</span>
                </div>
                <div class="text-[11px] text-slate-400 flex items-center justify-between">
                    <span>Kondisi Fisik:</span>
                    @if($record->hasDefects())
                        <span class="text-rose-400 font-bold">{{ $record->defect_count }} Item Rusak</span>
                    @else
                        <span class="text-emerald-400 font-bold">Semua Normal</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Catatan Umum Operator (Jika ada) -->
    @if($record->general_notes)
        <div class="bg-amber-50/60 border border-amber-200 rounded-2xl p-4 text-xs space-y-1 shadow-sm">
            <span class="font-bold text-amber-900 block flex items-center space-x-1.5">
                <i class="fa-solid fa-comment-dots text-amber-600"></i>
                <span>Catatan Umum dari Operator:</span>
            </span>
            <p class="text-slate-700 leading-relaxed italic">"{{ $record->general_notes }}"</p>
        </div>
    @endif

    <!-- Panel Keputusan Approval Admin QMA -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
        <h3 class="font-bold text-slate-900 text-sm flex items-center space-x-2 border-b border-slate-100 pb-3">
            <i class="fa-solid fa-stamp text-[#1b5e20]"></i>
            <span>Verifikasi & Keputusan Approval Admin QMA</span>
        </h3>

        @if($record->isPending())
            <!-- Form Aksi Approval / Reject -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Form Approve -->
                <form action="{{ route('qma.p2h.approve', $record->id) }}" method="POST" class="bg-emerald-50/50 border border-emerald-200 p-4 rounded-xl space-y-3">
                    @csrf
                    <div>
                        <span class="font-bold text-emerald-900 text-xs block mb-1">Setujui Hasil Pemeriksaan</span>
                        <input type="text" name="validation_notes" placeholder="Catatan persetujuan (opsional)..." class="w-full text-xs px-3 py-2 rounded-xl border border-emerald-300 focus:border-emerald-600 bg-white">
                    </div>
                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin MENYETUJUI hasil P2H untuk unit ini?')" class="w-full bg-[#1b5e20] hover:bg-[#144918] text-white font-bold py-2 px-4 rounded-xl text-xs shadow-sm transition flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Setujui P2H (Approve)</span>
                    </button>
                </form>

                <!-- Form Reject -->
                <form action="{{ route('qma.p2h.reject', $record->id) }}" method="POST" class="bg-rose-50/50 border border-rose-200 p-4 rounded-xl space-y-3">
                    @csrf
                    <div>
                        <span class="font-bold text-rose-900 text-xs block mb-1">Tolak Hasil Pemeriksaan <span class="text-rose-600">*</span></span>
                        <input type="text" name="validation_notes" required placeholder="Tuliskan alasan penolakan / instruksi perbaikan..." class="w-full text-xs px-3 py-2 rounded-xl border border-rose-300 focus:border-rose-600 bg-white">
                    </div>
                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin MENOLAK hasil P2H untuk unit ini?')" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded-xl text-xs shadow-sm transition flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Tolak P2H (Reject)</span>
                    </button>
                </form>
            </div>
        @else
            <!-- Info Hasil Validasi Sebelumnya -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                <div>
                    <span class="text-slate-400 block">Diverifikasi Oleh:</span>
                    <span class="font-bold text-slate-800 text-sm">{{ $record->validator->name ?? 'Admin QMA' }}</span>
                    <span class="text-slate-500 block text-[11px]">Pada {{ $record->validated_at ? $record->validated_at->format('d/m/Y H:i') . ' WIB' : '-' }}</span>
                </div>
                <div class="sm:text-right">
                    <span class="text-slate-400 block">Catatan Verifikator:</span>
                    <span class="font-medium text-slate-700">{{ $record->validation_notes ?? '-' }}</span>
                </div>
            </div>
        @endif
    </div>

    <!-- Detail Hasil Cek Per Section (Checklist Butir Soal) -->
    <div class="space-y-4">
        <h3 class="font-extrabold text-slate-900 text-base px-1">Rincian Butir Pemeriksaan Pra-Harian</h3>

        @foreach($groupedItems as $sectionName => $itemsInSection)
            <div class="bg-white rounded-none sm:rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-[#1b5e20]"></span>
                        <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wide">{{ $sectionName }}</h4>
                    </div>
                    <span class="text-[10px] font-semibold text-slate-500 bg-white border border-slate-200 px-2 py-0.5 rounded-full">
                        {{ count($itemsInSection) }} butir
                    </span>
                </div>

                <div class="divide-y divide-slate-100 p-2 sm:p-4 text-xs">
                    @foreach($itemsInSection as $item)
                        <div class="p-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 hover:bg-slate-50/60 rounded-xl transition">
                            <div class="flex items-start space-x-2 flex-1 pr-2">
                                <span class="font-bold text-slate-400">{{ $loop->iteration }}.</span>
                                <div>
                                    <span class="font-semibold text-slate-800 leading-snug">{{ $item->title }}</span>
                                    @if($item->notes)
                                        <p class="text-[11px] text-rose-600 font-medium mt-0.5 flex items-center space-x-1">
                                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i>
                                            <span>Catatan: {{ $item->notes }}</span>
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex-shrink-0">
                                @if(in_array($item->question_type, ['choice', 'condition', 'Multiple choice: only one answer', 'Matrix']) || !empty($item->condition_status))
                                    @if($item->condition_status === 'rusak')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            <i class="fa-solid fa-triangle-exclamation mr-1 text-rose-600"></i> Rusak
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="fa-solid fa-check mr-1 text-emerald-600"></i> Baik
                                        </span>
                                    @endif
                                @else
                                    <span class="font-semibold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg text-xs">
                                        {{ $item->answer_value ?? '-' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
