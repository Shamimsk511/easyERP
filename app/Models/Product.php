<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'product_group_id',
        'base_unit_id',
        'description',
        'opening_quantity',
        'opening_rate',
        'opening_date',
        'inventory_account_id',
        'opening_stock_transaction_id',
        'current_stock',
        'minimum_stock',
        'maximum_stock',
        'reorder_level',
        'purchase_price',
        'selling_price',
        'mrp',
        'is_active',
    ];

    protected $casts = [
        'opening_quantity' => 'decimal:3',
        'opening_rate' => 'decimal:2',
         'current_stock' => 'decimal:4',
        'opening_date' => 'date',
        'minimum_stock' => 'decimal:3',
        'maximum_stock' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'mrp' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // When product is being deleted
        static::deleting(function ($product) {
            // Get the transaction with entries
            if ($product->opening_stock_transaction_id) {
                $transaction = Transaction::withTrashed()
                    ->with('entries')
                    ->find($product->opening_stock_transaction_id);
                
                if ($transaction) {
                    // First delete all entries
                    $transaction->entries()->forceDelete(); // Force delete entries
                    
                    // Then delete the transaction
                    $transaction->forceDelete(); // Force delete transaction
                }
            }

            // Detach all alternative units
            $product->alternativeUnits()->detach();
        });
    }

    public function productGroup()
    {
        return $this->belongsTo(ProductGroup::class);
    }
public function movements()
{
    return $this->hasMany(ProductMovement::class)->orderBy('movement_date', 'desc');
}
    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function inventoryAccount()
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }

    /**
     * Get the opening stock transaction
     */
    public function openingStockTransaction()
    {
        return $this->belongsTo(Transaction::class, 'opening_stock_transaction_id');
    }

    public function alternativeUnits()
    {
        return $this->belongsToMany(Unit::class, 'product_units')
                    ->withPivot('conversion_factor', 'is_default', 'is_purchase_unit', 'is_sales_unit')
                    ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getOpeningStockValueAttribute()
    {
        if ($this->opening_quantity && $this->opening_rate) {
            return $this->opening_quantity * $this->opening_rate;
        }
        return 0;
    }

    /**
     * Get current stock quantity (opening + purchases - sales)
     */

    /**
     * Get current stock value
     */
    public function getCurrentStockValueAttribute()
    {
        $currentStock = $this->current_stock;
        $averageRate = $this->purchase_price ?? $this->opening_rate ?? 0;
        
        return $currentStock * $averageRate;
    }

    /**
     * Create accounting entry for opening stock
     */
    public function createOpeningStockJournalEntry()
    {
        if (!$this->opening_quantity || !$this->opening_rate || !$this->inventory_account_id) {
            return null;
        }

        $openingValue = $this->opening_stock_value;
        
        // Create transaction
        $transaction = Transaction::create([
            'date' => $this->opening_date ?? now(),
            'reference' => 'OPENING-STOCK-' . $this->id,
            'description' => 'Opening Stock for ' . $this->name,
            'notes' => 'Auto-generated from product creation',
            'status' => 'posted',
        ]);

        // Debit: Inventory Account (Asset increases)
        $transaction->entries()->create([
            'account_id' => $this->inventory_account_id,
            'amount' => $openingValue,
            'type' => 'debit',
            'memo' => $this->opening_quantity . ' ' . $this->baseUnit->symbol . ' @ ৳' . $this->opening_rate,
        ]);

        // Credit: Owner's Capital (from your seeder - Account ID 22)
        $transaction->entries()->create([
            'account_id' => 22, // Owner's Capital from your AccountSeeder
            'amount' => $openingValue,
            'type' => 'credit',
            'memo' => 'Opening stock value for ' . $this->name,
        ]);

        return $transaction;
    }

    /**
     * Update or create opening stock journal entry
     */
    public function updateOpeningStockJournalEntry()
    {
        // If there's an existing transaction, delete it first
        if ($this->opening_stock_transaction_id) {
            $oldTransaction = Transaction::withTrashed()
                ->with('entries')
                ->find($this->opening_stock_transaction_id);
            
            if ($oldTransaction) {
                // Force delete entries first
                $oldTransaction->entries()->forceDelete();
                // Then force delete transaction
                $oldTransaction->forceDelete();
            }
        }

        // Create new transaction if opening stock exists
        if ($this->opening_quantity > 0 && $this->opening_rate && $this->inventory_account_id) {
            return $this->createOpeningStockJournalEntry();
        }

        return null;
    }

    public function convertQuantityToDisplay($quantity)
    {
        $units = $this->alternativeUnits()
                     ->orderBy('conversion_factor', 'DESC')
                     ->get();
        
        if ($units->isEmpty()) {
            return number_format($quantity, 2) . ' ' . $this->baseUnit->symbol;
        }
        
        $remainingQty = $quantity;
        $display = [];
        
        foreach ($units as $unit) {
            if ($remainingQty >= $unit->pivot->conversion_factor) {
                $unitQty = floor($remainingQty / $unit->pivot->conversion_factor);
                $display[] = $unitQty . ' ' . $unit->symbol;
                $remainingQty = fmod($remainingQty, $unit->pivot->conversion_factor);
            }
        }
        
        if ($remainingQty > 0) {
            $display[] = number_format($remainingQty, 2) . ' ' . $this->baseUnit->symbol;
        }
        
        return implode(' + ', $display);
    }
}
