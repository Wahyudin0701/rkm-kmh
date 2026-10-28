@extends('layouts.app')

@section('title', 'Tong Sampah Sistem (Recycle Bin) - Super Admin RKM')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman (Clean Minimalist) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-100">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                Tong Sampah Sistem (Recycle Bin)
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Pusat pemulihan dan retensi data terhapus untuk seluruh modul operasional sistem RKM-KMH.
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" 
                    onclick="openRetentionModal()" 
                    class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition shadow-2xs">
                Atur Retensi ({{ $retentionDays > 0 ? $retentionDays . ' Hari' : 'Manual' }})
            </button>
            <button type="button" 
                    onclick="openEmptyTrashModal()" 
                    class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition shadow-xs">
                Kosongkan Sampah
            </button>
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if(session('success'))
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-emerald-900 text-xs font-medium">
            <span>{{ session('success') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 ml-3 text-sm">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl flex items-center justify-between text-rose-900 text-xs font-medium">
            <span>{{ session('error') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 ml-3 text-sm">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-900 text-xs font-medium">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Ringkasan Metrik (Clean & Tanpa Ikon Berlebihan) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Total Data di Sampah</span>
            <div class="flex items-baseline gap-2 mt-1.5">
                <span class="text-2xl font-bold text-slate-900">{{ number_format($categoryCounts['all']) }}</span>
                <span class="text-xs text-slate-400 font-medium">record</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-2">Tersimpan dengan Soft Delete</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Retensi Otomatis Aktif</span>
            <div class="flex items-baseline gap-2 mt-1.5">
                <span class="text-2xl font-bold text-indigo-600">
                    {{ $retentionDays > 0 ? $retentionDays . ' Hari' : 'Nonaktif' }}
                </span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-2">
                {{ $retentionDays > 0 ? 'Data kadaluarsa dibersihkan berkala' : 'Pembersihan manual oleh Super Admin' }}
            </span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Kategori Terisi</span>
            <div class="flex items-baseline gap-2 mt-1.5">
                @php
                    $nonEmptyCats = 0;
                    foreach($categories as $cKey => $cConf) {
                        if (($categoryCounts[$cKey] ?? 0) > 0) $nonEmptyCats++;
                    }
                @endphp
                <span class="text-2xl font-bold text-slate-900">{{ $nonEmptyCats }}</span>
                <span class="text-xs text-slate-400 font-medium">dari {{ count($categories) }} modul</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-2">Memiliki data dalam tong sampah</span>
        </div>
    </div>

    <!-- Filter Tab & Pencarian -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs space-y-4">
        <!-- Tab Pills Kategori (Scrollable Horizontal) -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
            <a href="{{ route('admin.trash.index', ['category' => 'all', 'q' => $search]) }}"
               class="shrink-0 px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ is_null($selectedCategory) ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Semua
                <span class="ml-1 text-[10px] {{ is_null($selectedCategory) ? 'bg-indigo-700 text-indigo-100' : 'bg-slate-200 text-slate-600' }} px-1.5 py-0.5 rounded-full">
                    {{ $categoryCounts['all'] }}
                </span>
            </a>
            @foreach($categories as $key => $conf)
                @php
                    $isActive = $selectedCategory === $key;
                    $count = $categoryCounts[$key] ?? 0;
                @endphp
                <a href="{{ route('admin.trash.index', ['category' => $key, 'q' => $search]) }}"
                   class="shrink-0 px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $isActive ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $conf['label'] }}
                    <span class="ml-1 text-[10px] {{ $isActive ? 'bg-indigo-700 text-indigo-100' : 'bg-slate-200 text-slate-600' }} px-1.5 py-0.5 rounded-full">
                        {{ $count }}
                    </span>
                </a>
            @endforeach
        </div>

        <!-- Form Pencarian Cepat -->
        <form method="GET" action="{{ route('admin.trash.index') }}" class="flex items-center gap-2">
            @if($selectedCategory)
                <input type="hidden" name="category" value="{{ $selectedCategory }}">
            @endif
            <div class="relative flex-1">
                <input type="text" 
                       name="q" 
                       value="{{ $search }}" 
                       placeholder="Cari data terhapus (nama, kode, NIK, nomor transaksi, dsb)..." 
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 py-2.5 px-3.5">
            </div>
            <button type="submit" 
                    class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold transition">
                Cari
            </button>
            @if($search)
                <a href="{{ route('admin.trash.index', ['category' => $selectedCategory ?? 'all']) }}" 
                   class="px-3.5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-semibold transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Tabel Data Terhapus -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4 w-44">Kategori Modul</th>
                        <th class="py-3.5 px-4">Identitas / Informasi Data</th>
                        <th class="py-3.5 px-4 w-44">Waktu Dihapus</th>
                        <th class="py-3.5 px-4 w-36">Retensi</th>
                        <th class="py-3.5 px-4 w-48 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $index => $item)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3 px-4 text-center text-slate-400 font-medium">
                                {{ $items->firstItem() + $index }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-block px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 text-slate-700">
                                    {{ $item->category_label }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 text-xs">
                                    {{ $item->title }}
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ $item->subtitle }}
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-800">
                                    {{ $item->deleted_at_human }}
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    {{ $item->days_in_trash == 0 ? 'Hari ini' : $item->days_in_trash . ' hari lalu' }}
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                @if(is_null($item->days_remaining))
                                    <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-600">
                                        Manual
                                    </span>
                                @elseif($item->days_remaining > 7)
                                    <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Sisa {{ $item->days_remaining }} hari
                                    </span>
                                @elseif($item->days_remaining > 0)
                                    <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Sisa {{ $item->days_remaining }} hari
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        Kadaluarsa
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Tombol Pulihkan -->
                                    <form method="POST" 
                                          action="{{ route('admin.trash.restore', ['category' => $item->category, 'id' => $item->id]) }}" 
                                          onsubmit="return confirm('Pulihkan data ini kembali ke sistem aktif?')">
                                        @csrf
                                        <button type="submit" 
                                                class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition shadow-2xs">
                                            Pulihkan
                                        </button>
                                    </form>

                                    <!-- Tombol Hapus Permanen -->
                                    <form method="POST" 
                                          action="{{ route('admin.trash.force_delete', ['category' => $item->category, 'id' => $item->id]) }}" 
                                          onsubmit="return confirm('PERINGATAN: Data akan dihapus secara permanen dari basis data dan tidak dapat dikembalikan lagi. Lanjutkan?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="px-2.5 py-1.5 rounded-lg border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold transition">
                                            Hapus Permanen
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <div class="max-w-xs mx-auto text-center space-y-1">
                                    <p class="text-xs font-bold text-slate-700">Tong Sampah Bersih</p>
                                    <p class="text-[11px] text-slate-400">Tidak ada data terhapus pada kategori ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Atur Retensi Otomatis -->
<div id="retentionModal" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full border border-slate-200 shadow-xl overflow-hidden p-6 space-y-5 animate-in fade-in duration-150">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Pengaturan Retensi Otomatis</h3>
            <button type="button" onclick="closeRetentionModal()" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
        </div>

        <p class="text-xs text-slate-500 leading-relaxed">
            Pilih berapa lama data terhapus disimpan di tong sampah sebelum dibersihkan permanen secara otomatis oleh sistem.
        </p>

        <form method="POST" action="{{ route('admin.trash.retention') }}" class="space-y-4">
            @csrf
            <div class="space-y-2.5">
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                    <input type="radio" name="retention_days" value="30" class="text-indigo-600 focus:ring-indigo-500" {{ $retentionDays == 30 ? 'checked' : '' }}>
                    <div>
                        <div class="text-xs font-bold text-slate-800">30 Hari (Default Rekomendasi)</div>
                        <div class="text-[10px] text-slate-500">Data otomatis dibersihkan 30 hari setelah dihapus</div>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                    <input type="radio" name="retention_days" value="60" class="text-indigo-600 focus:ring-indigo-500" {{ $retentionDays == 60 ? 'checked' : '' }}>
                    <div>
                        <div class="text-xs font-bold text-slate-800">60 Hari (2 Bulan)</div>
                        <div class="text-[10px] text-slate-500">Periode retensi menengah untuk evaluasi operasional</div>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                    <input type="radio" name="retention_days" value="90" class="text-indigo-600 focus:ring-indigo-500" {{ $retentionDays == 90 ? 'checked' : '' }}>
                    <div>
                        <div class="text-xs font-bold text-slate-800">90 Hari (3 Bulan / Kuartal)</div>
                        <div class="text-[10px] text-slate-500">Retensi jangka panjang sebelum pembersihan total</div>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                    <input type="radio" name="retention_days" value="0" class="text-indigo-600 focus:ring-indigo-500" {{ $retentionDays == 0 ? 'checked' : '' }}>
                    <div>
                        <div class="text-xs font-bold text-slate-800">Nonaktif (Pembersihan Manual)</div>
                        <div class="text-[10px] text-slate-500">Data terhapus disimpan selamanya sampai Super Admin menghapus manual</div>
                    </div>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button type="button" 
                        onclick="closeRetentionModal()" 
                        class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition shadow-xs">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Kosongkan Sampah -->
<div id="emptyTrashModal" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full border border-slate-200 shadow-xl overflow-hidden p-6 space-y-5 animate-in fade-in duration-150">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-rose-700">Kosongkan Tong Sampah</h3>
            <button type="button" onclick="closeEmptyTrashModal()" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
        </div>

        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 leading-relaxed">
            <strong>PERHATIAN:</strong> Tindakan ini akan menghapus data terpilih secara permanen dari server. Data yang telah dihapus permanen <strong>TIDAK DAPAT DIKEMBALIKAN</strong> lagi.
        </div>

        <form method="POST" action="{{ route('admin.trash.empty') }}" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih Cakupan Pembersihan</label>
                <select name="category" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3">
                    <option value="all" {{ is_null($selectedCategory) ? 'selected' : '' }}>Seluruh Tong Sampah (Semua Kategori - {{ $categoryCounts['all'] }} data)</option>
                    @foreach($categories as $key => $conf)
                        <option value="{{ $key }}" {{ $selectedCategory === $key ? 'selected' : '' }}>
                            Kategori: {{ $conf['label'] }} ({{ $categoryCounts[$key] ?? 0 }} data)
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Ketik <span class="font-mono font-bold text-rose-700">KOSONGKAN</span> untuk konfirmasi:
                </label>
                <input type="text" 
                       id="confirmText" 
                       name="confirm_text" 
                       oninput="validateEmptyTrashInput(this.value)"
                       placeholder="KOSONGKAN" 
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-rose-500 focus:ring focus:ring-rose-200/50 py-2.5 px-3 uppercase tracking-wider font-semibold">
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button type="button" 
                        onclick="closeEmptyTrashModal()" 
                        class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                    Batal
                </button>
                <button type="submit" 
                        id="emptyTrashSubmitBtn" 
                        disabled 
                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold transition shadow-xs">
                    Kosongkan Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRetentionModal() {
        document.getElementById('retentionModal').classList.remove('hidden');
    }

    function closeRetentionModal() {
        document.getElementById('retentionModal').classList.add('hidden');
    }

    function openEmptyTrashModal() {
        document.getElementById('confirmText').value = '';
        validateEmptyTrashInput('');
        document.getElementById('emptyTrashModal').classList.remove('hidden');
    }

    function closeEmptyTrashModal() {
        document.getElementById('emptyTrashModal').classList.add('hidden');
    }

    function validateEmptyTrashInput(value) {
        const btn = document.getElementById('emptyTrashSubmitBtn');
        if (value.trim() === 'KOSONGKAN') {
            btn.removeAttribute('disabled');
        } else {
            btn.setAttribute('disabled', 'disabled');
        }
    }
</script>
@endsection
