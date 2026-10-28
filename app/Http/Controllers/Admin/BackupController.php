<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
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

        return view('admin.backups.index', compact('backups', 'stats', 'tableGroups'));
    }

    /**
     * Buat file backup baru berekstensi khusus .kmh (Pilih Backup atau Backup Semua).
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

            // Rekam ke Log Aktivitas Super Admin
            AdminActivityLog::record(
                actionType: 'create',
                module: 'Backup & Restore',
                description: "Membuat file backup terenkripsi .kmh: {$filename} [Cakupan: {$scopeLabel}].",
                targetType: 'BackupFile',
                targetName: $filename,
                newValues: [
                    'filename' => $filename,
                    'scope' => $scope,
                    'table_count' => is_array($selectedTables) ? count($selectedTables) : 'all',
                    'include_media' => $includeMedia,
                ]
            );

            return back()->with('success', "Arsip terenkripsi .kmh ({$filename}) berhasil dibuat dan diamankan di server.");
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
            return back()->with('error', "File backup '{$filename}' tidak ditemukan di server.");
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
        $request->validate([
            'backup_file' => ['required', 'file', 'max:204800'], // Maks. 200 MB
        ], [
            'backup_file.required' => 'Pilih file backup yang ingin diunggah.',
            'backup_file.file' => 'File yang diunggah tidak valid.',
            'backup_file.max' => 'Ukuran file backup maksimal 200 MB.',
        ]);

        $file = $request->file('backup_file');
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

            AdminActivityLog::record(
                actionType: 'create',
                module: 'Backup & Restore',
                description: "Mengunggah file backup eksternal: {$targetFilename}.",
                targetType: 'BackupFile',
                targetName: $targetFilename
            );

            return back()->with('success', "File backup '{$targetFilename}' berhasil diunggah ke server.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyimpan file backup yang diunggah: ' . $e->getMessage());
        }
    }

    /**
     * Pulihkan (Restore) database & aset sistem dari file backup (.kmh).
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
                description: "Melakukan pemulihan (restore) database dan aset sistem dari file backup: {$filename}.",
                targetType: 'BackupFile',
                targetName: $filename
            );

            return back()->with('success', "Sistem berhasil dipulihkan dari file backup '{$filename}'. Seluruh data tabel telah disinkronkan kembali.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan saat memulihkan sistem: ' . $e->getMessage());
        }
    }

    /**
     * Hapus file backup dari penyimpanan server.
     */
    public function destroy(string $filename): RedirectResponse
    {
        $filename = basename($filename);

        if ($this->backupService->deleteBackup($filename)) {
            AdminActivityLog::record(
                actionType: 'delete',
                module: 'Backup & Restore',
                description: "Menghapus file backup: {$filename}.",
                targetType: 'BackupFile',
                targetName: $filename
            );

            return back()->with('success', "File backup '{$filename}' berhasil dihapus dari server.");
        }

        return back()->with('error', "File backup '{$filename}' tidak ditemukan atau gagal dihapus.");
    }
}
