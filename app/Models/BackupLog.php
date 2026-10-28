<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'filename',
        'scope',
        'scope_label',
        'table_count',
        'tables_included',
        'include_media',
        'file_size',
        'file_size_human',
        'status',
    ];

    protected $casts = [
        'tables_included' => 'array',
        'include_media' => 'boolean',
        'file_size' => 'integer',
        'table_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
