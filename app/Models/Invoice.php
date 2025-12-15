<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'due_date',
        'customer_id',
        'sales_account_id',
        'customer_ledger_account_id',
        'sales_return_account_id',   // Added: For sales returns
        'transaction_id',            // Added: Link to accounting transaction
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'total_paid',
        'delivery_status',
        'status',                    // Added: draft, posted, cancelled
        'outstanding_at_creation',
        'internal_notes',
        'customer_notes',
        'created_by',                // Added: Audit field
        'updated_by',                // Added: Audit field
        'deleted_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'outstanding_at_creation' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function salesAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'sales_account_id');
    }

    public function customerLedgerAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'customer_ledger_account_id');
    }

    public function salesReturnAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'sales_return_account_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get all transactions linked to this invoice (polymorphic)
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'source');
    }

    /**
     * Accessors
     */
    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->total_paid;
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->outstanding_balance <= 0;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->is_paid;
    }

    /**
     * Record a payment against this invoice
     */
    public function recordPayment(float $amount): void
    {
        $this->total_paid = (float) $this->total_paid + $amount;
        $this->save();
    }

    /**
     * Update delivery status based on items
     */
    public function updateDeliveryStatus(): void
    {
        $totalQty = $this->items()->sum('quantity');
        $deliveredQty = $this->items()->sum('delivered_quantity');

        if ($deliveredQty <= 0) {
            $this->delivery_status = 'pending';
        } elseif ($deliveredQty >= $totalQty) {
            $this->delivery_status = 'delivered';
        } else {
            $this->delivery_status = 'partial';
        }

        $this->save();
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('delivery_status', 'pending');
    }

    public function scopePartial($query)
    {
        return $query->where('delivery_status', 'partial');
    }

    public function scopeDelivered($query)
    {
        return $query->where('delivery_status', 'delivered');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereRaw('total_amount > total_paid');
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->whereRaw('total_amount > total_paid');
    }
}