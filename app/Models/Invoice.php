<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'due_date',
        'customer_id',
        'sales_account_id',
        'customer_ledger_account_id',
        'sales_return_account_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'outstanding_at_creation',
        'delivery_status',
        'internal_notes',
        'customer_notes',
        'deleted_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'outstanding_at_creation' => 'decimal:2',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function salesAccount()
    {
        return $this->belongsTo(Account::class, 'sales_account_id');
    }

    public function customerLedgerAccount()
    {
        return $this->belongsTo(Account::class, 'customer_ledger_account_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeDeleted($query)
    {
        return $query->whereNotNull('deleted_at');
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('invoice_date', [$startDate, $endDate]);
    }

    // Accessors & Methods
    public function getTotalPaidAttribute()
    {
        return $this->payments()->where('deleted_at', null)->sum('amount') ?? 0;
    }

    public function getOutstandingBalanceAttribute()
    {
        return $this->total_amount - $this->total_paid;
    }

    public function getDeliveredQuantityAttribute($itemId)
    {
        return $this->items()
            ->where('id', $itemId)
            ->first()
            ->delivered_quantity ?? 0;
    }

    public function getTotalDeliveredQuantityByInvoice()
    {
        $allDelivered = true;
        foreach ($this->items as $item) {
            if ($item->delivered_quantity < $item->quantity) {
                $allDelivered = false;
                break;
            }
        }
        return $allDelivered;
    }

    public function isFullyDelivered()
    {
        return $this->delivery_status === 'delivered';
    }

    public function updateDeliveryStatus()
    {
        $totalQty = 0;
        $deliveredQty = 0;

        foreach ($this->items as $item) {
            $totalQty += $item->quantity;
            $deliveredQty += $item->delivered_quantity;
        }

        if ($deliveredQty == 0) {
            $this->delivery_status = 'pending';
        } elseif ($deliveredQty >= $totalQty) {
            $this->delivery_status = 'delivered';
        } else {
            $this->delivery_status = 'partial';
        }

        $this->save();
    }

    public function canBeDeleted()
    {
        // Can delete only if not soft deleted already
        return $this->deleted_at === null;
    }

    public function markAsDeleted($userId)
    {
        $this->deleted_by = $userId;
        $this->deleted_at = now();
        $this->save();
    }
}
