<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_code',
        'name',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'customer_group_id',
        'ledger_account_id',
        'opening_balance',
        'opening_balance_type',
        'opening_balance_date',
        'credit_limit',
        'credit_period_days',
        'current_due_date',
        'total_extended_days',
        'extension_count',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'opening_balance_date' => 'date',
        'current_due_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ledger_account_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderBy('invoice_date', 'desc');
    }

    /**
     * Fixed: Get payments through invoices (HasManyThrough)
     */
    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            InvoicePayment::class,
            Invoice::class,
            'customer_id',    // FK on invoices table
            'invoice_id',     // FK on invoice_payments table
            'id',             // Local key on customers table
            'id'              // Local key on invoices table
        )->orderBy('invoice_payments.payment_date', 'desc');
    }

    /**
     * Customer ledger transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerLedgerTransaction::class)->orderBy('transaction_date', 'desc');
    }

    /**
     * Alias for transactions
     */
    public function ledgerTransactions(): HasMany
    {
        return $this->transactions();
    }

    public function dueExtensions(): HasMany
    {
        return $this->hasMany(CustomerDueExtension::class)->orderBy('created_at', 'desc');
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(CustomerPriceHistory::class);
    }

    public function deliveries(): HasManyThrough
    {
        return $this->hasManyThrough(
            Delivery::class,
            Invoice::class,
            'customer_id',
            'invoice_id'
        )->orderBy('deliveries.delivery_date', 'desc');
    }

    /**
     * Get current balance from ledger transactions
     * Positive = Customer owes us (Receivable)
     * Negative = We owe customer (Advance received)
     */
    public function getCurrentBalanceAttribute(): float
    {
        $transactionBalance = $this->transactions()->sum(DB::raw('debit - credit'));
        
        $openingBalance = $this->opening_balance_type === 'debit'
            ? (float) $this->opening_balance
            : -(float) $this->opening_balance;

        return $openingBalance + $transactionBalance;
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        $balance = $this->current_balance;
        $amount = number_format(abs($balance), 2);

        if ($balance > 0) {
            return "৳ {$amount} Dr";
        } elseif ($balance < 0) {
            return "৳ {$amount} Cr";
        }

        return "৳ 0.00";
    }

    /**
     * Check if customer is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->current_due_date 
            && $this->current_due_date->isPast() 
            && $this->current_balance > 0;
    }

    /**
     * Get total sales to this customer
     */
    public function getTotalSalesAttribute(): float
    {
        return (float) $this->invoices()
            ->whereNull('deleted_at')
            ->sum('total_amount');
    }

    /**
     * Get total payments received from this customer
     */
    public function getTotalPaymentsAttribute(): float
    {
        return (float) $this->payments()
            ->whereNull('invoice_payments.deleted_at')
            ->sum('invoice_payments.amount');
    }

    /**
     * Get last purchase rate for a product
     */
    public function getLastRateForProduct(int $productId): ?float
    {
        $history = $this->priceHistory()
            ->where('product_id', $productId)
            ->first();

        return $history?->rate;
    }

    /**
     * Check if credit limit exceeded
     */
    public function isCreditLimitExceeded(): bool
    {
        if ($this->credit_limit <= 0) {
            return false;
        }

        return $this->current_balance > $this->credit_limit;
    }

    /**
     * Get available credit
     */
    public function getAvailableCreditAttribute(): float
    {
        if ($this->credit_limit <= 0) {
            return 0;
        }

        return max(0, $this->credit_limit - $this->current_balance);
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

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('current_due_date')
            ->whereDate('current_due_date', '<', now());
    }

    public function scopeWithOutstandingBalance($query)
    {
        return $query->whereHas('transactions', function ($q) {
            $q->havingRaw('SUM(debit - credit) > 0');
        });
    }

    public function scopeInGroup($query, int $groupId)
    {
        return $query->where('customer_group_id', $groupId);
    }
}