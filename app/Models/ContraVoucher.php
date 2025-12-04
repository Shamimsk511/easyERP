<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContraVoucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'voucher_number',
        'contra_date',
        'amount',
        'from_account_id',
        'to_account_id',
        'transaction_id',
        'transfer_method',
        'cheque_number',
        'cheque_date',
        'bank_name',
        'reference_number',
        'description',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'contra_date' => 'date',
        'cheque_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helper Methods
    public static function generateVoucherNumber(): string
    {
        $prefix = 'CNT';
        $date = now()->format('Ymd');
        
        $lastVoucher = static::whereDate('created_at', today())
            ->where('voucher_number', 'like', $prefix . $date . '%')
            ->orderBy('voucher_number', 'desc')
            ->first();
        
        if ($lastVoucher) {
            $lastNumber = (int) substr($lastVoucher->voucher_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . $date . $newNumber;
    }

    public function getCanEditAttribute(): bool
    {
        return $this->status === 'draft';
    }

    public function getCanCancelAttribute(): bool
    {
        return $this->status === 'posted';
    }
}
