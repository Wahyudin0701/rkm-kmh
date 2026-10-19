<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'category',
        'parent_id',
        'p2h_template_id',
        'brand_type',
        'license_plate',
        'serial_number',
        'ownership',
        'division',
        'status',
        'current_km',
        'photo_path',
        'unit_category',
        'unit_type',
        'brand',
        'model_number',
        'production_year',
        'location',
        'fuel_consumption',
        'silo_document',
        'stnk_document',
        'pajak_document',
        'kir_document',
    ];

    protected $casts = [
        'current_km' => 'decimal:2',
        'fuel_consumption' => 'decimal:2',
        'production_year' => 'integer',
        'parent_id' => 'integer',
    ];

    public function isLightVehicle(): bool
    {
        return $this->category === 'light_vehicle';
    }

    public function isUnitSupport(): bool
    {
        return $this->category === 'unit_support';
    }

    public function isSupportCategory(): bool
    {
        return $this->isUnitSupport() && is_null($this->parent_id);
    }

    public function isSupportUnit(): bool
    {
        return $this->isUnitSupport() && !is_null($this->parent_id);
    }

    public function scopeLightVehicles(Builder $query): Builder
    {
        return $query->where('category', 'light_vehicle');
    }

    public function scopeUnitSupports(Builder $query): Builder
    {
        return $query->where('category', 'unit_support');
    }

    public function scopeUnitSupportCategories(Builder $query): Builder
    {
        return $query->where('category', 'unit_support')->whereNull('parent_id');
    }

    public function scopeUnitSupportPhysical(Builder $query): Builder
    {
        return $query->where('category', 'unit_support')->whereNotNull('parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'parent_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'parent_id')->orderBy('code');
    }

    /**
     * Hitung nomor urut 3 digit berikutnya untuk unit fisik kategori ini.
     */
    public function getNextUnitNumber(): string
    {
        $existingCodes = $this->units()->pluck('code')->all();
        $maxNum = 0;
        foreach ($existingCodes as $c) {
            if (preg_match('/' . preg_quote($this->code, '/') . '-(\d+)$/i', $c, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }
        return str_pad((string)($maxNum + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Hasilkan saran kode unit unik berikutnya (misal: BL01-001).
     */
    public function getNextUnitCode(): string
    {
        return $this->code . '-' . $this->getNextUnitNumber();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(VehicleActivity::class);
    }

    public function p2hTemplate(): BelongsTo
    {
        return $this->belongsTo(P2hTemplate::class, 'p2h_template_id');
    }

    public function p2hRecords(): HasMany
    {
        return $this->hasMany(P2hRecord::class, 'vehicle_id')->orderByDesc('inspected_at');
    }

    public function latestP2h(): HasOne
    {
        return $this->hasOne(P2hRecord::class, 'vehicle_id')->latestOfMany('inspected_at');
    }

    /**
     * Aktivitas gerbang terakhir dari kendaraan ini.
     */
    public function latestActivity(): HasOne
    {
        return $this->hasOne(VehicleActivity::class)->latestOfMany('recorded_at');
    }

    /**
     * Tentukan arah gerbang berikutnya yang sah:
     * - 'ANY' jika belum pernah ada riwayat (kendaraan baru)
     * - 'in' jika aktivitas terakhir adalah 'out'
     * - 'out' jika aktivitas terakhir adalah 'in'
     */
    public function determineNextAllowedDirection(): string
    {
        $last = $this->activities()
            ->whereNotIn('validation_status', ['cancelled', 'rejected'])
            ->latest('recorded_at')
            ->first();

        if (!$last) {
            return 'ANY';
        }

        return ($last->direction === 'out') ? 'in' : 'out';
    }
}
