<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AdminActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_username',
        'action_type',
        'module',
        'target_type',
        'target_id',
        'target_name',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper statis untuk merekam log aktivitas Super Admin secara otomatis.
     */
    public static function record(
        string $actionType,
        string $module,
        string $description,
        ?string $targetType = null,
        ?int $targetId = null,
        ?string $targetName = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        $actor = Auth::user();

        return self::create([
            'user_id' => $actor?->id,
            'user_name' => $actor?->name ?? 'Super Admin',
            'user_username' => $actor?->username ?? 'superadmin',
            'action_type' => $actionType,
            'module' => $module,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_name' => $targetName,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
