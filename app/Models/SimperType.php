<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimperType extends Model
{
    use HasFactory;

    public const SCOPE_LIGHT_VEHICLE = 'light_vehicle';
    public const SCOPE_UNIT_SUPPORT_MOBILE = 'unit_support_mobile';
    public const SCOPE_UNIT_SUPPORT_HEAVY = 'unit_support_heavy';
    public const SCOPE_ALL = 'all';

    public const SCOPES = [
        self::SCOPE_LIGHT_VEHICLE => 'Armada Ringan (Light Vehicle)',
        self::SCOPE_UNIT_SUPPORT_MOBILE => 'Armada Berat Bergerak (Heavy Mobile / Dump Truck)',
        self::SCOPE_UNIT_SUPPORT_HEAVY => 'Alat Berat Operasional (Statis / Tambang)',
        self::SCOPE_ALL => 'Semua Kategori Armada',
    ];

    /**
     * Dapatkan daftar cakupan armada yang disinkronisasikan langsung
     * dengan data Master Kategori Heavy Equipment dari modul QMA.
     *
     * @return array<string, string>
     */
    public static function getAvailableScopes(): array
    {
        $scopes = [
            self::SCOPE_ALL => 'Semua Kategori (Universal)',
            self::SCOPE_LIGHT_VEHICLE => 'Light Vehicle (LV)',
        ];

        // Baseline kategori Heavy Equipment umum
        $defaultHeavy = [
            'Dump Truck',
            'Excavator',
            'Wheel Loader',
            'Motor Grader',
            'Truk Mixer',
            'Water Truck',
            'Forklift',
            'Crane Truck',
            'Crawler Crane',
            'Concrete Pump Truck',
        ];
        foreach ($defaultHeavy as $dh) {
            $scopes[$dh] = "Heavy: {$dh}";
        }

        // Sinkronisasi kategori Heavy Equipment dari data master QMA
        try {
            $heavyCategories = Vehicle::unitSupportCategories()
                ->orderBy('unit_category')
                ->get();

            foreach ($heavyCategories as $cat) {
                $key = $cat->unit_category ?: $cat->brand_type;
                if ($key) {
                    $codeSuffix = !empty($cat->code) ? " ({$cat->code})" : '';
                    $scopes[$key] = "Heavy: {$key}{$codeSuffix}";
                }
            }
        } catch (\Throwable $e) {
            // Fallback saat migrasi awal jika tabel belum siap
        }

        // Dukungan fallback untuk cakupan legacy
        if (!isset($scopes[self::SCOPE_UNIT_SUPPORT_MOBILE])) {
            $scopes[self::SCOPE_UNIT_SUPPORT_MOBILE] = 'Heavy Mobile / Dump Truck (Legacy)';
        }
        if (!isset($scopes[self::SCOPE_UNIT_SUPPORT_HEAVY])) {
            $scopes[self::SCOPE_UNIT_SUPPORT_HEAVY] = 'Alat Berat Operasional (Legacy)';
        }

        return $scopes;
    }

    protected $fillable = [
        'code',
        'name',
        'category_scope',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function employeeSimpers(): HasMany
    {
        return $this->hasMany(EmployeeSimper::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_simpers')
            ->withPivot(['simper_number', 'expired_at', 'status', 'notes'])
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
