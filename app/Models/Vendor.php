<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_code',
        'name',
        'company_name',
        'email',
        'phone',
        'mobile',
        'address',
        'city',
        'state',
        'postal_code',
        'country',  
        'name',
        'description',
        'ledger_account_id',
        'opening_balance',
        'opening_balance_type',
        'opening_balance_date',
        'is_active', // Add this
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ledger_account_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function transactionEntries(): HasManyThrough
    {
return $this->hasManyThrough(
            TransactionEntry::class,
            Account::class,
            'id',              // Foreign key on accounts table
            'account_id',      // Foreign key on transaction_entries table
            'ledger_account_id', // Local key on vendors table
            'id'               // Local key on accounts table
        );
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
     public function getCurrentBalanceAttribute(): float
    {
        if (!$this->ledger_account_id) {
            return 0;
        }

        $account = Account::find($this->ledger_account_id);
        if (!$account) {
            return 0;
        }

        // Get credits (payments TO vendor - increases liability)
        $credits = $account->transactionEntries()
            ->whereHas('transaction', function ($query) {
                $query->where('type', '!=', 'opening_balance');
            })
            ->where('type', 'credit')
            ->sum('amount');

        // Get debits (payments FROM vendor - decreases liability)
        $debits = $account->transactionEntries()
            ->whereHas('transaction', function ($query) {
                $query->where('type', '!=', 'opening_balance');
            })
            ->where('type', 'debit')
            ->sum('amount');

        $transactionBalance = $credits - $debits;

        // Add opening balance
        if ($this->opening_balance > 0) {
            if ($this->opening_balance_type === 'credit') {
                $balance = $transactionBalance + $this->opening_balance;
            } else {
                $balance = $transactionBalance - $this->opening_balance;
            }
        } else {
            $balance = $transactionBalance;
        }

        return round($balance, 2);
    }
}
