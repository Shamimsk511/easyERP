<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'symbol',
        'type',
        'is_base_unit',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active units
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for base units only
     */
    public function scopeBaseUnits($query)
    {
        return $query->where('is_base_unit', true);
    }

    /**
     * Get products using this unit
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_units')
                    ->withPivot('conversion_factor', 'is_default_unit', 'is_purchase_unit', 'is_sales_unit')
                    ->withTimestamps();
    }

    /**
     * Check if unit is in use
     */
    public function isInUse(): bool
    {
        // Uncomment when Product model is created
        // return $this->products()->exists();
        return false;
    }
}
