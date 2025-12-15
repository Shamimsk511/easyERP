<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Delivery extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'challan_number',
        'invoice_id',
        'delivery_date',
        'delivery_method',
        'driver_name',
        'delivered_by_user_id',
        'notes',
        'transaction_id',
    ];

        protected $dates = ['delivery_date', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by_user_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get delivery summary
     */
    public function getSummary(): array
    {
        $items = $this->items;
        
        return [
            'challan_number' => $this->challan_number,
            'invoice_number' => $this->invoice->invoice_number,
            'customer_name' => $this->invoice->customer->name,
            'delivery_date' => $this->delivery_date->format('Y-m-d'),
            'total_items' => $items->count(),
            'driver' => $this->driver_name,
            'method' => $this->delivery_method,
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    // Methods
    public function getTotalDeliveredAmount()
    {
        return $this->items()
            ->join('invoice_items', 'delivery_items.invoice_item_id', '=', 'invoice_items.id')
            ->selectRaw('SUM(delivery_items.delivered_quantity * invoice_items.unit_price) as total')
            ->value('total') ?? 0;
    }

    public function getTotalDeliveredQuantity()
    {
        return $this->items()->sum('delivered_quantity');
    }
}