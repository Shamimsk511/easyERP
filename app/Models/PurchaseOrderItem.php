<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'unit_id',              // Added: Support for alternative units
        'description',          // Added: For non-product items
        'quantity',
        'received_quantity',    // Added: For partial receiving
        'rate',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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
     * Get remaining quantity to receive
     */
    public function getRemainingQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity - (float) $this->received_quantity);
    }

    /**
     * Check if fully received
     */
    public function isFullyReceived(): bool
    {
        return (float) $this->received_quantity >= (float) $this->quantity;
    }

    /**
     * Record receipt of items
     */
    public function recordReceipt(float $qty): void
    {
        $this->received_quantity = (float) $this->received_quantity + $qty;
        $this->save();
    }

    /**
     * Reverse receipt (for deletions)
     */
    public function reverseReceipt(float $qty): void
    {
        $this->received_quantity = max(0, (float) $this->received_quantity - $qty);
        $this->save();
    }

    /**
     * Get display unit (alternative or base)
     */
    public function getDisplayUnitAttribute(): string
    {
        if ($this->unit) {
            return $this->unit->symbol;
        }

        return $this->product?->baseUnit?->symbol ?? 'Unit';
    }

    /**
     * Calculate line total
     */
    public function calculateAmount(): float
    {
        return (float) $this->quantity * (float) $this->rate;
    }

    /**
     * Update amount based on quantity and rate
     */
    public function recalculateAmount(): void
    {
        $this->amount = $this->calculateAmount();
        $this->save();
    }
}