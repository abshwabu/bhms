<?php

namespace App\Domain\Clinical\Models;

use App\Domain\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

class Icd10Code extends BaseModel
{
    protected $table = 'icd10_codes';

    protected $fillable = [
        'code',
        'description',
        'category',
        'chapter',
        'is_billable',
        'is_active',
    ];

    protected $casts = [
        'is_billable' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scope query to active codes.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query by search term (code or description).
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function ($q) use ($term) {
            $q->where('code', 'ILIKE', "{$term}%")
              ->orWhere('description', 'ILIKE', "%{$term}%")
              ->orWhere('category', 'ILIKE', "%{$term}%");
        });
    }
}
