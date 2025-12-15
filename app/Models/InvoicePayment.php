<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoicePayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_number',
        'invoice_id',
        'payment_date',
        'amount',
        'payment_method',
        'account_id',
        'transaction_id',       // Fixed: Removed duplicate
        'cheque_number',
        'cheque_date',
        'bank_name',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'cheque_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get customer through invoice
     */
    public function getCustomerAttribute()
    {
        return $this->invoice?->customer;
    }

    /**
     * Get payment summary
     */
    public function getSummary(): array
    {
        return [
            'payment_number' => $this->payment_number,
            'invoice_number' => $this->invoice->invoice_number ?? 'N/A',
            'customer_name' => $this->invoice->customer->name ?? 'N/A',
            'amount' => (float) $this->amount,
            'payment_date' => $this->payment_date->format('Y-m-d'),
            'method' => $this->payment_method,
            'account' => $this->account->name ?? 'N/A',
        ];
    }

    /**
     * Get formatted payment method
     */
    public function getMethodDisplayAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->payment_method));
    }

    /**
     * Check if payment is by cheque
     */
    public function getIsChequeAttribute(): bool
    {
        return strtolower($this->payment_method) === 'cheque';
    }

    /**
     * Scopes
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeChequePayments($query)
    {
        return $query->where('payment_method', 'cheque');
    }

    public function scopeCashPayments($query)
    {
        return $query->where('payment_method', 'cash');
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Generate payment number
     */
    public static function generatePaymentNumber(): string
    {
        $prefix = 'PAY-' . date('Ym') . '-';
        $lastPayment = static::where('payment_number', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }
}