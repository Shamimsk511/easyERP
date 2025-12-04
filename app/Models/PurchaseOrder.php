<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrder extends Model
{

    use HasFactory;
    protected $fillable = [
        'vendor_id','purchase_account_id', 'order_number', 'status', 'order_date',
        'received_date', 'notes', 'total_amount',
        'transaction_id',
    ];

     protected $casts = [
        'order_date' => 'date',
        'received_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
     public function purchaseAccount()
    {
        return $this->belongsTo(Account::class, 'purchase_account_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function productMovements()
    {
        return $this->morphMany(ProductMovement::class, 'reference');
    }
}
