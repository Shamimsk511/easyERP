<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{
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

    // Relationships
    public function parentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_account_id');
    }

    public function childAccounts(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_account_id');
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

    public function isDebitAccount(): bool
    {
        return in_array($this->type, ['asset', 'expense']);
    }

    public function isCreditAccount(): bool
    {
        return in_array($this->type, ['liability', 'equity', 'income']);
    }
}
