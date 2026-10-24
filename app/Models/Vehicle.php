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
        'site_status',
        'location_updated_at',
        'location_updated_by',
        'location_note',
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
        'location_updated_at' => 'datetime',
    ];

    protected $appends = [
        'photo_url',
        'site_status_label',
    ];

    public function isInsideSite(): bool
    {
        return ($this->site_status ?? 'inside_site') === 'inside_site';
    }

    public function isOutsideSite(): bool
    {
        return ($this->site_status ?? 'inside_site') === 'outside_site';
    }

    public function getSiteStatusLabelAttribute(): string
    {
        return $this->isOutsideSite() ? 'Di Luar Site' : 'Di Dalam Site';
    }

    public function isLightVehicle(): bool
    {
        return $this->category === 'light_vehicle';
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }

        if (str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://')) {
            return $this->photo_path;
        }

        return asset('storage/' . ltrim($this->photo_path, '/'));
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

    /**
     * Scope untuk seluruh unit operasional yang dapat dilacak posisinya:
     * - Seluruh Light Vehicle
     * - Seluruh Heavy Equipment fisik (anak kategori) ATAU kategori tunggal yang belum memiliki anak unit
     */
    public function scopeTrackableUnits(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('category', 'light_vehicle')
              ->orWhere(function (Builder $sq) {
                  $sq->where('category', 'unit_support')
                     ->where(function (Builder $sub) {
                         $sub->whereNotNull('parent_id')
                             ->orWhereDoesntHave('units');
                     });
              });
        });
    }

    public function scopeInsideSite(Builder $query): Builder
    {
        return $query->where('site_status', 'inside_site');
    }

    public function scopeOutsideSite(Builder $query): Builder
    {
        return $query->where('site_status', 'outside_site');
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

    public function locationHistories(): HasMany
    {
        return $this->hasMany(VehicleLocationHistory::class)->orderByDesc('recorded_at');
    }

    public function latestLocationHistory(): HasOne
    {
        return $this->hasOne(VehicleLocationHistory::class)->latestOfMany('recorded_at');
    }

    public function supportTimesheets(): HasMany
    {
        return $this->hasMany(UnitSupportTimesheet::class)->orderByDesc('date')->orderByDesc('id');
    }

    public function latestSupportTimesheet(): HasOne
    {
        return $this->hasOne(UnitSupportTimesheet::class)->latestOfMany('date');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(UnitTransfer::class, 'vehicle_id');
    }

    public function transporterTrips(): HasMany
    {
        return $this->hasMany(UnitTransfer::class, 'transporter_vehicle_id');
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
