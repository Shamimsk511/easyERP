<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'passive_account_id',
        'item_type',
        'description',
        'unit_id',
        'quantity',
        'alt_qty_display',
        'alt_qty_boxes',
        'alt_qty_pieces',
        'base_quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'line_total',
        'rate_given_to_customer',
        'delivered_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'alt_qty_boxes' => 'decimal:3',
        'alt_qty_pieces' => 'decimal:3',
        'base_quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'rate_given_to_customer' => 'decimal:2',
        'delivered_quantity' => 'decimal:3',
    ];

    // Relationships
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

    public function passiveAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'passive_account_id');
    }

    // Accessors
    public function getPendingQuantityAttribute(): float
    {
        return max(0, $this->quantity - $this->delivered_quantity);
    }

    public function getIsFullyDeliveredAttribute(): bool
    {
        return $this->pending_quantity <= 0;
    }

    /**
     * Calculate and return alternative quantity breakdown
     * 
     * Example: 110 sft with conversion factors:
     * - 1 pcs = 0.6670 sft
     * - 1 box = 16.6670 sft (25 pcs)
     * 
     * Result: 6 boxes + 15 pcs (with 0.8 rounding rule)
     * 
     * @param float $baseQty Quantity in base unit (e.g., sft)
     * @param Product|null $product
     * @return array
     */
    public static function calculateAltQtyBreakdown(float $baseQty, ?Product $product = null): array
    {
        $result = [
            'display' => number_format($baseQty, 2) . ' ' . ($product?->baseUnit?->symbol ?? 'units'),
            'boxes' => 0,
            'pieces' => 0,
            'remainder' => 0,
            'base_qty' => $baseQty,
        ];

        if (!$product || $baseQty <= 0) {
            return $result;
        }

        $product->load(['baseUnit', 'alternativeUnits']);
        $altUnits = $product->alternativeUnits->sortByDesc('pivot.conversion_factor');

        if ($altUnits->isEmpty()) {
            return $result;
        }

        $remaining = $baseQty;
        $displayParts = [];
        $boxes = 0;
        $pieces = 0;

        foreach ($altUnits as $unit) {
            $factor = $unit->pivot->conversion_factor;
            
            if ($remaining >= $factor) {
                $qty = $remaining / $factor;
                
                // Apply 0.8 rounding rule: >= 0.8 rounds up, < 0.8 floors
                $fractional = $qty - floor($qty);
                $roundedQty = $fractional >= 0.8 ? ceil($qty) : floor($qty);
                
                if ($roundedQty > 0) {
                    // Identify box vs pieces by symbol or conversion factor
                    $symbolLower = strtolower($unit->symbol);
                    if (str_contains($symbolLower, 'box') || $factor > 5) {
                        $boxes = $roundedQty;
                        $displayParts[] = (int)$roundedQty . ' ' . $unit->symbol;
                    } else {
                        $pieces = $roundedQty;
                        $displayParts[] = (int)$roundedQty . ' ' . $unit->symbol;
                    }
                    
                    $remaining -= $roundedQty * $factor;
                }
            }
        }

        // Handle remaining base units
        if ($remaining > 0.01) {
            $fractional = $remaining - floor($remaining);
            $roundedRemainder = $fractional >= 0.8 ? ceil($remaining) : floor($remaining);
            
            if ($roundedRemainder > 0) {
                $displayParts[] = number_format($roundedRemainder, 2) . ' ' . $product->baseUnit->symbol;
            }
            $result['remainder'] = $roundedRemainder;
        }

        $result['display'] = implode(' + ', $displayParts) ?: $result['display'];
        $result['boxes'] = $boxes;
        $result['pieces'] = $pieces;

        return $result;
    }

    /**
     * Calculate alternative quantity from base quantity for this item
     */
    public function calculateAltQty(): array
    {
        return self::calculateAltQtyBreakdown($this->quantity, $this->product);
    }

    /**
     * Calculate line total with discount
     */
    public function calculateLineTotal(): float
    {
        $subtotal = $this->quantity * $this->unit_price;
        $discountAmount = 0;

        if ($this->discount_percent > 0) {
            $discountAmount = $subtotal * ($this->discount_percent / 100);
        } elseif ($this->discount_amount > 0) {
            $discountAmount = $this->discount_amount;
        }

        return $subtotal - $discountAmount;
    }

    /**
     * Boot method to auto-calculate values on save
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Auto-calculate line_total
            if ($item->item_type === 'product') {
                $item->line_total = $item->calculateLineTotal();
                
                // Calculate alt qty display
                if ($item->product_id) {
                    $altQty = $item->calculateAltQty();
                    $item->alt_qty_display = $altQty['display'];
                    $item->alt_qty_boxes = $altQty['boxes'];
                    $item->alt_qty_pieces = $altQty['pieces'];
                    $item->base_quantity = $altQty['base_qty'];
                }
            }

            // Store rate for customer history
            if (!$item->rate_given_to_customer && $item->unit_price > 0) {
                $item->rate_given_to_customer = $item->unit_price;
            }
        });
    }
}