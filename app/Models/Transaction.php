<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
        'source_type',  // Added: Polymorphic source (Invoice::class, Delivery::class, etc.)
        'source_id',    // Added: ID of the source model
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get all transaction entries (double-entry lines)
     */
    public function entries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    /**
     * Get the source model (Invoice, Delivery, Payment, etc.)
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if transaction can be edited
     */
    public function getCanEditAttribute(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if transaction can be voided
     */
    public function getCanVoidAttribute(): bool
    {
        return $this->status === 'posted';
    }

    /**
     * Void this transaction
     */
    public function void(): bool
    {
        return $this->update(['status' => 'voided']);
    }

    /**
     * Get total debit amount
     */
    public function getTotalDebitAttribute(): float
    {
        return (float) $this->entries()->where('type', 'debit')->sum('amount');
    }

    /**
     * Get total credit amount
     */
    public function getTotalCreditAttribute(): float
    {
        return (float) $this->entries()->where('type', 'credit')->sum('amount');
    }

    /**
     * Check if transaction is balanced (debits = credits)
     */
    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    /**
     * Alias methods for compatibility
     */
    public function getTotalDebits(): float
    {
        return $this->total_debit;
    }

    public function getTotalCredits(): float
    {
        return $this->total_credit;
    }

    public function getTotalAmount(): float
    {
        return $this->total_debit;
    }

    /**
     * Scopes
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForSource($query, string $sourceType, int $sourceId)
    {
        return $query->where('source_type', $sourceType)->where('source_id', $sourceId);
    }
}