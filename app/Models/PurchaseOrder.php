<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;  // Fixed: SoftDeletes now properly used

    protected $fillable = [
        'vendor_id',
        'purchase_account_id',
        'order_number',
        'status',
        'order_date',
        'received_date',
        'notes',
        'total_amount',
        'transaction_id',
    ];

    protected $casts = [
        'order_date' => 'date',
        'received_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'purchase_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Product movements created by this PO
     */
    public function productMovements(): MorphMany
    {
        return $this->morphMany(ProductMovement::class, 'reference');
    }

    /**
     * Check if PO is pending
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if PO is received
     */
    public function getIsReceivedAttribute(): bool
    {
        return $this->status === 'received';
    }

    /**
     * Check if PO can be edited
     */
    public function getCanEditAttribute(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if all items are fully received
     */
    public function isFullyReceived(): bool
    {
        return $this->items->every(fn($item) => $item->isFullyReceived());
    }

    /**
     * Get total received amount
     */
    public function getTotalReceivedAmountAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return (float) $item->received_quantity * (float) $item->rate;
        });
    }

    /**
     * Mark PO as received
     */
    public function markAsReceived(): void
    {
        $this->update([
            'status' => 'received',
            'received_date' => now(),
        ]);
    }

    /**
     * Recalculate total amount from items
     */
    public function recalculateTotal(): void
    {
        $total = $this->items()->sum('amount');
        $this->update(['total_amount' => $total]);
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'PO-' . date('Ym') . '-';
        $lastOrder = static::withTrashed()
            ->where('order_number', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('order_date', [$startDate, $endDate]);
    }
}