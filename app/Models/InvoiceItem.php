<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'item_type',
        'description',
        'unit_id',
        'quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'line_total',
        'rate_given_to_customer',
        'delivered_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'rate_given_to_customer' => 'decimal:2',
        'delivered_quantity' => 'decimal:3',
    ];

    /**
     * Relationships
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get remaining quantity to be delivered
     */
    public function getRemainingQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity - (float) $this->delivered_quantity);
    }

    /**
     * Check if fully delivered
     */
    public function isFullyDelivered(): bool
    {
        return (float) $this->delivered_quantity >= (float) $this->quantity;
    }

      // Accessors
    public function getLineTotalAttribute()
    {
        $baseTotal = $this->quantity * $this->unit_price;
        $discount = ($baseTotal * $this->discount_percent) / 100;
        return $baseTotal - $discount;
    }

    // Methods
    public function recordDelivery($qty)
    {
        $this->delivered_quantity += $qty;
        $this->save();
    }

    public function reverseDelivery($qty)
    {
        $this->delivered_quantity = max(0, $this->delivered_quantity - $qty);
        $this->save();
    }
}