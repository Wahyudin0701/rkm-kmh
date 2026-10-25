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
