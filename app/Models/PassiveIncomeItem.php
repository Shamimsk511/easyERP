<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class PassiveIncomeItem extends Model
{
    use SoftDeletes;

    protected $table = 'passive_income_items';

    protected $fillable = [
        'name',
        'account_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLabour(Builder $query): Builder
    {
        return $query->where('name', 'like', '%labour%')
            ->orWhere('name', 'like', '%labor%')
            ->orWhere('name', 'like', '%loading%');
    }

    public function scopeTransportation(Builder $query): Builder
    {
        return $query->where('name', 'like', '%transport%')
            ->orWhere('name', 'like', '%delivery%')
            ->orWhere('name', 'like', '%freight%');
    }

    // Static helpers for commonly used items
    public static function getLabourChargesAccount(): ?Account
    {
        $item = static::active()
            ->where('name', 'Labour Charges')
            ->first();
        
        return $item?->account ?? Account::where('code', '4510')->first();
    }

    public static function getTransportationAccount(): ?Account
    {
        $item = static::active()
            ->where('name', 'Transportation Charges')
            ->first();
        
        return $item?->account ?? Account::where('code', '4520')->first();
    }

    /**
     * Get all active items for Select2 dropdown
     */
    public static function getForDropdown(): array
    {
        return static::active()
            ->with('account:id,name,code')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'text' => $item->name,
                'account_id' => $item->account_id,
                'account_name' => $item->account?->name,
                'account_code' => $item->account?->code,
            ])
            ->toArray();
    }
}