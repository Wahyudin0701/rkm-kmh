@extends('layouts.app')

@section('title', 'Profil Saya - PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Judul -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-1.5 mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                    PENGATURAN AKUN
                </span>
                <!-- Tombol Info Panduan Halaman Sejajar Kicker -->
                <button type="button" 
                        onclick="openGuideModal()" 
                        class="w-3.5 h-3.5 rounded-full bg-slate-200/90 hover:bg-slate-300 text-slate-500 hover:text-slate-800 text-[8px] inline-flex items-center justify-center font-bold transition shadow-2xs cursor-pointer focus:outline-none" 
                        title="Panduan Halaman Profil Saya">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                Profil Saya
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Kelola informasi identitas, kredensial login, dan pengaturan keamanan akun Anda.
            </p>
        </div>
    </div>

    <!-- Layout 2 Kolom Profil -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        <!-- Kolom Kiri: Kartu Ringkasan Pengguna -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col items-center text-center">
            <!-- Avatar Besar -->
            <div class="w-24 h-24 rounded-full bg-[#1b5e20] text-white text-3xl font-black flex items-center justify-center shadow-lg mb-4 ring-4 ring-emerald-50">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>

            <h3 class="text-lg font-bold text-slate-900">{{ $user->name }}</h3>
            <p class="text-xs font-mono text-slate-500 mt-0.5">NIK / Username: {{ $user->username ?? '-' }}</p>

            <!-- Role Badge -->
            <div class="mt-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wide">
                    <i class="fa-solid fa-user-shield mr-1.5 text-[11px]"></i>
                    {{ str_replace('_', ' ', $user->role) }}
                </span>
            </div>

            <div class="w-full border-t border-slate-100 mt-6 pt-5 space-y-3 text-left text-xs">
                <div class="flex justify-between items-center py-1">
                    <span class="text-slate-500">Status Akun</span>
                    <span class="inline-flex items-center font-bold text-emerald-700">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                        Aktif
                    </span>
                </div>
                <div class="flex justify-between items-center py-1">
                    <span class="text-slate-500">Unit Operasional</span>
                    <span class="font-semibold text-slate-800">PLTA Merangin 350 MW</span>
                </div>
                <div class="flex justify-between items-center py-1">
                    <span class="text-slate-500">Terdaftar Sejak</span>
                    <span class="font-semibold text-slate-800">{{ $user->created_at ? $user->created_at->format('d M Y') : '12 Okt 2026' }}</span>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Form Edit Profil & Kata Sandi -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 mb-1">Perbarui Data Akun</h3>
            <p class="text-xs text-slate-400 mb-6">Ubah data nama lengkap, email, atau perbarui kata sandi akun Anda.</p>

            @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                    <div class="font-bold flex items-center">
                        <i class="fa-solid fa-triangle-exclamation mr-2"></i> Mohon periksa kesalahan berikut:
                    </div>
                    <ul class="list-disc list-inside ml-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('profile.update') }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <!-- Nama Lengkap -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-2">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full text-xs px-4 py-3 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Username / NIK (Read-Only) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-2">Username / NIK</label>
                        <div class="relative">
                            <input type="text" value="{{ $user->username ?? '-' }}" readonly
                                   class="w-full text-xs px-4 py-3 rounded-xl border border-slate-200 bg-slate-100 text-slate-500 font-mono cursor-not-allowed">
                            <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 text-xs" title="Username dikelola oleh Administrator">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Role Sistem (Read-Only) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-2">Hak Akses Sistem</label>
                        <div class="relative">
                            <input type="text" value="{{ strtoupper(str_replace('_', ' ', $user->role)) }}" readonly
                                   class="w-full text-xs px-4 py-3 rounded-xl border border-slate-200 bg-slate-100 text-slate-500 cursor-not-allowed">
                            <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 text-xs" title="Hak akses ditentukan administrator">
                                <i class="fa-solid fa-shield"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Email Pengguna -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-2">Alamat Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="nama@kerincihydro.co.id"
                           class="w-full text-xs px-4 py-3 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>

                <!-- Pemisah Bagian Ganti Password -->
                <div class="pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Keamanan & Kata Sandi</h4>
                    <p class="text-[11px] text-slate-400 mb-4">Kosongkan kolom kata sandi di bawah jika Anda tidak berniat mengubah kata sandi saat ini.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-2">Kata Sandi Baru</label>
                            <input type="password" name="password" placeholder="Minimal 6 karakter"
                                   class="w-full text-xs px-4 py-3 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-2">Konfirmasi Kata Sandi Baru</label>
                            <input type="password" name="password_confirmation" placeholder="Ulangi kata sandi baru"
                                   class="w-full text-xs px-4 py-3 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi Simpan -->
                <div class="pt-4 flex justify-end">
                    <button type="submit" 
                            class="bg-[#1b5e20] hover:bg-[#144919] text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center space-x-2 transition shadow-md">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (PROFIL SAYA)                                -->
<!-- ========================================================================= -->
<div id="guideModal" 
     onclick="if(event.target === this) closeGuideModal()" 
     class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-slate-100 transform transition-all my-8">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-sm border border-slate-200">
                    <i class="fa-solid fa-info"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight leading-tight">Panduan Halaman</h3>
                    <p class="text-xs text-slate-500">Profil Saya & Pengaturan Akun</p>
                </div>
            </div>
            <button type="button" 
                    onclick="closeGuideModal()" 
                    class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="mt-4 space-y-4">
            <p class="text-xs text-slate-600 leading-relaxed">
                Selamat datang di halaman <strong>Profil Saya</strong>! Halaman ini adalah pusat pengelolaan identitas pribadi, kontak dinas, dan kredensial keamanan akun Anda di Sistem PT Kerinci Merangin Hidro. Di halaman ini Anda dapat:
            </p>

            <div class="space-y-3">
                <!-- Fitur 1 (Hijau) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-emerald-200/50">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Perbarui Nama & Kontak Email</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Sesuaikan nama lengkap dan alamat email dinas Anda agar data pelaporan dan kontak notifikasi sistem selalu mutakhir.
                        </p>
                    </div>
                </div>

                <!-- Fitur 2 (Biru) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-blue-200/50">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Keamanan & Ganti Kata Sandi</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Ganti kata sandi secara berkala untuk menjaga kerahasiaan akun. Kosongkan isian kata sandi baru jika hanya ingin memperbarui data profil.
                        </p>
                    </div>
                </div>

                <!-- Fitur 3 (Amber) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-amber-200/50">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Kredensial & Wewenang Terkunci</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Username (NIK) dan Hak Akses sistem dikunci secara otomatis. Hubungi Administrator jika terdapat penyesuaian hak akses operasional.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Tombol -->
        <div class="pt-4 mt-5 border-t border-slate-100 flex items-center justify-end">
            <button type="button" 
                    onclick="closeGuideModal()" 
                    class="px-6 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer shadow-2xs">
                Saya Mengerti
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openGuideModal() {
        const modal = document.getElementById('guideModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.documentElement.classList.add('modal-open');
            document.body.classList.add('modal-open');
        }
    }

    function closeGuideModal() {
        const modal = document.getElementById('guideModal');
        if (modal) {
            modal.classList.add('hidden');
            document.documentElement.classList.remove('modal-open');
            document.body.classList.remove('modal-open');
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGuideModal();
        }
    });
</script>
@endsection
