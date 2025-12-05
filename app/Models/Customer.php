<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'notes'
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'opening_balance_date' => 'date',
        'current_due_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function group()
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function ledgerAccount()
    {
        return $this->belongsTo(Account::class, 'ledger_account_id');
    }
public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderBy('invoice_date', 'desc');
    }
  public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('payment_date', 'desc');
    }  
    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerLedgerTransaction::class)->orderBy('transaction_date', 'desc');
    }
public function ledgerTransactions(): HasMany
    {
        return $this->hasMany(CustomerLedgerTransaction::class)->orderBy('transaction_date', 'desc');
    }
    public function dueExtensions(): HasMany
    {
        return $this->hasMany(CustomerDueExtension::class)->orderBy('created_at', 'desc');
    }
 public function priceHistory(): HasMany
    {
        return $this->hasMany(CustomerPriceHistory::class);
    }
    // Calculate current balance
    public function getCurrentBalanceAttribute()
    {
        $transactions = $this->transactions()->sum(DB::raw('debit - credit'));
        $openingBalance = $this->opening_balance_type === 'debit' 
            ? $this->opening_balance 
            : -$this->opening_balance;
        
        return $openingBalance + $transactions;
    }

    // Check if overdue
public function getIsOverdueAttribute()
{
    if (!$this->current_due_date) {
        return false;
    }
    
    return $this->current_due_date->isPast() && $this->current_balance > 0;
}

    // Get overdue days
public function getOverdueDaysAttribute()
{
    if (!$this->current_due_date) {
        return 0;
    }
    
    // Check if actually overdue (due date is in the past)
    if ($this->current_due_date->isFuture()) {
        return 0; // Not overdue yet
    }
    
    // Only calculate if there's an outstanding balance
    if ($this->current_balance <= 0) {
        return 0; // No outstanding balance
    }
    
    // Calculate days overdue (absolute value, rounded)
    return (int) abs($this->current_due_date->diffInDays(now(), false));
}



    // Check credit limit
    public function isWithinCreditLimit($additionalAmount = 0)
    {
        if ($this->credit_limit <= 0) {
            return true; // No limit set
        }
        
        $currentBalance = $this->current_balance + $additionalAmount;
        return $currentBalance <= $this->credit_limit;
    }

    // Generate unique customer code
    public static function generateCustomerCode()
    {
        $lastCustomer = self::withTrashed()->orderBy('id', 'desc')->first();
        $nextId = $lastCustomer ? $lastCustomer->id + 1 : 1;
        return 'CUST' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }

    // Extend due date
    public function extendDueDate($newDueDate, $reason = null)
    {
        $originalDueDate = $this->current_due_date;
        $daysExtended = $originalDueDate->diffInDays($newDueDate);

        CustomerDueExtension::create([
            'customer_id' => $this->id,
            'original_due_date' => $originalDueDate,
            'extended_due_date' => $newDueDate,
            'days_extended' => $daysExtended,
            'reason' => $reason,
            'extended_by' => Auth::id(),
        ]);

        $this->update([
            'current_due_date' => $newDueDate,
            'total_extended_days' => $this->total_extended_days + $daysExtended,
            'extension_count' => $this->extension_count + 1,
        ]);
    }

    // Boot method to auto-generate code
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $customer->customer_code = self::generateCustomerCode();
            }
        });
    }
    public function customers()
{
    return $this->hasMany(Customer::class, 'ledger_account_id');
}

/**
 * Record a sale transaction
 */
public function recordSale($amount, $date, $description, $otherAccountId, $reference = null)
{
    DB::beginTransaction();
    try {
        // Create main transaction
        $transaction = Transaction::create([
            'date' => $date,
            'reference' => $reference ?? 'INV-' . time(),
            'description' => $description,
            'status' => 'posted',
        ]);

        // Debit customer account (increase receivable)
        $transaction->entries()->create([
            'account_id' => $this->ledger_account_id,
            'amount' => $amount,
            'type' => 'debit',
            'memo' => 'Sale to ' . $this->name,
        ]);

        // Credit sales/other account
        $transaction->entries()->create([
            'account_id' => $otherAccountId,
            'amount' => $amount,
            'type' => 'credit',
            'memo' => 'Sale from ' . $this->name,
        ]);

        DB::commit();
        return $transaction;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

/**
 * Record a payment received
 */
public function recordPayment($amount, $date, $description, $cashAccountId, $reference = null)
{
    DB::beginTransaction();
    try {
        // Create main transaction
        $transaction = Transaction::create([
            'date' => $date,
            'reference' => $reference ?? 'RCP-' . time(),
            'description' => $description,
            'status' => 'posted',
        ]);

        // Debit cash account
        $transaction->entries()->create([
            'account_id' => $cashAccountId,
            'amount' => $amount,
            'type' => 'debit',
            'memo' => 'Payment from ' . $this->name,
        ]);

        // Credit customer account (reduce receivable)
        $transaction->entries()->create([
            'account_id' => $this->ledger_account_id,
            'amount' => $amount,
            'type' => 'credit',
            'memo' => 'Payment received',
        ]);

        DB::commit();
        return $transaction;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

public function transactionEntries()
{
    return $this->ledgerAccount->transactionEntries();
}

/**
 * Get outstanding balance for customer
 */
public function getOutstandingBalance(): float
    {
        if (!$this->ledger_account_id) {
            return 0;
        }

        $account = Account::find($this->ledger_account_id);
        if (!$account) {
            return 0;
        }

        // Get all debits (increases AR)
        $debits = DB::table('transaction_entries as te')
            ->join('transactions as t', 'te.transaction_id', '=', 't.id')
            ->where('te.account_id', $account->id)
            ->where('te.type', 'debit')
            ->whereIn('t.status', ['posted', 'voided']) // Include posted and voided for accuracy
            ->sum('te.amount');

        // Get all credits (decreases AR - payments)
        $credits = DB::table('transaction_entries as te')
            ->join('transactions as t', 'te.transaction_id', '=', 't.id')
            ->where('te.account_id', $account->id)
            ->where('te.type', 'credit')
            ->whereIn('t.status', ['posted', 'voided'])
            ->sum('te.amount');

        // AR is a debit account, so: Opening Balance (debit) + Debits - Credits
        if ($this->opening_balance_type === 'credit') {
            $balance = -$this->opening_balance + $debits - $credits;
        } else {
            $balance = $this->opening_balance + $debits - $credits;
        }

        return round($balance, 2);
    }

public function getBalanceFromInvoices(): float
    {
        // Get all invoices (except deleted)
        $invoiceTotal = DB::table('invoices')
            ->where('customer_id', $this->id)
            ->whereNull('deleted_at')
            ->sum('total_amount');

        // Get all payments (except deleted)
        $paymentTotal = DB::table('invoice_payments')
            ->whereIn('invoice_id', $this->invoices()->pluck('id'))
            ->whereNull('deleted_at')
            ->sum('amount');

        return round($invoiceTotal - $paymentTotal, 2);
    }
    public function getCustomerInfo(): array
    {
        return [
            'id' => $this->id,
            'customer_code' => $this->customer_code,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'outstanding_balance' => $this->getOutstandingBalance(),
            'credit_limit' => $this->credit_limit ?? 0,
            'is_active' => $this->is_active,
        ];
    }


// ===== SCOPES =====
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // ===== BOOT =====


}
