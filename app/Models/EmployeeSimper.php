<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSimper extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'employee_id',
        'simper_type_id',
        'simper_number',
        'expired_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'expired_at' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function simperType(): BelongsTo
    {
        return $this->belongsTo(SimperType::class);
    }

    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE || ! $this->expired_at) {
            return false;
        }

        return $this->expired_at->isFuture() || $this->expired_at->isToday();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeValid(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();

        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('expired_at')
            ->where('expired_at', '>=', $today);
    }
}
