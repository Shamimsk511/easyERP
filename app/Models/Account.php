<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Account extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'parent_account_id',
        'opening_balance',
        'opening_balance_date',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
        'is_active' => 'boolean',
    ];
    
    // Append computed attributes to arrays/JSON
    protected $appends = ['current_balance'];

    // Relationships
    public function parentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_account_id');
    }

    public function childAccounts(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_account_id');
    }
    
    // Recursive relationship for tree view (with eager loading)
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_account_id')->with('children');
    }

    public function transactionEntries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    // Helper methods
    public function getCurrentBalance()
    {
        $debits = $this->transactionEntries()
            ->where('type', 'debit')
            ->sum('amount');
        
        $credits = $this->transactionEntries()
            ->where('type', 'credit')
            ->sum('amount');

        // Calculate based on account type
        if (in_array($this->type, ['asset', 'expense'])) {
            return $this->opening_balance + $debits - $credits;
        } else {
            return $this->opening_balance + $credits - $debits;
        }
    }
    
    // Accessor for current_balance attribute (Laravel 11+ syntax)
    protected function currentBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getCurrentBalance(),
        );
    }

    public function isDebitAccount(): bool
    {
        return in_array($this->type, ['asset', 'expense']);
    }

    public function isCreditAccount(): bool
    {
        return in_array($this->type, ['liability', 'equity', 'income']);
    }
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInventoryAccounts($query)
    {
        return $query->where('name', 'LIKE', '%Inventory%')->where('type', 'asset');
    }

}
