<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'sales_return_account_id',
        'labour_account_id',
        'transportation_account_id',
        'transaction_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'labour_amount',
        'transportation_amount',
        'round_off_amount',
        'total_amount',
        'total_paid',
        'delivery_status',
        'status',
        'outstanding_at_creation',
        'internal_notes',
        'customer_notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'labour_amount' => 'decimal:2',
        'transportation_amount' => 'decimal:2',
        'round_off_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'outstanding_at_creation' => 'decimal:2',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function productItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->where('item_type', 'product');
    }

    public function passiveItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->where('item_type', 'passive_income');
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

    public function labourAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'labour_account_id');
    }

    public function transportationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'transportation_account_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getOutstandingBalanceAttribute(): float
    {
        return max(0, $this->total_amount - $this->total_paid);
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->outstanding_balance <= 0;
    }

    public function getAdditionalChargesAttribute(): float
    {
        return ($this->labour_amount ?? 0) + ($this->transportation_amount ?? 0);
    }

    // Methods
    public function recordPayment(float $amount): void
    {
        $this->total_paid = min($this->total_amount, $this->total_paid + $amount);
        $this->save();
    }

    public function reversePayment(float $amount): void
    {
        $this->total_paid = max(0, $this->total_paid - $amount);
        $this->save();
    }

    public function updateDeliveryStatus(): void
    {
        $totalQty = $this->productItems()->sum('quantity');
        $deliveredQty = $this->productItems()->sum('delivered_quantity');

        if ($deliveredQty <= 0) {
            $this->delivery_status = 'pending';
        } elseif ($deliveredQty >= $totalQty) {
            $this->delivery_status = 'delivered';
        } else {
            $this->delivery_status = 'partial';
        }
        $this->save();
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV-';
        $year = now()->format('Y');
        $lastInvoice = static::withTrashed()
            ->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $nextNumber = $lastInvoice
            ? (int) substr($lastInvoice->invoice_number, -5) + 1
            : 1;

        return $prefix . $year . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}