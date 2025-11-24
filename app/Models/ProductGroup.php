<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'parent_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get parent group
     */
    public function parent()
    {
        return $this->belongsTo(ProductGroup::class, 'parent_id');
    }

    /**
     * Get child groups (one level)
     */
    public function children()
    {
        return $this->hasMany(ProductGroup::class, 'parent_id');
    }

    /**
     * Get child groups recursively
     */
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    /**
     * Get all parent groups
     */
    public function parents()
    {
        return $this->belongsTo(ProductGroup::class, 'parent_id')->with('parents');
    }

    /**
     * Get products in this group
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'product_group_id');
    }

    /**
     * Scope for root groups
     */
    public function scopeRootGroups($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for active groups
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get full hierarchy path
     */
    public function getFullPathAttribute()
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }

    /**
     * Check if group has children
     */
    public function hasChildren()
    {
        return $this->children()->exists();
    }

    /**
     * Check if group has products
     */
    public function hasProducts()
    {
        return $this->products()->exists();
    }

    /**
     * Check if group can be deleted
     */
    public function canBeDeleted()
    {
        return !$this->hasChildren() && !$this->hasProducts();
    }
}
