<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentVoucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'voucher_number',
        'payment_date',
        'payment_method',
        'amount',
        'payee_type',
        'payee_id',
        'paid_from_account_id',
        'paid_to_account_id',
        'transaction_id',
        'cheque_number',
        'cheque_date',
        'bank_name',
        'description',
        'notes',
        'status',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'cheque_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function paidFromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'paid_from_account_id');
    }

    public function paidToAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'paid_to_account_id');
    }
        public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the payee (vendor, customer, etc.)
     */
    public function payee(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Generate unique voucher number
     */
    public static function generateVoucherNumber(): string
    {
        $prefix = 'PV-' . date('Ym') . '-';
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
