@extends('layouts.app')

@section('title', 'Template P2H ' . $template->name . ' - Admin QMA')

@section('content')
<div class="w-full space-y-6">
    <!-- Header Judul & Tombol Aksi (Format Konsisten Sesuai Referensi Gambar) -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-2 mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                    MANAJEMEN TEMPLATE P2H
                </span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                {{ $template->name }}
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                {{ $template->description ?: "Kode Checksheet: {$template->code} • Template form inspeksi resmi kategori {$template->name}" }}
            </p>
        </div>

        <!-- Tombol Aksi Kanan (Kembali & Tambah Section Baru) -->
        <div class="grid grid-cols-2 gap-2.5 w-full sm:flex sm:items-center sm:gap-3 sm:w-auto">
            <a href="{{ route('qma.p2h.templates') }}" 
               class="w-full sm:w-auto flex items-center justify-center space-x-2 text-xs font-bold text-slate-700 hover:text-slate-900 bg-white border border-gray-200 rounded-xl px-4 py-2.5 shadow-sm transition hover:bg-gray-50 cursor-pointer">
                <i class="fa-solid fa-arrow-left text-xs text-slate-500"></i>
                <span>Kembali</span>
            </a>
            <button type="button" 
                    onclick="openAddSectionModal()" 
                    class="w-full sm:w-auto flex items-center justify-center space-x-2 bg-[#1b5e20] hover:bg-[#144919] text-white px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer"
                    title="Tambah Bagian/Section Baru">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah Section Baru</span>
            </button>
        </div>
    </div>

    <!-- Filter / Pencarian Pertanyaan Cepat -->
    <div class="relative w-full max-w-sm sm:max-w-md">
        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-magnifying-glass text-xs"></i>
        </span>
        <input type="text" 
               id="questionSearchInput"
               placeholder="Cari butir pertanyaan atau section..." 
               class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 shadow-sm transition">
    </div>

    <!-- Rincian Section & Butir Pertanyaan (Tampilan Tab Horizontal & Panel Konten di Bawahnya) -->
    @if(!empty($groupedItems) && count($groupedItems) > 0)
        <div class="space-y-4">
            <!-- 1. Baris Section Horizontal (Dari Kiri ke Kanan) -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin" id="sectionsContainer">
                @foreach($groupedItems as $sectionName => $itemsInSection)
                    @php
                        $secKey = 'sec_' . md5($sectionName);
                        $isFirst = $loop->first;
                    @endphp
                    <button type="button" 
                            onclick="selectSection('{{ $secKey }}')"
                            id="tab-{{ $secKey }}"
                            data-section-key="{{ $secKey }}"
                            data-section-name="{{ strtolower($sectionName) }}"
                            data-raw-section="{{ $sectionName }}"
                            data-count="{{ count($itemsInSection) }}"
                            class="section-tab group inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer shrink-0 {{ $isFirst ? 'bg-[#1b5e20] text-white shadow-xs border border-[#1b5e20]' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                        <span class="tab-title">{{ $sectionName }}</span>
                    </button>
                @endforeach

                <!-- Tombol Tambah Section Baru di Akhir Baris Horizontal -->
                <button type="button" 
                        onclick="openAddSectionModal()" 
                        class="inline-flex items-center space-x-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold bg-white text-emerald-800 border-2 border-dashed border-emerald-300 hover:border-[#1b5e20] hover:bg-emerald-50 transition whitespace-nowrap cursor-pointer shrink-0"
                        title="Tambah Bagian/Section Baru">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Tambah Section</span>
                </button>

                <!-- Tombol Atur Urutan Posisi Section -->
                @if(count($groupedItems) > 1)
                    <button type="button" 
                            onclick="openReorderSectionsModal()" 
                            class="inline-flex items-center space-x-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition whitespace-nowrap cursor-pointer shrink-0"
                            title="Atur Urutan Posisi Section">
                        <i class="fa-solid fa-arrow-down-up-across-line text-xs text-slate-400"></i>
                        <span>Atur Urutan Section</span>
                    </button>
                @endif
            </div>

            <!-- 2. Panel Konten List Pertanyaan & Fitur Tambah Pertanyaan di Bawah Baris Section -->
            <div id="sectionPanelsContainer" class="space-y-4">
                @foreach($groupedItems as $sectionName => $itemsInSection)
                    @php
                        $secKey = 'sec_' . md5($sectionName);
                        $isFirst = $loop->first;
                    @endphp
                    <div class="section-card section-content bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-2xs overflow-hidden transition-all duration-200 {{ $isFirst ? '' : 'hidden' }}"
                         id="content-{{ $secKey }}"
                         data-section-key="{{ $secKey }}"
                         data-raw-section="{{ $sectionName }}"
                         data-section-name="{{ strtolower($sectionName) }}">
                        
                        <!-- Header Panel Section Aktif (Informasi Section & Fitur Tambah Pertanyaan) -->
                        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between gap-3 bg-white">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                    <h3 class="font-bold text-slate-900 text-base sm:text-lg tracking-tight truncate section-title">
                                        {{ $sectionName }}
                                    </h3>
                                    <span class="section-count-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-[#1b5e20] border border-emerald-200/80"
                                          data-total="{{ count($itemsInSection) }}">
                                        {{ count($itemsInSection) }} Pertanyaan
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5 truncate hidden sm:block">
                                    Daftar butir pertanyaan inspeksi pra-harian pada bagian ini.
                                </p>
                            </div>

                            <!-- Fitur Tambah Pertanyaan ke Section Ini (+ Tambah di Mobile, + Tambah Pertanyaan di Desktop) -->
                            <button type="button" 
                                    onclick="openAddItemModal('{{ addslashes($sectionName) }}')"
                                    class="bg-[#1b5e20] hover:bg-[#144919] text-white px-3 sm:px-4 py-2 rounded-xl text-xs font-bold inline-flex items-center space-x-1.5 transition shadow-sm cursor-pointer shrink-0"
                                    title="Tambah pertanyaan ke bagian {{ $sectionName }}">
                                <i class="fa-solid fa-plus text-xs"></i>
                                <span>Tambah<span class="hidden sm:inline"> Pertanyaan</span></span>
                            </button>
                        </div>

                        <!-- Tabel List Pertanyaan di Bawah Section -->
                        <div class="overflow-x-auto p-3 sm:p-5">
                            <table class="w-full text-left text-xs min-w-[480px]">
                                <thead>
                                    <tr class="border-b border-slate-200 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                        <th class="pb-3 px-2 w-10 text-center whitespace-nowrap">No</th>
                                        <th class="pb-3 px-3 min-w-[180px]">Pertanyaan / Butir Cek</th>
                                        <th class="pb-3 px-3 w-48 whitespace-nowrap">Tipe Respon</th>
                                        <th class="pb-3 px-2 w-28 text-right whitespace-nowrap">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 question-tbody">
                                    @forelse($itemsInSection as $item)
                                        @php
                                            $typeLabel = $item->question_type_label;
                                        @endphp
                                        <tr class="question-row hover:bg-slate-50/70 transition group"
                                            data-title="{{ strtolower($item->title) }}"
                                            data-seq="{{ $item->sequence }}"
                                            data-type="{{ strtolower($typeLabel) }}">
                                            <td class="py-3 px-2 font-bold text-slate-400 text-center align-middle whitespace-nowrap">
                                                {{ $item->sequence }}.
                                            </td>
                                            <td class="py-3 px-3 font-semibold text-slate-800 leading-snug align-middle question-title">
                                                {{ $item->title }}
                                            </td>
                                            <td class="py-3 px-3 align-middle whitespace-nowrap">
                                                @if(in_array($typeLabel, ['Multiple choice: only one answer', 'Matrix']))
                                                    <span class="inline-flex items-center whitespace-nowrap text-[11px] font-semibold bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-lg border border-emerald-200/80 leading-none">
                                                        {{ $typeLabel }}
                                                    </span>
                                                @elseif($typeLabel === 'Multiple choice: multiple answers allowed')
                                                    <span class="inline-flex items-center whitespace-nowrap text-[11px] font-semibold bg-teal-50 text-teal-700 px-2.5 py-1 rounded-lg border border-teal-200/80 leading-none">
                                                        {{ $typeLabel }}
                                                    </span>
                                                @elseif(in_array($typeLabel, ['Multiple Lines Text Box', 'Single Line Text Box']))
                                                    <span class="inline-flex items-center whitespace-nowrap text-[11px] font-semibold bg-amber-50 text-amber-700 px-2.5 py-1 rounded-lg border border-amber-200/80 leading-none">
                                                        {{ $typeLabel }}
                                                    </span>
                                                @elseif($typeLabel === 'Numerical Value')
                                                    <span class="inline-flex items-center whitespace-nowrap text-[11px] font-semibold bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg border border-blue-200/80 leading-none">
                                                        {{ $typeLabel }}
                                                    </span>
                                                @elseif(in_array($typeLabel, ['Date', 'Datetime']))
                                                    <span class="inline-flex items-center whitespace-nowrap text-[11px] font-semibold bg-purple-50 text-purple-700 px-2.5 py-1 rounded-lg border border-purple-200/80 leading-none">
                                                        {{ $typeLabel }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center whitespace-nowrap text-[11px] font-semibold bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg border border-slate-200 leading-none">
                                                        {{ $typeLabel }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-2 text-right align-middle shrink-0 whitespace-nowrap">
                                                <div class="inline-flex items-center space-x-1 justify-end">
                                                    <!-- Tombol Edit Pertanyaan -->
                                                    <button type="button" 
                                                            onclick="openEditItemModal({{ $item->id }}, '{{ addslashes($item->title) }}', '{{ addslashes($item->section) }}', '{{ addslashes($item->question_type) }}', {{ $item->sequence }})" 
                                                            class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition cursor-pointer"
                                                            title="Edit Pertanyaan">
                                                        <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                                                        <span>Edit</span>
                                                    </button>

                                                    <!-- Tombol Hapus Pertanyaan -->
                                                    <button type="button" 
                                                            onclick="openDeleteItemModal({{ $item->id }}, '{{ addslashes($item->title) }}', {{ $item->sequence }})" 
                                                            class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition cursor-pointer"
                                                            title="Hapus Pertanyaan">
                                                        <i class="fa-regular fa-trash-can text-[10px]"></i>
                                                        <span>Hapus</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-12 px-4 text-center">
                                                <p class="text-xs font-bold text-slate-700">Belum ada butir pertanyaan pada bagian ini.</p>
                                                <p class="text-[11px] text-slate-400 mt-0.5">Tambahkan butir pemeriksaan baru menggunakan tombol di bawah.</p>
                                                <div class="mt-3">
                                                    <button type="button" 
                                                            onclick="openAddItemModal('{{ addslashes($sectionName) }}')"
                                                            class="bg-[#1b5e20] hover:bg-[#144919] text-white px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center space-x-1.5 transition shadow-sm cursor-pointer">
                                                        <span>+ Tambah Pertanyaan Pertama</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>
                @endforeach
            </div>
        </div>

        <!-- Pesan Tidak Ditemukan Global Saat Real-time Search -->
        <div id="noResultsGlobalMessage" class="hidden bg-white rounded-none sm:rounded-2xl border border-slate-200 p-12 text-center text-slate-500 shadow-xs">
            <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
            <p class="text-xs font-medium">Data "<span id="noResultsQueryText" class="font-bold text-slate-800"></span>" tidak ditemukan di tabel</p>
        </div>
    @else
        <!-- Keadaan Template Kosong -->
        <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 p-12 text-center text-slate-400 shadow-xs">
            <h4 class="text-base font-bold text-slate-800">Belum Ada Butir Pertanyaan</h4>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                Template checksheet ini belum memiliki butir pertanyaan. Tambahkan bagian/section dan butir pemeriksaan pertama untuk template ini.
            </p>
            <div class="mt-5">
                <button type="button" 
                        onclick="openAddSectionModal()" 
                        class="bg-[#1b5e20] hover:bg-[#144919] text-white px-4 py-2.5 rounded-xl text-xs font-bold inline-flex items-center space-x-2 transition shadow-sm cursor-pointer">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Tambah Section Pertama</span>
                </button>
            </div>
        </div>
    @endif
</div>

<!-- ========================================================================= -->
<!-- MODAL: TAMBAH SECTION & DAFTAR PERTANYAAN (BATCH)                         -->
<!-- ========================================================================= -->
<div id="addSectionModal" 
     data-backdrop="static"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 sm:p-8 border border-slate-100 my-8 max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 shrink-0">
            <div>
                <h3 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 text-[#1b5e20] flex items-center justify-center text-sm">
                        <i class="fa-solid fa-folder-plus"></i>
                    </span>
                    <span>Tambah Section & Daftar Pertanyaan</span>
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Template: <span class="font-semibold text-slate-700">{{ $template->name }}</span>
                    &bull; Buat nama section dan masukkan butir pertanyaan beserta tipe responnya.
                </p>
            </div>
            <button type="button" onclick="closeAddSectionModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form action="{{ route('qma.p2h.templates.items.store', $template->id) }}" method="POST" class="flex flex-col flex-1 overflow-hidden pt-4 space-y-4">
            @csrf

            <!-- Area Scrollable Form -->
            <div class="overflow-y-auto pr-1 sm:pr-2 space-y-5 flex-1 custom-scrollbar">
                <!-- Input Nama Section -->
                <div class="bg-emerald-50/50 border border-emerald-200/80 rounded-2xl p-4">
                    <label class="block text-xs font-bold text-slate-800 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-folder text-emerald-600 text-xs"></i>
                        <span>Nama Bagian / Section Baru <span class="text-rose-500">*</span></span>
                    </label>
                    <input type="text" 
                           name="section" 
                           id="addSectionNameInput"
                           list="sectionSuggestions" 
                           required 
                           placeholder="Contoh: Pemeriksaan Keliling (Walk Around Inspection), Ruang Mesin..." 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 font-medium">
                    <p class="text-[10px] text-slate-500 mt-1.5">
                        Masukkan nama bagian baru atau pilih dari section yang sudah pernah dibuat sebelumnya.
                    </p>
                </div>

                <!-- Pilihan Posisi Penempatan Section (Sebelum / Sesudah Section Tertentu) -->
                <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-4 space-y-3">
                    <label class="block text-xs font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-arrows-up-down text-emerald-600 text-xs"></i>
                        <span>Posisi Penempatan Section</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Letakkan Section Ini Pada:</label>
                            <select name="position_type" 
                                    id="addSectionPositionType"
                                    onchange="toggleTargetSectionDropdown()"
                                    class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 font-medium">
                                <option value="end" selected>Di Paling Akhir (Default)</option>
                                <option value="start">Di Paling Awal (Urutan Pertama)</option>
                                <option value="after">Setelah Section Tertentu...</option>
                                <option value="before">Sebelum Section Tertentu...</option>
                            </select>
                        </div>
                        <div id="targetSectionWrapper" class="hidden">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Pilih Section Acuan:</label>
                            <select name="target_section" 
                                    id="addSectionTargetSection"
                                    class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 font-medium">
                                @foreach(array_keys($groupedItems) as $sName)
                                    <option value="{{ $sName }}">{{ $sName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400">
                        Tentukan posisi apakah section ini diletakkan sebelum atau sesudah section yang sudah ada.
                    </p>
                </div>

                <!-- Bagian Daftar Pertanyaan di Bawah Section -->
                <div>
                    <div class="flex items-center justify-between mb-2.5">
                        <div>
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fa-solid fa-list-check text-slate-400 text-xs"></i>
                                <span>Daftar Pertanyaan Section</span>
                            </h4>
                            <p class="text-[10px] text-slate-400">Tentukan butir pertanyaan dan pilih tipe respon untuk masing-masing butir.</p>
                        </div>
                        <span class="hidden sm:inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200 whitespace-nowrap">
                            <span id="sectionQuestionsCount">1</span>&nbsp;Butir Pertanyaan
                        </span>
                    </div>

                    <!-- Container Butir Pertanyaan Dinamis -->
                    <div id="sectionQuestionsList" class="space-y-3.5">
                        <!-- Card Pertanyaan #1 (Default) -->
                        <div class="section-q-card bg-slate-50/80 rounded-2xl p-4 border border-slate-200/80 space-y-3 relative">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-700 q-card-title flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-full bg-[#1b5e20] text-white flex items-center justify-center text-[10px] font-bold q-num-badge">1</span>
                                    <span>Butir Pertanyaan #1</span>
                                </span>
                                <button type="button" 
                                        onclick="removeQuestionFromSection(this)" 
                                        class="btn-remove-q hidden text-slate-400 hover:text-rose-600 transition p-1 text-xs cursor-pointer" 
                                        title="Hapus baris pertanyaan ini">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>

                            <!-- Teks Pertanyaan -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">
                                    Teks Pertanyaan / Butir Cek <span class="text-rose-500">*</span>
                                </label>
                                <textarea name="questions[0][title]" 
                                          rows="2" 
                                          required 
                                          placeholder="Masukkan teks pertanyaan atau item pemeriksaan..." 
                                          class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10"></textarea>
                            </div>

                            <!-- Tipe Respon Pertanyaan -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">
                                    Tipe Respon Pertanyaan <span class="text-rose-500">*</span>
                                </label>
                                <select name="questions[0][question_type]" 
                                        required 
                                        class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10">
                                    <option value="Multiple choice: only one answer" selected>Multiple choice: only one answer</option>
                                    <option value="Multiple choice: multiple answers allowed">Multiple choice: multiple answers allowed</option>
                                    <option value="Single Line Text Box">Single Line Text Box</option>
                                    <option value="Multiple Lines Text Box">Multiple Lines Text Box</option>
                                    <option value="Numerical Value">Numerical Value</option>
                                    <option value="Date">Date</option>
                                    <option value="Datetime">Datetime</option>
                                    <option value="Matrix">Matrix</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Tambah Baris Pertanyaan Lain -->
                    <div class="mt-3">
                        <button type="button" 
                                onclick="addQuestionToSection()" 
                                class="w-full py-2.5 rounded-xl border-2 border-dashed border-emerald-300 hover:border-[#1b5e20] text-[#1b5e20] bg-emerald-50/50 hover:bg-emerald-50 text-xs font-bold flex items-center justify-center space-x-2 transition cursor-pointer">
                            <i class="fa-solid fa-plus text-xs"></i>
                            <span>Tambah Butir Pertanyaan Lain di Section Ini</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Actions Footer -->
            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 shrink-0">
                <button type="button" 
                        onclick="closeAddSectionModal()" 
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1b5e20] hover:bg-[#144919] transition shadow-sm flex items-center space-x-1.5">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    <span>Simpan Section & Pertanyaan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: ATUR URUTAN POSISI SECTION (REORDER)                               -->
<!-- ========================================================================= -->
<div id="reorderSectionsModal" 
     data-backdrop="static"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-8 border border-slate-100 my-8 max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 shrink-0">
            <div>
                <h3 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-arrows-up-down"></i>
                    </span>
                    <span>Atur Urutan Posisi Section</span>
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Gunakan tombol panah untuk memindahkan posisi section sebelum atau sesudah section lainnya.
                </p>
            </div>
            <button type="button" onclick="closeReorderSectionsModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Form Reorder -->
        <form action="{{ route('qma.p2h.templates.sections.reorder', $template->id) }}" method="POST" class="flex flex-col flex-1 overflow-hidden pt-4 space-y-4">
            @csrf

            <!-- Scrollable List of Sections -->
            <div class="overflow-y-auto pr-1 space-y-2 flex-1 custom-scrollbar" id="reorderSectionsList">
                @foreach(array_keys($groupedItems) as $index => $secName)
                    <div class="reorder-section-item flex items-center justify-between p-3 rounded-2xl bg-slate-50 border border-slate-200 transition select-none hover:bg-slate-100/80"
                         data-section="{{ $secName }}">
                        <div class="flex items-center space-x-3 min-w-0">
                            <span class="w-6 h-6 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold flex items-center justify-center item-order-num shrink-0">
                                {{ $index + 1 }}
                            </span>
                            <span class="text-xs font-bold text-slate-800 truncate">
                                {{ $secName }}
                            </span>
                            <span class="text-[10px] text-slate-400 hidden sm:inline">
                                ({{ count($groupedItems[$secName]) }} Pertanyaan)
                            </span>
                        </div>

                        <!-- Tombol Geser Atas / Bawah -->
                        <div class="flex items-center space-x-1.5 shrink-0">
                            <button type="button" 
                                    onclick="moveSectionItemUp(this)" 
                                    class="w-7 h-7 rounded-xl bg-white hover:bg-[#1b5e20] text-slate-600 hover:text-white border border-slate-200 hover:border-[#1b5e20] transition flex items-center justify-center text-xs shadow-2xs cursor-pointer"
                                    title="Pindahkan Lebih Awal (Sebelumnya)">
                                <i class="fa-solid fa-arrow-up"></i>
                            </button>
                            <button type="button" 
                                    onclick="moveSectionItemDown(this)" 
                                    class="w-7 h-7 rounded-xl bg-white hover:bg-[#1b5e20] text-slate-600 hover:text-white border border-slate-200 hover:border-[#1b5e20] transition flex items-center justify-center text-xs shadow-2xs cursor-pointer"
                                    title="Pindahkan Lebih Akhir (Sesudahnya)">
                                <i class="fa-solid fa-arrow-down"></i>
                            </button>
                        </div>

                        <!-- Hidden Input untuk form submit -->
                        <input type="hidden" name="sections[]" value="{{ $secName }}" class="reorder-input">
                    </div>
                @endforeach
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 shrink-0">
                <button type="button" 
                        onclick="closeReorderSectionsModal()" 
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1b5e20] hover:bg-[#144919] transition shadow-sm flex items-center space-x-1.5">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    <span>Simpan Urutan Section</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT BUTIR PERTANYAAN                                              -->
<!-- ========================================================================= -->
<div id="editItemModal" 
     data-backdrop="static"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-8 border border-slate-100">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                    Edit Butir Pertanyaan
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Template: <span class="font-semibold text-slate-700">{{ $template->name }}</span></p>
            </div>
            <button type="button" onclick="closeEditItemModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="editItemForm" method="POST" action="" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- Bagian / Section -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Bagian / Section <span class="text-rose-500">*</span>
                </label>
                <input type="text" 
                       id="editItemSection" 
                       name="section" 
                       list="sectionSuggestions" 
                       required 
                       placeholder="Contoh: Pemeriksaan Keliling, Ruang Mesin..." 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10">
                <p class="text-[10px] text-slate-400 mt-1">Pilih kategori section yang ada atau ketik nama baru.</p>
            </div>

            <!-- Teks Pertanyaan -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Teks Pertanyaan / Butir Cek <span class="text-rose-500">*</span>
                </label>
                <textarea id="editItemTitle" 
                          name="title" 
                          rows="3" 
                          required 
                          placeholder="Masukkan pertanyaan butir pemeriksaan pra-operasi..." 
                          class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10"></textarea>
            </div>

            <!-- Tipe Respon Pertanyaan -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Tipe Respon Pertanyaan <span class="text-rose-500">*</span>
                </label>
                <select id="editItemType" 
                        name="question_type" 
                        required 
                        class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10">
                    <option value="Multiple choice: only one answer">Multiple choice: only one answer</option>
                    <option value="Multiple choice: multiple answers allowed">Multiple choice: multiple answers allowed</option>
                    <option value="Single Line Text Box">Single Line Text Box</option>
                    <option value="Multiple Lines Text Box">Multiple Lines Text Box</option>
                    <option value="Numerical Value">Numerical Value</option>
                    <option value="Date">Date</option>
                    <option value="Datetime">Datetime</option>
                    <option value="Matrix">Matrix</option>
                </select>
            </div>

            <!-- Tombol Aksi Modal -->
            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" 
                        onclick="closeEditItemModal()" 
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1b5e20] hover:bg-[#144919] transition shadow-sm flex items-center space-x-1.5">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: TAMBAH BUTIR PERTANYAAN TUNGGAL                                     -->
<!-- ========================================================================= -->
<div id="addItemModal" 
     data-backdrop="static"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-8 border border-slate-100">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                    Tambah Butir Pertanyaan
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Template: <span class="font-semibold text-slate-700">{{ $template->name }}</span>
                    <span id="addItemSectionBadge" class="hidden font-medium text-slate-600">
                        &bull; Bagian: <span id="addItemSectionBadgeText" class="font-bold text-[#1b5e20] bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200"></span>
                    </span>
                </p>
            </div>
            <button type="button" onclick="closeAddItemModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('qma.p2h.templates.items.store', $template->id) }}" method="POST" class="space-y-4">
            @csrf

            <!-- Bagian / Section (Disembunyikan jika menambah langsung di dalam section) -->
            <div id="addItemSectionContainer">
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Bagian / Section <span class="text-rose-500">*</span>
                </label>
                <input type="text" 
                       name="section" 
                       id="addItemSection" 
                       list="sectionSuggestions" 
                       required 
                       placeholder="Contoh: Pemeriksaan Keliling, Ruang Mesin..." 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10">
                <p class="text-[10px] text-slate-400 mt-1">Pilih kategori section yang ada atau ketik nama baru.</p>
            </div>

            <!-- Teks Pertanyaan -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Teks Pertanyaan / Butir Cek <span class="text-rose-500">*</span>
                </label>
                <textarea name="title" 
                          rows="3" 
                          required 
                          placeholder="Masukkan pertanyaan butir pemeriksaan pra-operasi..." 
                          class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10"></textarea>
            </div>

            <!-- Tipe Respon Pertanyaan -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Tipe Respon Pertanyaan <span class="text-rose-500">*</span>
                </label>
                <select name="question_type" 
                        required 
                        class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10">
                    <option value="Multiple choice: only one answer" selected>Multiple choice: only one answer</option>
                    <option value="Multiple choice: multiple answers allowed">Multiple choice: multiple answers allowed</option>
                    <option value="Single Line Text Box">Single Line Text Box</option>
                    <option value="Multiple Lines Text Box">Multiple Lines Text Box</option>
                    <option value="Numerical Value">Numerical Value</option>
                    <option value="Date">Date</option>
                    <option value="Datetime">Datetime</option>
                    <option value="Matrix">Matrix</option>
                </select>
            </div>


            <!-- Tombol Aksi Modal -->
            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" 
                        onclick="closeAddItemModal()" 
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1b5e20] hover:bg-[#144919] transition shadow-sm flex items-center space-x-1.5">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Tambah Pertanyaan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: KONFIRMASI HAPUS PERTANYAAN                                        -->
<!-- ========================================================================= -->
<div id="deleteItemModal" 
     data-backdrop="static"
     class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 sm:p-8 border border-slate-100 text-center">
        <div class="w-14 h-14 bg-rose-50 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
            <i class="fa-regular fa-trash-can"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-900 mb-1">Hapus Butir Pertanyaan?</h3>
        <p class="text-xs text-slate-500 mb-4">
            Apakah Anda yakin ingin menghapus pertanyaan #<span id="deleteItemSeq" class="font-bold text-slate-700"></span> "<span id="deleteItemTitle" class="font-semibold text-slate-700"></span>"?
        </p>

        <form id="deleteItemForm" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="flex items-center justify-center space-x-2">
                <button type="button" 
                        onclick="closeDeleteItemModal()" 
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 transition shadow-sm">
                    Ya, Hapus
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Datalist Section Suggestions -->
<datalist id="sectionSuggestions">
    @foreach($availableSections as $sec)
        <option value="{{ $sec }}">
    @endforeach
</datalist>

<!-- ========================================================================= -->
<!-- JAVASCRIPT: LOGIKA MODAL & FILTER PERTANYAAN                              -->
<!-- ========================================================================= -->
<script>
    const baseItemUpdateUrl = "{{ url('/qma/p2h-templates/' . $template->id . '/items') }}";

    function openAddSectionModal() {
        const modal = document.getElementById('addSectionModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        toggleTargetSectionDropdown();
        const secInput = document.getElementById('addSectionNameInput');
        if (secInput) secInput.focus();
    }

    function closeAddSectionModal() {
        const modal = document.getElementById('addSectionModal');
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function toggleTargetSectionDropdown() {
        const posType = document.getElementById('addSectionPositionType');
        const targetWrapper = document.getElementById('targetSectionWrapper');
        if (!posType || !targetWrapper) return;
        if (posType.value === 'after' || posType.value === 'before') {
            targetWrapper.classList.remove('hidden');
        } else {
            targetWrapper.classList.add('hidden');
        }
    }

    function openReorderSectionsModal() {
        const modal = document.getElementById('reorderSectionsModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeReorderSectionsModal() {
        const modal = document.getElementById('reorderSectionsModal');
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function updateReorderNumbers() {
        const list = document.getElementById('reorderSectionsList');
        if (!list) return;
        const items = list.querySelectorAll('.reorder-section-item');
        items.forEach((item, idx) => {
            const numSpan = item.querySelector('.item-order-num');
            if (numSpan) numSpan.textContent = idx + 1;
        });
    }

    function moveSectionItemUp(btn) {
        const item = btn.closest('.reorder-section-item');
        if (!item) return;
        const prev = item.previousElementSibling;
        if (prev) {
            item.parentNode.insertBefore(item, prev);
            updateReorderNumbers();
        }
    }

    function moveSectionItemDown(btn) {
        const item = btn.closest('.reorder-section-item');
        if (!item) return;
        const next = item.nextElementSibling;
        if (next) {
            item.parentNode.insertBefore(next, item);
            updateReorderNumbers();
        }
    }


    function updateSectionQuestionNumbers() {
        const list = document.getElementById('sectionQuestionsList');
        if (!list) return;
        const cards = list.querySelectorAll('.section-q-card');
        const countBadge = document.getElementById('sectionQuestionsCount');
        if (countBadge) countBadge.textContent = cards.length;

        cards.forEach((card, idx) => {
            const badge = card.querySelector('.q-num-badge');
            const titleSpan = card.querySelector('.q-card-title span:last-child');
            const removeBtn = card.querySelector('.btn-remove-q');
            
            if (badge) badge.textContent = (idx + 1);
            if (titleSpan) titleSpan.textContent = `Butir Pertanyaan #${idx + 1}`;
            
            if (removeBtn) {
                if (cards.length <= 1) {
                    removeBtn.classList.add('hidden');
                } else {
                    removeBtn.classList.remove('hidden');
                }
            }

            const textarea = card.querySelector('textarea');
            const select = card.querySelector('select');

            if (textarea) textarea.name = `questions[${idx}][title]`;
            if (select) select.name = `questions[${idx}][question_type]`;
        });
    }

    function addQuestionToSection() {
        const list = document.getElementById('sectionQuestionsList');
        if (!list) return;

        const cards = list.querySelectorAll('.section-q-card');
        const newIdx = cards.length;

        const newCard = document.createElement('div');
        newCard.className = 'section-q-card bg-slate-50/80 rounded-2xl p-4 border border-slate-200/80 space-y-3 relative';
        newCard.innerHTML = `
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 q-card-title flex items-center gap-1.5">
                    <span class="w-5 h-5 rounded-full bg-[#1b5e20] text-white flex items-center justify-center text-[10px] font-bold q-num-badge">${newIdx + 1}</span>
                    <span>Butir Pertanyaan #${newIdx + 1}</span>
                </span>
                <button type="button" 
                        onclick="removeQuestionFromSection(this)" 
                        class="btn-remove-q text-slate-400 hover:text-rose-600 transition p-1 text-xs cursor-pointer" 
                        title="Hapus baris pertanyaan ini">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">
                    Teks Pertanyaan / Butir Cek <span class="text-rose-500">*</span>
                </label>
                <textarea name="questions[${newIdx}][title]" 
                          rows="2" 
                          required 
                          placeholder="Masukkan teks pertanyaan atau item pemeriksaan..." 
                          class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10"></textarea>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">
                    Tipe Respon Pertanyaan <span class="text-rose-500">*</span>
                </label>
                <select name="questions[${newIdx}][question_type]" 
                        required 
                        class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10">
                    <option value="Multiple choice: only one answer" selected>Multiple choice: only one answer</option>
                    <option value="Multiple choice: multiple answers allowed">Multiple choice: multiple answers allowed</option>
                    <option value="Single Line Text Box">Single Line Text Box</option>
                    <option value="Multiple Lines Text Box">Multiple Lines Text Box</option>
                    <option value="Numerical Value">Numerical Value</option>
                    <option value="Date">Date</option>
                    <option value="Datetime">Datetime</option>
                    <option value="Matrix">Matrix</option>
                </select>
            </div>
        `;

        list.appendChild(newCard);
        updateSectionQuestionNumbers();

        const newTextarea = newCard.querySelector('textarea');
        if (newTextarea) newTextarea.focus();
    }

    function removeQuestionFromSection(btn) {
        const card = btn.closest('.section-q-card');
        const list = document.getElementById('sectionQuestionsList');
        if (card && list && list.querySelectorAll('.section-q-card').length > 1) {
            card.remove();
            updateSectionQuestionNumbers();
        }
    }

    function normalizeQuestionType(rawType) {
        if (!rawType) return 'Multiple choice: only one answer';
        const lower = rawType.toLowerCase().trim();
        if (lower === 'multiple lines text box' || lower === 'textarea') return 'Multiple Lines Text Box';
        if (lower === 'single line text box' || lower === 'text') return 'Single Line Text Box';
        if (lower === 'numerical value' || lower === 'number') return 'Numerical Value';
        if (lower === 'date') return 'Date';
        if (lower === 'datetime') return 'Datetime';
        if (lower === 'multiple choice: only one answer' || lower === 'choice' || lower === 'condition') return 'Multiple choice: only one answer';
        if (lower === 'multiple choice: multiple answers allowed' || lower === 'multiple_choice') return 'Multiple choice: multiple answers allowed';
        if (lower === 'matrix') return 'Matrix';
        return rawType;
    }

    function openEditItemModal(id, title, section, type, seq) {
        const form = document.getElementById('editItemForm');
        form.action = baseItemUpdateUrl + '/' + id;
        document.getElementById('editItemTitle').value = title;
        document.getElementById('editItemSection').value = section;
        document.getElementById('editItemType').value = normalizeQuestionType(type);

        document.getElementById('editItemModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeEditItemModal() {
        document.getElementById('editItemModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    let activeSectionKey = @json(!empty($groupedItems) ? 'sec_' . md5(array_key_first($groupedItems)) : '');

    function selectSection(secKey) {
        activeSectionKey = secKey;

        // 1. Update styling tabs horizontal
        const allTabs = document.querySelectorAll('.section-tab');
        allTabs.forEach(tab => {
            const key = tab.dataset.sectionKey;

            if (key === secKey) {
                tab.className = 'section-tab group inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer shrink-0 bg-[#1b5e20] text-white shadow-xs border border-[#1b5e20]';
            } else {
                tab.className = 'section-tab group inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer shrink-0 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300';
            }
        });

        // 2. Tampilkan panel konten section yang dipilih dan sembunyikan yang lain
        const allPanels = document.querySelectorAll('.section-content');
        allPanels.forEach(panel => {
            if (panel.id === 'content-' + secKey) {
                panel.classList.remove('hidden');
            } else {
                panel.classList.add('hidden');
            }
        });
    }

    function toggleSection(secKey) {
        selectSection(secKey);
    }

    function openAddItemModal(defaultSection = '', forceShowSectionInput = false) {
        const sectionInput = document.getElementById('addItemSection');
        const sectionContainer = document.getElementById('addItemSectionContainer');
        const sectionBadge = document.getElementById('addItemSectionBadge');
        const sectionBadgeText = document.getElementById('addItemSectionBadgeText');

        // Jika tidak dispesifikasikan defaultSection, gunakan active section saat ini
        let targetSection = defaultSection;
        if (!targetSection && activeSectionKey) {
            const activeTab = document.getElementById('tab-' + activeSectionKey);
            if (activeTab && activeTab.dataset.rawSection) {
                targetSection = activeTab.dataset.rawSection;
            }
        }

        if (sectionInput) {
            sectionInput.value = forceShowSectionInput ? '' : (targetSection || '');
        }

        if (targetSection && !forceShowSectionInput) {
            // Ketika menambahkan pertanyaan di dalam section: tidak perlu tampilkan input section lagi
            if (sectionContainer) sectionContainer.classList.add('hidden');
            if (sectionBadge) {
                sectionBadge.classList.remove('hidden');
                if (sectionBadgeText) sectionBadgeText.textContent = targetSection;
            }
        } else {
            // Mode tambah section baru atau template kosong: tampilkan input section
            if (sectionContainer) sectionContainer.classList.remove('hidden');
            if (sectionBadge) sectionBadge.classList.add('hidden');
        }

        document.getElementById('addItemModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeAddItemModal() {
        document.getElementById('addItemModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function openDeleteItemModal(id, title, seq) {
        const form = document.getElementById('deleteItemForm');
        form.action = baseItemUpdateUrl + '/' + id;
        document.getElementById('deleteItemSeq').textContent = seq;
        document.getElementById('deleteItemTitle').textContent = title;

        document.getElementById('deleteItemModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeDeleteItemModal() {
        document.getElementById('deleteItemModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function applySearchFilter(query) {
        const tabs = document.querySelectorAll('.section-tab');
        const panels = document.querySelectorAll('.section-content');
        const noResultsGlobal = document.getElementById('noResultsGlobalMessage');
        const noResultsText = document.getElementById('noResultsQueryText');
        const panelsContainer = document.getElementById('sectionPanelsContainer');
        let totalMatches = 0;
        let firstMatchSecKey = null;

        tabs.forEach(tab => {
            const secKey = tab.dataset.sectionKey;
            const secName = tab.dataset.sectionName || '';
            const panel = document.getElementById('content-' + secKey);
            const badge = tab.querySelector('.tab-badge');
            const total = tab.dataset.count || 0;

            if (!panel) return;
            const rows = panel.querySelectorAll('.question-row');
            let secMatches = 0;

            rows.forEach(row => {
                const title = row.dataset.title || '';
                const type = row.dataset.type || '';
                const seq = row.dataset.seq || '';

                const match = !query || title.includes(query) || type.includes(query) || seq.includes(query) || secName.includes(query);
                row.style.display = match ? '' : 'none';
                if (match) secMatches++;
            });

            if (query) {
                if (badge) badge.textContent = `${secMatches} cocok`;
                if (secMatches > 0 || secName.includes(query)) {
                    tab.style.display = '';
                    totalMatches += secMatches;
                    if (!firstMatchSecKey) firstMatchSecKey = secKey;
                } else {
                    tab.style.display = 'none';
                }
            } else {
                if (badge) badge.textContent = `${total} Pertanyaan`;
                tab.style.display = '';
            }
        });

        if (query) {
            // Jika section aktif saat ini tidak ada kecocokan, alihkan ke section pertama yang memiliki kecocokan
            const activePanel = document.getElementById('content-' + activeSectionKey);
            const activeHasMatches = activePanel && Array.from(activePanel.querySelectorAll('.question-row')).some(r => r.style.display !== 'none');
            if (!activeHasMatches && firstMatchSecKey) {
                selectSection(firstMatchSecKey);
            }
        }

        if (noResultsGlobal && noResultsText) {
            if (query && totalMatches === 0) {
                noResultsText.textContent = query;
                noResultsGlobal.classList.remove('hidden');
                if (panelsContainer) panelsContainer.classList.add('hidden');
            } else {
                noResultsGlobal.classList.add('hidden');
                if (panelsContainer) panelsContainer.classList.remove('hidden');
                if (activeSectionKey) {
                    const activeP = document.getElementById('content-' + activeSectionKey);
                    if (activeP) activeP.classList.remove('hidden');
                }
            }
        }
    }

    // Event listener input pencarian seketika (Client-side real-time)
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('questionSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                applySearchFilter(e.target.value.toLowerCase().trim());
            });

            // Jika ada query awal dari SSR / URL parameter
            if (searchInput.value.trim() !== '') {
                applySearchFilter(searchInput.value.toLowerCase().trim());
            }
        }
    });
</script>
@endsection
