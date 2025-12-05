<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPriceHistory extends Model
{
    protected $table = 'customer_price_history';

    protected $fillable = [
        'customer_id',
        'product_id',
        'rate',
        'last_sold_date',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'last_sold_date' => 'date',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
