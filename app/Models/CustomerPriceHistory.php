<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPriceHistory extends Model
{
    protected $table = 'customer_price_history';

    protected $fillable = [
        'customer_id',
        'product_id',
        'rate',
        'last_sold_date',
        'quantity_sold',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'last_sold_date' => 'date',
        'quantity_sold' => 'decimal:3',
    ];

    /**
     * Relationships
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Update price history from invoice
     */
    public function updateFromInvoice(float $quantity, float $rate): void
    {
        $this->rate = $rate;
        $this->last_sold_date = now();
        $this->quantity_sold = (float) $this->quantity_sold + $quantity;
        $this->save();
    }

    /**
     * Create or update price history from invoice
     */
    public static function createFromInvoice(
        Customer $customer,
        Product $product,
        float $quantity,
        float $rate
    ): self {
        return static::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
            ],
            [
                'rate' => $rate,
                'last_sold_date' => now(),
                'quantity_sold' => \DB::raw("COALESCE(quantity_sold, 0) + {$quantity}"),
            ]
        );
    }

    /**
     * Get last rate for a customer-product combination
     */
    public static function getLastRate(int $customerId, int $productId): ?float
    {
        $record = static::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->first();

        return $record?->rate;
    }

    /**
     * Scopes
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderBy('last_sold_date', 'desc');
    }
}