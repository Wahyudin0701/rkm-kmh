<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimperType extends Model
{
    use HasFactory, SoftDeletes;

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

    protected $appends = [
        'scopes',
        'category_scope_display',
    ];

    /**
     * Dapatkan seluruh cakupan kategori dalam bentuk array terurai.
     *
     * @return array<string>
     */
    public function getScopesAttribute(): array
    {
        $raw = $this->category_scope;

        if (empty($raw)) {
            return [self::SCOPE_ALL];
        }

        if (is_array($raw)) {
            return array_values(array_unique($raw));
        }

        if (is_string($raw)) {
            $trimmed = trim($raw);
            if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    return array_values(array_unique($decoded));
                }
            }
            if (str_contains($trimmed, ',')) {
                return array_values(array_filter(array_map('trim', explode(',', $trimmed))));
            }
            return [$trimmed];
        }

        return [(string) $raw];
    }

    /**
     * Cek apakah jenis SIMPER ini mencakup scope target tertentu.
     */
    public function coversScope(string $targetScope): bool
    {
        $scopes = $this->scopes;

        // Universal scope mencakup semua kategori
        if (in_array(self::SCOPE_ALL, $scopes, true) || in_array('all', $scopes, true)) {
            return true;
        }

        if (in_array($targetScope, $scopes, true)) {
            return true;
        }

        // Bandingkan case-insensitive
        foreach ($scopes as $s) {
            if (strcasecmp($s, $targetScope) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Teks ringkas representasi cakupan kendaraan untuk badge UI.
     */
    public function getCategoryScopeDisplayAttribute(): string
    {
        $scopes = $this->scopes;

        if (empty($scopes) || in_array(self::SCOPE_ALL, $scopes, true)) {
            return 'Semua Kategori (Universal)';
        }

        if (count($scopes) === 1 && $scopes[0] === self::SCOPE_LIGHT_VEHICLE) {
            return 'Light Vehicle (LV)';
        }

        $formatted = array_map(function ($s) {
            if ($s === self::SCOPE_LIGHT_VEHICLE) return 'LV';
            if ($s === self::SCOPE_ALL) return 'Universal';
            return str_replace('_', ' ', $s);
        }, $scopes);

        return implode(', ', $formatted);
    }

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
