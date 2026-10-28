<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Services\TrashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrashController extends Controller
{
    public function __construct(
        protected TrashService $trashService
    ) {}

    /**
     * Tampilkan halaman daftar data terhapus (Recycle Bin / Tong Sampah).
     */
    public function index(Request $request): View
    {
        $selectedCategory = $request->query('category', 'all');
        if ($selectedCategory === 'all') {
            $selectedCategory = null;
        }

        $search = $request->query('q');
        $page = (int) $request->query('page', 1);

        $categories = TrashService::getCategoryDefinitions();
        $categoryCounts = $this->trashService->getCategoryCounts();
        $retentionDays = $this->trashService->getRetentionDays();
        $items = $this->trashService->getTrashedItems($selectedCategory, $search, 15, $page);

        return view('admin.trash.index', compact(
            'items',
            'categories',
            'categoryCounts',
            'selectedCategory',
            'search',
            'retentionDays'
        ));
    }

    /**
     * Pulihkan data yang terhapus kembali ke sistem.
     */
    public function restore(string $category, int $id): RedirectResponse
    {
        $success = $this->trashService->restoreItem($category, $id);

        if (!$success) {
            return back()->with('error', 'Data tidak ditemukan di tong sampah atau gagal dipulihkan.');
        }

        $catDefinitions = TrashService::getCategoryDefinitions();
        $label = $catDefinitions[$category]['label'] ?? $category;

        AdminActivityLog::record(
            actionType: 'restore',
            module: 'Tong Sampah',
            description: "Memulihkan data pada kategori '{$label}' (ID: {$id}).",
            targetType: $category,
            targetId: $id
        );

        return back()->with('success', "Data '{$label}' berhasil dipulihkan ke sistem aktif.");
    }

    /**
     * Hapus data secara permanen dari basis data.
     */
    public function forceDelete(string $category, int $id): RedirectResponse
    {
        $success = $this->trashService->forceDeleteItem($category, $id);

        if (!$success) {
            return back()->with('error', 'Data tidak ditemukan di tong sampah atau gagal dihapus permanen.');
        }

        $catDefinitions = TrashService::getCategoryDefinitions();
        $label = $catDefinitions[$category]['label'] ?? $category;

        AdminActivityLog::record(
            actionType: 'delete',
            module: 'Tong Sampah',
            description: "Menghapus permanen data pada kategori '{$label}' (ID: {$id}).",
            targetType: $category,
            targetId: $id
        );

        return back()->with('success', "Data '{$label}' berhasil dihapus permanen dari sistem.");
    }

    /**
     * Kosongkan seluruh tong sampah atau kategori tertentu.
     */
    public function emptyAll(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm_text' => ['required', 'in:KOSONGKAN'],
            'category' => ['nullable', 'string'],
        ], [
            'confirm_text.in' => 'Konfirmasi tidak valid. Ketik "KOSONGKAN" dengan huruf kapital untuk melanjutkan.',
        ]);

        $category = $request->input('category');
        if ($category === 'all') {
            $category = null;
        }

        $deletedCount = $this->trashService->emptyTrash($category);

        $catDefinitions = TrashService::getCategoryDefinitions();
        $targetScope = $category && isset($catDefinitions[$category]) 
            ? "kategori '{$catDefinitions[$category]['label']}'" 
            : 'seluruh kategori';

        AdminActivityLog::record(
            actionType: 'delete',
            module: 'Tong Sampah',
            description: "Mengosongkan tong sampah ({$targetScope}) - {$deletedCount} data dihapus permanen.",
            targetType: 'RecycleBin',
            newValues: ['deleted_count' => $deletedCount, 'scope' => $category ?? 'all']
        );

        return back()->with('success', "Tong sampah ({$targetScope}) berhasil dikosongkan. Sebanyak {$deletedCount} data telah dihapus permanen.");
    }

    /**
     * Perbarui durasi retensi otomatis (auto-delete).
     */
    public function updateRetention(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'retention_days' => ['required', 'integer', 'in:0,30,60,90'],
        ], [
            'retention_days.in' => 'Pilihan periode retensi tidak valid (hanya 30, 60, 90 hari atau Nonaktif/Manual).',
        ]);

        $days = (int) $validated['retention_days'];
        $this->trashService->setRetentionDays($days);

        $daysText = $days > 0 ? "{$days} hari" : 'Nonaktif (Pembersihan Manual)';

        AdminActivityLog::record(
            actionType: 'update',
            module: 'Tong Sampah',
            description: "Mengubah pengaturan retensi otomatis tong sampah menjadi {$daysText}.",
            targetType: 'SystemSetting',
            newValues: ['trash_retention_days' => $days]
        );

        return back()->with('success', "Pengaturan retensi otomatis berhasil diperbarui ke {$daysText}.");
    }
}
