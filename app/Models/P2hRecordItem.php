<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class P2hRecordItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'p2h_record_id',
        'template_item_id',
        'section',
        'title',
        'question_type',
        'condition_status',
        'answer_value',
        'notes',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(P2hRecord::class, 'p2h_record_id');
    }

    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(P2hTemplateItem::class, 'template_item_id');
    }

    public function isBaik(): bool
    {
        return strtolower((string)$this->condition_status) === 'baik';
    }

    public function isRusak(): bool
    {
        return strtolower((string)$this->condition_status) === 'rusak';
    }
}
