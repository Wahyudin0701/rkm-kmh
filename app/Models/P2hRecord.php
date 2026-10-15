<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class P2hRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_no',
        'vehicle_id',
        'employee_id',
        'template_id',
        'shift',
        'start_hm',
        'start_km',
        'start_time',
        'stop_time',
        'summary_condition',
        'defect_count',
        'general_notes',
        'validation_status',
        'validated_by',
        'validated_at',
        'validation_notes',
        'inspected_at',
    ];

    protected $casts = [
        'start_hm' => 'decimal:2',
        'start_km' => 'decimal:2',
        'defect_count' => 'integer',
        'start_time' => 'datetime',
        'stop_time' => 'datetime',
        'validated_at' => 'datetime',
        'inspected_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(P2hTemplate::class, 'template_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(P2hRecordItem::class, 'p2h_record_id');
    }

    public function isPending(): bool
    {
        return $this->validation_status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->validation_status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->validation_status === 'rejected';
    }

    public function hasDefects(): bool
    {
        return $this->summary_condition === 'has_defect' || $this->defect_count > 0;
    }
}
