<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\BackupLog;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        protected DatabaseBackupService $backupService
    ) {}

    /**
     * Tampilkan halaman manajemen backup & restore sistem.
     */
    public function index(): View
    {
        $backups = $this->backupService->listBackups();
        $stats = $this->backupService->getSystemStats();
        $tableGroups = $this->backupService->getTableGroups();

        // Sinkronisasi file fisik di server ke riwayat BackupLog jika belum tercatat
        foreach ($backups as $b) {
            BackupLog::firstOrCreate(
                ['filename' => $b['filename']],
                [
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()?->name ?? auth()->user()?->username ?? 'Super Admin',
                    'scope' => $b['type'],
                    'scope_label' => $b['type_label'],
                    'table_count' => ($b['type'] === 'semua') ? count($this->backupService->getAllTableNames()) : 0,
                    'tables_included' => null,
                    'include_media' => ($b['type'] === 'semua'),
                    'file_size' => $b['size_raw'],
                    'file_size_human' => $b['size_human'],
                    'status' => 'Tersimpan di Server',
                    'created_at' => $b['created_at'],
                ]
            );
        }

        // Ambil seluruh riwayat backup terurut dari yang terbaru
        $backupLogs = BackupLog::latest()->get();
        $backupDir = $this->backupService->getBackupDirectory();

        // Tandai apakah file fisik masih ada di disk VPS server
        $backupLogs->each(function ($log) use ($backupDir) {
            $fullPath = $backupDir . DIRECTORY_SEPARATOR . $log->filename;
            $log->file_exists = File::exists($fullPath);
        });

        return view('admin.backups.index', compact('backupLogs', 'backups', 'stats', 'tableGroups'));
    }

    /**
     * Buat file backup baru berekstensi khusus .kmh (Pilih Backup atau Backup Semua).
     * Otomatis memicu unduhan langsung ke komputer Super Admin demi menghemat disk VPS kecil.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup_scope' => ['required', 'in:pilihan,semua'],
            'selected_tables' => ['nullable', 'array'],
            'selected_tables.*' => ['string'],
            'include_media' => ['nullable', 'in:1,0'],
        ], [
            'backup_scope.required' => 'Pilih cakupan pencadangan: Pilih Backup atau Backup Semua.',
            'backup_scope.in' => 'Cakupan backup tidak valid.',
        ]);

        $scope = $validated['backup_scope'];
        $includeMedia = false;
        $selectedTables = null;

        if ($scope === 'semua') {
            $includeMedia = true;
            $selectedTables = null; // Semua tabel
            $scopeLabel = 'Backup Semua (Database + Media Upload)';
        } else {
            $selectedTables = $request->input('selected_tables', []);
            if (empty($selectedTables)) {
                return back()->with('error', 'Silakan centang minimal satu tabel yang ingin Anda cadangkan.');
            }
            $includeMedia = (bool) $request->input('include_media', false);
            $scopeLabel = 'Pilih Backup (' . count($selectedTables) . ' Tabel Terpilih)';
        }

        try {
            $filename = $this->backupService->createKmhBackup($selectedTables, $includeMedia);

            $fullPath = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;
            $sizeBytes = File::exists($fullPath) ? File::size($fullPath) : 0;
            $sizeHuman = $this->backupService->formatBytes($sizeBytes);

            // Rekam ke Riwayat Aktivitas Backup
            BackupLog::create([
                'user_id' => $request->user()?->id,
                'user_name' => $request->user()?->name ?? $request->user()?->username ?? 'Super Admin',
                'filename' => $filename,
                'scope' => $scope,
                'scope_label' => $scopeLabel,
                'table_count' => is_array($selectedTables) ? count($selectedTables) : count($this->backupService->getAllTableNames()),
                'tables_included' => $selectedTables,
                'include_media' => $includeMedia,
                'file_size' => $sizeBytes,
                'file_size_human' => $sizeHuman,
                'status' => 'Berhasil Diunduh',
            ]);

            // Rekam ke Log Aktivitas Super Admin
            AdminActivityLog::record(
                actionType: 'create',
                module: 'Backup & Restore',
                description: "Membuat file backup terenkripsi .kmh: {$filename} [Cakupan: {$scopeLabel}]. File siap diunduh ke komputer lokal.",
                targetType: 'BackupFile',
                targetName: $filename,
                newValues: [
                    'filename' => $filename,
                    'scope' => $scope,
                    'table_count' => is_array($selectedTables) ? count($selectedTables) : 'all',
                    'include_media' => $includeMedia,
                    'size' => $sizeHuman,
                ]
            );

            return back()
                ->with('success', "Arsip backup terenkripsi .kmh ({$filename}) berhasil dibuat dan langsung diunduh ke komputer Anda.")
                ->with('auto_download', route('admin.backups.download', $filename));
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat backup: ' . $e->getMessage());
        }
    }

    /**
     * Unduh file backup .kmh ke penyimpanan komputer lokal.
     */
    public function download(string $filename): BinaryFileResponse|RedirectResponse
    {
        $filename = basename($filename);
        $fullPath = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        if (!File::exists($fullPath)) {
            return back()->with('error', "File backup '{$filename}' tidak ditemukan di server (kemungkinan telah dihapus untuk menghemat kapasitas disk VPS).");
        }

        AdminActivityLog::record(
            actionType: 'export',
            module: 'Backup & Restore',
            description: "Mengunduh file arsip backup: {$filename}.",
            targetType: 'BackupFile',
            targetName: $filename
        );

        return response()->download($fullPath, $filename, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Unggah file backup (.kmh, .sql, atau .zip) dari komputer lokal ke server.
     */
    public function upload(Request $request): RedirectResponse
    {
        // Validasi ketersediaan file dan deteksi error PHP upload limit
        if (!$request->hasFile('backup_file')) {
            $iniMax = ini_get('upload_max_filesize') ?: '2M';
            $postMax = ini_get('post_max_size') ?: '8M';
            return back()->with('error', "Gagal mengunggah file. Server tidak menerima berkas file (kemungkinan ukuran file melebihi batas konfigurasi PHP server: upload_max_filesize={$iniMax}, post_max_size={$postMax}).");
        }

        $file = $request->file('backup_file');
        if (!$file->isValid()) {
            return back()->with('error', 'File yang diunggah tidak valid atau gagal terkirim: ' . $file->getErrorMessage());
        }

        $request->validate([
            'backup_file' => ['required', 'file', 'max:204800'], // Maks. 200 MB
        ], [
            'backup_file.required' => 'Pilih file backup yang ingin diunggah.',
            'backup_file.file' => 'File yang diunggah tidak valid.',
            'backup_file.max' => 'Ukuran file backup maksimal 200 MB.',
        ]);

        $originalName = basename($file->getClientOriginalName());
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['kmh', 'sql', 'zip', 'gz'])) {
            return back()->with('error', 'Format file tidak didukung. File backup harus berekstensi .kmh, .sql, atau .zip.');
        }

        $targetDir = $this->backupService->getBackupDirectory();
        $targetFilename = $originalName;

        if (File::exists($targetDir . DIRECTORY_SEPARATOR . $targetFilename)) {
            $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
            $targetFilename = $nameOnly . '_' . now()->format('Ymd_His') . '.' . $ext;
        }

        try {
            $file->move($targetDir, $targetFilename);
            $storedPath = $targetDir . DIRECTORY_SEPARATOR . $targetFilename;
            $fileSize = File::exists($storedPath) ? File::size($storedPath) : 0;
            $fileSizeHuman = $this->backupService->formatBytes($fileSize);

            // Rekam ke Riwayat Aktivitas Backup
            BackupLog::firstOrCreate(
                ['filename' => $targetFilename],
                [
                    'user_id' => $request->user()?->id,
                    'user_name' => $request->user()?->name ?? $request->user()?->username ?? 'Super Admin',
                    'scope' => str_contains($targetFilename, 'semua') ? 'semua' : 'pilihan',
                    'scope_label' => 'Unggahan Eksternal (' . strtoupper($ext) . ')',
                    'table_count' => 0,
                    'tables_included' => null,
                    'include_media' => false,
                    'file_size' => $fileSize,
                    'file_size_human' => $fileSizeHuman,
                    'status' => 'Tersimpan di Server',
                ]
            );

            AdminActivityLog::record(
                actionType: 'create',
                module: 'Backup & Restore',
                description: "Mengunggah file backup eksternal: {$targetFilename} ({$fileSizeHuman}).",
                targetType: 'BackupFile',
                targetName: $targetFilename
            );

            return back()->with('success', "File backup '{$targetFilename}' ({$fileSizeHuman}) berhasil diunggah ke server.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyimpan file backup yang diunggah: ' . $e->getMessage());
        }
    }

    /**
     * Pulihkan (Restore) database & aset sistem dari file backup (.kmh) yang sudah ada di server.
     */
    public function restore(Request $request, string $filename): RedirectResponse
    {
        $filename = basename($filename);
        $fullPath = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        if (!File::exists($fullPath)) {
            return back()->with('error', "File backup '{$filename}' tidak ditemukan di server.");
        }

        // Verifikasi Keamanan: Ketik "PULIHKAN" atau masukkan password Super Admin
        $confirmed = false;
        if ($request->filled('confirmation') && trim($request->input('confirmation')) === 'PULIHKAN') {
            $confirmed = true;
        } elseif ($request->filled('password') && Hash::check($request->input('password'), $request->user()->password)) {
            $confirmed = true;
        }

        if (!$confirmed) {
            return back()->with('error', 'Konfirmasi keamanan tidak sesuai. Silakan ketik kata "PULIHKAN" atau masukkan password akun Super Admin Anda untuk melanjutkan pemulihan.');
        }

        try {
            $this->backupService->restore($fullPath);

            // Bersihkan cache aplikasi & permission registrar Spatie
            Artisan::call('cache:clear');
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            AdminActivityLog::record(
                actionType: 'update',
                module: 'Backup & Restore',
                description: "Melakukan pemulihan (restore) database dan aset sistem dari file backup server: {$filename}.",
                targetType: 'BackupFile',
                targetName: $filename
            );

            return back()->with('success', "Sistem berhasil dipulihkan dari file backup '{$filename}'. Seluruh data tabel telah disinkronkan kembali.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan saat memulihkan sistem: ' . $e->getMessage());
        }
    }

    /**
     * Pulihkan (Restore) database & aset sistem secara langsung dari file (.kmh) yang diunggah/didrag & drop.
     * Tidak meninggalkan file sampah di server sehingga sangat hemat disk VPS.
     */
    public function restoreFromFile(Request $request): RedirectResponse
    {
        if (!$request->hasFile('backup_file')) {
            $iniMax = ini_get('upload_max_filesize') ?: '2M';
            $postMax = ini_get('post_max_size') ?: '8M';
            return back()->with('error', "File backup .kmh belum dipilih atau file melebihi batas upload PHP server (upload_max_filesize: {$iniMax}, post_max_size: {$postMax}).");
        }

        $file = $request->file('backup_file');
        if (!$file->isValid()) {
            return back()->with('error', 'File yang diunggah tidak valid atau gagal dibaca: ' . $file->getErrorMessage());
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['kmh', 'sql', 'zip'])) {
            return back()->with('error', 'Format file tidak didukung. File backup harus berekstensi .kmh, .sql, atau .zip.');
        }

        // Verifikasi Keamanan: Ketik "PULIHKAN" atau masukkan password Super Admin
        $confirmed = false;
        if ($request->filled('confirmation') && trim($request->input('confirmation')) === 'PULIHKAN') {
            $confirmed = true;
        } elseif ($request->filled('password') && Hash::check($request->input('password'), $request->user()->password)) {
            $confirmed = true;
        }

        if (!$confirmed) {
            return back()->with('error', 'Konfirmasi keamanan tidak sesuai. Silakan ketik kata "PULIHKAN" atau masukkan password akun Super Admin Anda untuk melanjutkan pemulihan.');
        }

        $tempPath = $file->getRealPath();
        $originalName = $file->getClientOriginalName();

        try {
            $this->backupService->restore($tempPath);

            // Bersihkan cache aplikasi & permission registrar Spatie
            Artisan::call('cache:clear');
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            AdminActivityLog::record(
                actionType: 'update',
                module: 'Backup & Restore',
                description: "Melakukan pemulihan (restore) langsung dari file lokal: {$originalName}.",
                targetType: 'BackupFile',
                targetName: $originalName
            );

            return back()->with('success', "Sistem berhasil dipulihkan secara langsung dari file '{$originalName}'. Seluruh data tabel dan media telah disinkronkan kembali.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan saat memulihkan sistem: ' . $e->getMessage());
        }
    }

    /**
     * Hapus file backup dari penyimpanan server untuk menghemat kapasitas disk VPS.
     * Riwayat log backup tetap tersimpan di sistem.
     */
    public function destroy(string $filename): RedirectResponse
    {
        $filename = basename($filename);

        if ($this->backupService->deleteBackup($filename)) {
            // Update status log jika ada
            BackupLog::where('filename', $filename)->update([
                'status' => 'Tersimpan di Komputer Lokal (Dihapus dari Server)',
            ]);

            AdminActivityLog::record(
                actionType: 'delete',
                module: 'Backup & Restore',
                description: "Menghapus file fisik backup dari disk server: {$filename}.",
                targetType: 'BackupFile',
                targetName: $filename
            );

            return back()->with('success', "File fisik '{$filename}' berhasil dihapus dari penyimpanan server untuk menghemat kapasitas disk VPS. Riwayat pencadangan tetap tersimpan.");
        }

        return back()->with('error', "File backup '{$filename}' tidak ditemukan di server atau sudah pernah dihapus.");
    }
}
