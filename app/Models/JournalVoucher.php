<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalVoucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'voucher_number',
        'journal_date',
        'transaction_id',
        'description',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'journal_date' => 'date',
    ];

    // Relationships
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
        $prefix = 'JV';
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

    public function getTotalDebitAttribute(): float
    {
        if (!$this->transaction) {
            return 0;
        }

        return $this->transaction->entries()
            ->where('type', 'debit')
            ->sum('amount');
    }

    public function getTotalCreditAttribute(): float
    {
        if (!$this->transaction) {
            return 0;
        }

        return $this->transaction->entries()
            ->where('type', 'credit')
            ->sum('amount');
    }

    public function getIsBalancedAttribute(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }
}
