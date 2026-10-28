<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitSupportTimesheet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'timesheet_no',
        'vehicle_id',
        'operator_id',
        'date',
        'shift',
        'meter_type',
        'start_time',
        'end_time',
        'start_meter',
        'end_meter',
        'operating_hours',
        'standby_hours',
        'breakdown_hours',
        'location',
        'activity_type',
        'task_description',
        'fuel_liters',
        'status',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'start_meter' => 'decimal:2',
        'end_meter' => 'decimal:2',
        'operating_hours' => 'decimal:2',
        'standby_hours' => 'decimal:2',
        'breakdown_hours' => 'decimal:2',
        'fuel_liters' => 'decimal:2',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Generate nomor timesheet unik harian (Format: TS-US-YYYYMMDD-0001).
     */
    public static function generateTimesheetNo(?Carbon $date = null): string
    {
        $d = $date ?: Carbon::today();
        $dateStr = $d->format('Ymd');
        $prefix = "TS-US-{$dateStr}-";

        $last = self::where('timesheet_no', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last->timesheet_no, $matches)) {
            $seq = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
