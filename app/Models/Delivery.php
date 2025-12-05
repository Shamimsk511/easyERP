<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    protected $casts = [
        'delivery_date' => 'date',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function deliveredBy()
    {
        return $this->belongsTo(User::class, 'delivered_by_user_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    // Methods
    public function getTotalDeliveredAmount()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $invoiceItem = $item->invoiceItem;
            $total += $invoiceItem->unit_price * $item->delivered_quantity;
        }
        return $total;
    }
}
