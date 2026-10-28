<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeSimper;
use App\Models\Gate;
use App\Models\P2hRecord;
use App\Models\P2hRecordItem;
use App\Models\P2hTemplate;
use App\Models\P2hTemplateItem;
use App\Models\SecurityGuard;
use App\Models\SimperType;
use App\Models\SystemSetting;
use App\Models\UnitSupportTimesheet;
use App\Models\UnitTransfer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrashService
{
    public const DEFAULT_RETENTION_DAYS = 30;

    /**
     * Category definitions and configurations.
     */
    public static function getCategoryDefinitions(): array
    {
        return [
            'activities' => [
                'label' => 'Perjalanan Gate',
                'model' => VehicleActivity::class,
                'title_column' => 'transaction_no',
                'with' => ['vehicle', 'driver', 'gate'],
                'subtitle_callback' => function ($item) {
                    $veh = $item->vehicle()->withTrashed()->first();
                    $driver = $item->driver()->withTrashed()->first();
                    $vehCode = $veh ? $veh->code : 'Unit #' . $item->vehicle_id;
                    $driverName = $driver ? $driver->name : 'Driver #' . $item->driver_id;
                    return "{$vehCode} • {$driverName}";
                },
                'search_columns' => ['transaction_no', 'purpose'],
            ],
            'employees' => [
                'label' => 'Data Karyawan',
                'model' => Employee::class,
                'title_column' => 'name',
                'with' => [],
                'subtitle_callback' => function ($item) {
                    $nik = $item->nik ?? '-';
                    $pos = $item->position ?? ($item->is_operator ? 'Operator' : 'Karyawan');
                    $div = $item->division ? " • {$item->division}" : '';
                    return "NIK: {$nik} • {$pos}{$div}";
                },
                'search_columns' => ['name', 'nik', 'position', 'division', 'unit_kerja'],
            ],
            'vehicles' => [
                'label' => 'Unit Kendaraan & Alat Berat',
                'model' => Vehicle::class,
                'title_column' => 'code',
                'with' => [],
                'subtitle_callback' => function ($item) {
                    $plate = $item->license_plate ?? $item->model_number ?? '-';
                    $cat = $item->unit_category ?? $item->category ?? 'Unit';
                    return "No. Plat/Model: {$plate} • Kategori: {$cat}";
                },
                'search_columns' => ['code', 'license_plate', 'brand_type', 'brand', 'model_number', 'unit_type'],
            ],
            'transfers' => [
                'label' => 'Mobilisasi / Mutasi Unit',
                'model' => UnitTransfer::class,
                'title_column' => 'transfer_no',
                'with' => ['vehicle'],
                'subtitle_callback' => function ($item) {
                    $veh = $item->vehicle()->withTrashed()->first();
                    $vehCode = $veh ? $veh->code : 'Unit #' . $item->vehicle_id;
                    $route = ($item->from_location ?? '?') . ' ➔ ' . ($item->to_location ?? '?');
                    return "{$vehCode} • {$route}";
                },
                'search_columns' => ['transfer_no', 'from_location', 'to_location', 'description'],
            ],
            'timesheets' => [
                'label' => 'Timesheet Alat Berat',
                'model' => UnitSupportTimesheet::class,
                'title_column' => 'timesheet_no',
                'with' => ['vehicle', 'operator'],
                'subtitle_callback' => function ($item) {
                    $veh = $item->vehicle()->withTrashed()->first();
                    $op = $item->operator()->withTrashed()->first();
                    $vehCode = $veh ? $veh->code : 'Unit #' . $item->vehicle_id;
                    $opName = $op ? $op->name : 'Operator #' . $item->operator_id;
                    return "{$vehCode} • {$opName} • Shift: " . ($item->shift ?? '-');
                },
                'search_columns' => ['timesheet_no', 'location', 'task_description'],
            ],
            'p2h_records' => [
                'label' => 'Pemeriksaan P2H',
                'model' => P2hRecord::class,
                'title_column' => 'transaction_no',
                'with' => ['vehicle', 'employee'],
                'subtitle_callback' => function ($item) {
                    $veh = $item->vehicle()->withTrashed()->first();
                    $emp = $item->employee()->withTrashed()->first();
                    $vehCode = $veh ? $veh->code : 'Unit #' . $item->vehicle_id;
                    $empName = $emp ? $emp->name : 'Pemeriksa #' . $item->employee_id;
                    $cond = $item->summary_condition ? ucfirst($item->summary_condition) : 'Kondisi -';
                    return "{$vehCode} • {$empName} • {$cond}";
                },
                'search_columns' => ['transaction_no', 'general_notes'],
            ],
            'users' => [
                'label' => 'User Akun',
                'model' => User::class,
                'title_column' => 'name',
                'with' => [],
                'subtitle_callback' => function ($item) {
                    $email = $item->email ?? '-';
                    $role = $item->role ?? 'User';
                    return "{$email} • Role: {$role}";
                },
                'search_columns' => ['name', 'email', 'role'],
            ],
            'simpers' => [
                'label' => 'SIMPER Karyawan',
                'model' => EmployeeSimper::class,
                'title_column' => 'simper_number',
                'with' => ['employee', 'simperType'],
                'subtitle_callback' => function ($item) {
                    $emp = $item->employee()->withTrashed()->first();
                    $type = $item->simperType()->withTrashed()->first();
                    $empName = $emp ? $emp->name : 'Karyawan #' . $item->employee_id;
                    $typeName = $type ? $type->name : 'Tipe #' . $item->simper_type_id;
                    return "{$empName} • Tipe: {$typeName}";
                },
                'search_columns' => ['simper_number', 'notes'],
            ],
            'simper_types' => [
                'label' => 'Kategori SIMPER',
                'model' => SimperType::class,
                'title_column' => 'name',
                'with' => [],
                'subtitle_callback' => function ($item) {
                    $code = $item->code ?? '-';
                    $scope = $item->scope ?? '-';
                    return "Kode: {$code} • Scope: {$scope}";
                },
                'search_columns' => ['name', 'code', 'scope', 'description'],
            ],
            'p2h_templates' => [
                'label' => 'Template P2H',
                'model' => P2hTemplate::class,
                'title_column' => 'name',
                'with' => [],
                'subtitle_callback' => function ($item) {
                    $code = $item->code ?? '-';
                    return "Kode Template: {$code}";
                },
                'search_columns' => ['name', 'code', 'description'],
            ],
            'gates' => [
                'label' => 'Pos / Gate',
                'model' => Gate::class,
                'title_column' => 'name',
                'with' => [],
                'subtitle_callback' => function ($item) {
                    $code = $item->code ?? '-';
                    return "Kode Pos: {$code}";
                },
                'search_columns' => ['name', 'code'],
            ],
            'security_guards' => [
                'label' => 'Petugas Keamanan',
                'model' => SecurityGuard::class,
                'title_column' => 'name',
                'with' => ['gate'],
                'subtitle_callback' => function ($item) {
                    $badge = $item->badge_id ?? '-';
                    $team = $item->team ? "Regu {$item->team}" : '-';
                    return "Badge: {$badge} • {$team}";
                },
                'search_columns' => ['name', 'badge_id'],
            ],
        ];
    }

    /**
     * Get count of trashed items for each category and the total count.
     */
    public function getCategoryCounts(): array
    {
        $categories = self::getCategoryDefinitions();
        $counts = [];
        $total = 0;

        foreach ($categories as $key => $config) {
            $model = $config['model'];
            $count = $model::onlyTrashed()->count();
            $counts[$key] = $count;
            $total += $count;
        }

        $counts['all'] = $total;
        return $counts;
    }

    /**
     * Get retention period in days.
     */
    public function getRetentionDays(): int
    {
        $val = SystemSetting::get('trash_retention_days', self::DEFAULT_RETENTION_DAYS);
        return (int) $val;
    }

    /**
     * Update retention period in days.
     */
    public function setRetentionDays(int $days): void
    {
        SystemSetting::set('trash_retention_days', $days);
    }

    /**
     * Get trashed items with optional filtering and pagination.
     */
    public function getTrashedItems(?string $category = null, ?string $search = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $retentionDays = $this->getRetentionDays();
        $categories = self::getCategoryDefinitions();

        if ($category && isset($categories[$category])) {
            // Single category query
            $config = $categories[$category];
            $model = $config['model'];
            $query = $model::onlyTrashed()->with($config['with'] ?? []);

            if ($search) {
                $query->where(function ($q) use ($config, $search) {
                    foreach ($config['search_columns'] as $col) {
                        $q->orWhere($col, 'like', "%{$search}%");
                    }
                });
            }

            $total = $query->count();
            $rawItems = $query->latest('deleted_at')
                ->forPage($page, $perPage)
                ->get();

            $mapped = $rawItems->map(function ($item) use ($category, $config, $retentionDays) {
                return $this->formatTrashedItem($item, $category, $config, $retentionDays);
            });

            return new LengthAwarePaginator(
                $mapped,
                $total,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        // All categories: aggregate items
        $collected = collect();
        foreach ($categories as $catKey => $config) {
            $model = $config['model'];
            $query = $model::onlyTrashed();

            if ($search) {
                $query->where(function ($q) use ($config, $search) {
                    foreach ($config['search_columns'] as $col) {
                        $q->orWhere($col, 'like', "%{$search}%");
                    }
                });
            }

            // Limit to most recent 200 per category to prevent memory exhaustion
            $items = $query->latest('deleted_at')->limit(200)->get();
            foreach ($items as $item) {
                $collected->push($this->formatTrashedItem($item, $catKey, $config, $retentionDays));
            }
        }

        // Sort unified collection by deleted_at descending
        $sorted = $collected->sortByDesc('deleted_at_timestamp')->values();
        $total = $sorted->count();
        $sliced = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $sliced,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Format a model into a standard trashed display object.
     */
    protected function formatTrashedItem($item, string $category, array $config, int $retentionDays): object
    {
        $deletedAt = $item->deleted_at ? Carbon::parse($item->deleted_at) : Carbon::now();
        $daysInTrash = (int) $deletedAt->diffInDays(Carbon::now());
        
        $daysRemaining = null;
        if ($retentionDays > 0) {
            $daysRemaining = max(0, $retentionDays - $daysInTrash);
        }

        $titleCol = $config['title_column'];
        $title = $item->{$titleCol} ?? ("ID #" . $item->id);

        $subtitle = '';
        if (isset($config['subtitle_callback']) && is_callable($config['subtitle_callback'])) {
            try {
                $subtitle = call_user_func($config['subtitle_callback'], $item);
            } catch (\Throwable $e) {
                $subtitle = 'ID: ' . $item->id;
            }
        }

        return (object) [
            'id' => $item->id,
            'category' => $category,
            'category_label' => $config['label'],
            'title' => $title,
            'subtitle' => $subtitle,
            'deleted_at' => $deletedAt,
            'deleted_at_timestamp' => $deletedAt->timestamp,
            'deleted_at_human' => $deletedAt->translatedFormat('d M Y, H:i'),
            'days_in_trash' => $daysInTrash,
            'days_remaining' => $daysRemaining,
            'raw_item' => $item,
        ];
    }

    /**
     * Restore a trashed item.
     */
    public function restoreItem(string $category, int $id): bool
    {
        $categories = self::getCategoryDefinitions();
        if (!isset($categories[$category])) {
            return false;
        }

        $model = $categories[$category]['model'];
        $item = $model::onlyTrashed()->find($id);

        if (!$item) {
            return false;
        }

        DB::transaction(function () use ($item) {
            $item->restore();

            // Cascade restore children if applicable
            if ($item instanceof P2hRecord) {
                P2hRecordItem::onlyTrashed()
                    ->where('p2h_record_id', $item->id)
                    ->restore();
            }

            if ($item instanceof P2hTemplate) {
                P2hTemplateItem::onlyTrashed()
                    ->where('template_id', $item->id)
                    ->restore();
            }

            if ($item instanceof Employee) {
                EmployeeSimper::onlyTrashed()
                    ->where('employee_id', $item->id)
                    ->restore();
            }
        });

        return true;
    }

    /**
     * Force delete an item permanently.
     */
    public function forceDeleteItem(string $category, int $id): bool
    {
        $categories = self::getCategoryDefinitions();
        if (!isset($categories[$category])) {
            return false;
        }

        $model = $categories[$category]['model'];
        $item = $model::onlyTrashed()->find($id);

        if (!$item) {
            return false;
        }

        DB::transaction(function () use ($item) {
            // Cascade force delete children
            if ($item instanceof P2hRecord) {
                P2hRecordItem::withTrashed()
                    ->where('p2h_record_id', $item->id)
                    ->forceDelete();
            }

            if ($item instanceof P2hTemplate) {
                P2hTemplateItem::withTrashed()
                    ->where('template_id', $item->id)
                    ->forceDelete();
            }

            if ($item instanceof Employee) {
                EmployeeSimper::withTrashed()
                    ->where('employee_id', $item->id)
                    ->forceDelete();
            }

            $item->forceDelete();
        });

        return true;
    }

    /**
     * Empty trash completely or for a specific category.
     */
    public function emptyTrash(?string $category = null): int
    {
        $categories = self::getCategoryDefinitions();
        $totalDeleted = 0;

        if ($category && isset($categories[$category])) {
            $model = $categories[$category]['model'];
            $items = $model::onlyTrashed()->get();
            foreach ($items as $item) {
                $this->forceDeleteItem($category, $item->id);
                $totalDeleted++;
            }
            return $totalDeleted;
        }

        // Empty all
        foreach ($categories as $catKey => $config) {
            $model = $config['model'];
            $items = $model::onlyTrashed()->get();
            foreach ($items as $item) {
                $this->forceDeleteItem($catKey, $item->id);
                $totalDeleted++;
            }
        }

        return $totalDeleted;
    }

    /**
     * Prune trash older than retention threshold.
     */
    public function pruneOldTrash(?int $days = null): int
    {
        if ($days === null) {
            $days = $this->getRetentionDays();
        }

        // If retention is 0 or less, auto-pruning is disabled (manual only)
        if ($days <= 0) {
            return 0;
        }

        $cutoff = Carbon::now()->subDays($days);
        $categories = self::getCategoryDefinitions();
        $prunedCount = 0;

        foreach ($categories as $catKey => $config) {
            $model = $config['model'];
            $expiredItems = $model::onlyTrashed()->where('deleted_at', '<=', $cutoff)->get();
            foreach ($expiredItems as $item) {
                $this->forceDeleteItem($catKey, $item->id);
                $prunedCount++;
            }
        }

        return $prunedCount;
    }
}
