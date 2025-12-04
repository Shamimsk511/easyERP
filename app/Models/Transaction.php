<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'date',
         'type',
        'reference',
        'description',
        'notes',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    // Remove the boot method that was causing issues

    public function entries()
    {
        return $this->hasMany(TransactionEntry::class);
    }
      /**
     * Check if transaction can be edited
     */
    public function getCanEditAttribute()
    {
        return $this->status === 'draft';
    }

    /**
     * Check if transaction can be voided
     */
    public function getCanVoidAttribute()
    {
        return $this->status === 'posted';
    }

    public function void()
    {
        $this->update(['status' => 'voided']);
    }

    public function getTotalDebitAttribute()
    {
        return $this->entries()->where('type', 'debit')->sum('amount');
    }

    public function getTotalCreditAttribute()
    {
        return $this->entries()->where('type', 'credit')->sum('amount');
    }

    public function isBalanced()
    {
        return $this->total_debit == $this->total_credit;
    }
public function getTotalDebits()
{
    return $this->entries()
        ->where('type', 'debit')
        ->sum('amount');
}

public function getTotalCredits()
{
    return $this->entries()
        ->where('type', 'credit')
        ->sum('amount');
}

public function getTotalAmount()
{
    return $this->getTotalDebits(); // or getTotalCredits() - they should be equal
}


}