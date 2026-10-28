<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class P2hTemplateItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'template_id',
        'section',
        'title',
        'question_type',
        'sequence',
    ];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(P2hTemplate::class, 'template_id');
    }

    /**
     * Konversi question_type (termasuk nilai legacy) ke label standar resmi.
     */
    public function getQuestionTypeLabelAttribute(): string
    {
        return match(strtolower(trim($this->question_type))) {
            'multiple lines text box', 'textarea' => 'Multiple Lines Text Box',
            'single line text box', 'text' => 'Single Line Text Box',
            'numerical value', 'number' => 'Numerical Value',
            'date' => 'Date',
            'datetime' => 'Datetime',
            'multiple choice: only one answer', 'choice', 'condition' => 'Multiple choice: only one answer',
            'multiple choice: multiple answers allowed', 'multiple_choice' => 'Multiple choice: multiple answers allowed',
            'matrix' => 'Matrix',
            default => $this->question_type,
        };
    }
}
