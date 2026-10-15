<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class P2hTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'file_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(P2hTemplateItem::class, 'template_id')->orderBy('sequence');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'p2h_template_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(P2hRecord::class, 'template_id');
    }

    /**
     * Ambil item yang dikelompokkan berdasarkan section.
     */
    public function getGroupedItemsAttribute(): array
    {
        $grouped = [];
        foreach ($this->items as $item) {
            $grouped[$item->section][] = $item;
        }
        return $grouped;
    }
}
