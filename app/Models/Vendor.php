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
        'description',
        'ledger_account_id',
        'opening_balance',
        'opening_balance_type',
        'opening_balance_date',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate vendor_code on creating
        static::creating(function ($vendor) {
            if (empty($vendor->vendor_code)) {
                $lastVendor = static::withTrashed()->latest('id')->first();
                $nextId = $lastVendor ? $lastVendor->id + 1 : 1;
                $vendor->vendor_code = 'V' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
            }
        });
    }

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

    /**
     * Get all transaction entries through the ledger account
     */
    public function transactionEntries(): HasManyThrough
    {
        return $this->hasManyThrough(
            TransactionEntry::class,
            Account::class,
            'id',                   // FK on accounts table
            'account_id',           // FK on transaction_entries table
            'ledger_account_id',    // Local key on vendors table
            'id'                    // Local key on accounts table
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

    public function scopeWithBalance($query)
    {
        return $query->withCount([
            'transactionEntries as debit_total' => function ($q) {
                $q->where('type', 'debit')->select(\DB::raw('COALESCE(SUM(amount), 0)'));
            },
            'transactionEntries as credit_total' => function ($q) {
                $q->where('type', 'credit')->select(\DB::raw('COALESCE(SUM(amount), 0)'));
            },
        ]);
    }

    /**
     * Get current balance from ledger
     * Positive = We owe vendor (Payable)
     * Negative = Vendor owes us (Advance paid)
     */
    public function getCurrentBalanceAttribute(): float
    {
        if (!$this->ledger_account_id) {
            return 0;
        }

        $account = $this->ledgerAccount;
        if (!$account) {
            return 0;
        }

        $debits = $account->transactionEntries()
            ->where('type', 'debit')
            ->sum('amount');

        $credits = $account->transactionEntries()
            ->where('type', 'credit')
            ->sum('amount');

        // Vendor accounts are liability type (Credit increases balance)
        $balance = $account->opening_balance + $credits - $debits;

        return (float) $balance;
    }

    /**
     * Get formatted balance with type indicator
     */
    public function getFormattedBalanceAttribute(): string
    {
        $balance = $this->current_balance;
        $amount = number_format(abs($balance), 2);

        if ($balance > 0) {
            return "৳ {$amount} (Payable)";
        } elseif ($balance < 0) {
            return "৳ {$amount} (Advance)";
        }

        return "৳ 0.00";
    }

    /**
     * Check if vendor has outstanding balance
     */
    public function hasOutstanding(): bool
    {
        return $this->current_balance > 0;
    }

    /**
     * Get total purchase amount
     */
    public function getTotalPurchasesAttribute(): float
    {
        return (float) $this->purchaseOrders()
            ->where('status', 'received')
            ->sum('total_amount');
    }
}