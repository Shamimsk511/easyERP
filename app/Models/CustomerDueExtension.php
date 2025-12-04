<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerDueExtension extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'original_due_date',
        'extended_due_date',
        'days_extended',
        'reason',
        'extended_by'
    ];

    protected $casts = [
        'original_due_date' => 'date',
        'extended_due_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function extendedBy()
    {
        return $this->belongsTo(User::class, 'extended_by');
    }
}
