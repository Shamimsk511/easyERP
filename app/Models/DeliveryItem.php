<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryItem extends Model
{
    protected $fillable = [
        'delivery_id',
        'invoice_item_id',
        'delivered_quantity',
    ];

    protected $casts = [
        'delivered_quantity' => 'decimal:3',
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class);
    }
}
