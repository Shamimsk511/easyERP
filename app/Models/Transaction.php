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
        'reference',
        'description',
        'notes',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Remove the boot method that was causing issues

    public function entries()
    {
        return $this->hasMany(TransactionEntry::class);
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
}
