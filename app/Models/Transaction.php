<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
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

    // Relationships
    public function entries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    // Helper methods
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

    public function isBalanced(): bool
{
        $debits = $this->entries()->where('type', 'debit')->sum('amount');
        $credits = $this->entries()->where('type', 'credit')->sum('amount');
        
        return abs($debits - $credits) < 0.01;
    }

    public function post()
    {
        if ($this->isBalanced() && $this->status === 'draft') {
            $this->update(['status' => 'posted']);
            return true;
        }
        return false;
    }

    public function void()
    {
        $this->update(['status' => 'voided']);
    }
}
