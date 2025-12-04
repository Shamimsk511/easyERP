<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceiptVoucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'voucher_number',
        'receipt_date',
        'payment_method',
        'amount',
        'customer_id',
        'received_in_account_id',
        'customer_account_id',
        'transaction_id',
        'cheque_number',
        'cheque_date',
        'bank_name',
        'description',
        'notes',
        'status',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'cheque_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function receivedInAccount()
    {
        return $this->belongsTo(Account::class, 'received_in_account_id');
    }

    public function customerAccount()
    {
        return $this->belongsTo(Account::class, 'customer_account_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Generate unique voucher number
     */
    public static function generateVoucherNumber(): string
    {
        $prefix = 'RCV-' . date('Ym') . '-';
        $lastVoucher = self::where('voucher_number', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastVoucher) {
            $lastNumber = (int) substr($lastVoucher->voucher_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Check if voucher can be edited
     */
    public function canEdit(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if voucher can be cancelled
     */
    public function canCancel(): bool
    {
        return $this->status === 'posted';
    }
}
