<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Unit;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\ProductMovement;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of products with server-side DataTables
     */
public function index(Request $request)
{
    if ($request->ajax()) {
        $data = Product::with(['productGroup', 'baseUnit', 'inventoryAccount'])
                      ->select('products.*');
        
        return DataTables::eloquent($data)
            ->addIndexColumn()
            // ADD THESE EXPLICIT COLUMNS FOR id, name, code
            ->editColumn('id', function ($row) {
                return $row->id;
            })
            ->editColumn('name', function ($row) {
                return $row->name;
            })
            ->editColumn('code', function ($row) {
                return $row->code ?? '<span class="text-muted">-</span>';
            })
            // EXISTING CUSTOM COLUMNS
            ->addColumn('group_name', function ($row) {
                return $row->productGroup ? $row->productGroup->full_path : '<span class="badge badge-secondary">No Group</span>';
            })
            ->addColumn('unit_name', function ($row) {
                return '<code>' . $row->baseUnit->symbol . '</code>';
            })
            ->addColumn('current_stock', function ($row) {
                $currentStock = $row->current_stock;
                
                if ($currentStock > 0) {
                    // Check stock levels
                    if ($currentStock <= $row->minimum_stock) {
                        $badgeClass = 'badge-danger';
                    } elseif ($currentStock <= $row->reorder_level) {
                        $badgeClass = 'badge-warning';
                    } else {
                        $badgeClass = 'badge-success';
                    }
                    
                    return '<span class="badge ' . $badgeClass . '">' . 
                           number_format($currentStock, 2) . ' ' . $row->baseUnit->symbol . 
                           '</span>';
                }
                
                return '<span class="badge badge-secondary">0 ' . $row->baseUnit->symbol . '</span>';
            })
            ->addColumn('stock_value', function ($row) {
                $value = $row->current_stock_value;
                if ($value > 0) {
                    return '<strong>৳ ' . number_format($value, 2) . '</strong>';
                }
                return '<span class="text-muted">৳ 0.00</span>';
            })
            ->addColumn('rate_info', function ($row) {
                if ($row->purchase_price) {
                    return '৳ ' . number_format($row->purchase_price, 2);
                } elseif ($row->opening_rate) {
                    return '৳ ' . number_format($row->opening_rate, 2);
                }
                return '<span class="badge badge-warning">Not Set</span>';
            })
            ->addColumn('is_active', function ($row) {
                return $row->is_active 
                    ? '<span class="badge badge-success">Active</span>' 
                    : '<span class="badge badge-danger">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                $viewBtn = '<a href="' . route('products.show', $row->id) . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>';
                $editBtn = '<a href="' . route('products.edit', $row->id) . '" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>';
                $deleteBtn = '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '"><i class="fas fa-trash"></i></button>';
                
                return '<div class="btn-group">' . $viewBtn . ' ' . $editBtn . ' ' . $deleteBtn . '</div>';
            })
            ->filter(function ($query) use ($request) {
                // Filter by product group
                if ($request->has('product_group_id') && $request->product_group_id != '') {
                    $query->where('product_group_id', $request->product_group_id);
                }
                
                // Filter by status
                if ($request->has('status') && $request->status != '') {
                    $query->where('is_active', $request->status);
                }
            })
            ->rawColumns(['code', 'group_name', 'unit_name', 'current_stock', 'stock_value', 'rate_info', 'is_active', 'action'])
            ->make(true);
    }
    
    // Get product groups for filter dropdown
    $productGroups = ProductGroup::active()->orderBy('name')->get();
    
    return view('products.index', compact('productGroups'));
}



    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $productGroups = ProductGroup::active()->get();
        $units = Unit::active()->orderBy('name')->get();
        $baseUnits = Unit::active()->where('is_base_unit', true)->orderBy('name')->get();
        $inventoryAccounts = Account::inventoryAccounts()->active()->get();
        
        return view('products.create', compact('productGroups', 'units', 'baseUnits', 'inventoryAccounts'));
    }

    /**
     * Store a newly created product
     */
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:200|unique:products,name',
        'code' => 'nullable|string|max:50|unique:products,code',
        'product_group_id' => 'nullable|exists:product_groups,id',
        'base_unit_id' => 'required|exists:units,id',
        'description' => 'nullable|string',
        'opening_quantity' => 'nullable|numeric|min:0',
        'opening_rate' => 'nullable|numeric|min:0',
        'opening_date' => 'nullable|date',
        'inventory_account_id' => 'nullable|exists:accounts,id',
        'minimum_stock' => 'nullable|numeric|min:0',
        'maximum_stock' => 'nullable|numeric|min:0',
        'reorder_level' => 'nullable|numeric|min:0',
        'purchase_price' => 'nullable|numeric|min:0',
        'selling_price' => 'nullable|numeric|min:0',
        'mrp' => 'nullable|numeric|min:0',
        'is_active' => 'nullable|boolean',
        'alt_unit_id' => 'nullable|array',
        'alt_unit_id.*' => 'exists:units,id',
        'conversion_factor' => 'nullable|array',
        'conversion_factor.*' => 'numeric|min:0.0001',
        'is_purchase_unit' => 'nullable|array',
        'is_sales_unit' => 'nullable|array',
    ]);

    DB::beginTransaction();
    try {
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;
        
        // Set opening date if opening quantity is provided but date is not
        if (isset($validated['opening_quantity']) && $validated['opening_quantity'] > 0 && empty($validated['opening_date'])) {
            $validated['opening_date'] = now()->toDateString();
        }
        
        // Validate opening stock has required fields
        if (isset($validated['opening_quantity']) && $validated['opening_quantity'] > 0) {
            if (empty($validated['opening_rate'])) {
                throw new \Exception('Opening rate is required when opening quantity is provided.');
            }
            if (empty($validated['inventory_account_id'])) {
                throw new \Exception('Inventory account is required when opening stock is provided.');
            }
        }
        
        // Create product
        $product = Product::create($validated);

        // Save alternative units
        if ($request->has('alt_unit_id') && is_array($request->alt_unit_id)) {
            foreach ($request->alt_unit_id as $index => $unitId) {
                if ($unitId && isset($request->conversion_factor[$index]) && $request->conversion_factor[$index] > 0) {
                    $product->alternativeUnits()->attach($unitId, [
                        'conversion_factor' => $request->conversion_factor[$index],
                        'is_default' => false,
                        'is_purchase_unit' => isset($request->is_purchase_unit[$index]) ? 1 : 0,
                        'is_sales_unit' => isset($request->is_sales_unit[$index]) ? 1 : 0,
                    ]);
                }
            }
        }

        // Create accounting entry for opening stock
        if ($product->opening_quantity > 0 && $product->opening_rate && $product->inventory_account_id) {
            $transaction = $product->createOpeningStockJournalEntry();
            
            if ($transaction) {
                // Store transaction ID in product
                $product->update(['opening_stock_transaction_id' => $transaction->id]);
            } else {
                throw new \Exception('Failed to create opening stock journal entry.');
            }
        }

        DB::commit();

        return redirect()
            ->route('products.index')
            ->with('success', 'Product created successfully!' . 
                   ($product->opening_quantity > 0 ? ' Opening stock journal entry created.' : ''));

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Error creating product: ' . $e->getMessage());
    }
}


    /**
     * Display the specified product
     */
public function show(Product $product)
{
    $product->load('productGroup', 'baseUnit', 'inventoryAccount', 'alternativeUnits');
    
    // Load product movements with relationships
    $movements = $product->movements()
        ->with(['createdBy', 'product.baseUnit'])
        ->orderBy('movement_date', 'desc')
        ->orderBy('created_at', 'desc')
        ->get();
    
    return view('products.show', compact('product', 'movements'));
}


    /**
     * Show the form for editing the specified product
     */
    public function edit(Product $product)
    {
        $product->load('alternativeUnits');
        $productGroups = ProductGroup::active()->get();
        $units = Unit::active()->orderBy('name')->get();
        $baseUnits = Unit::active()->where('is_base_unit', true)->orderBy('name')->get();
        $inventoryAccounts = Account::inventoryAccounts()->active()->get();
        
        return view('products.edit', compact('product', 'productGroups', 'units', 'baseUnits', 'inventoryAccounts'));
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, Product $product)
{
    $validated = $request->validate([
        'name' => [
            'required',
            'string',
            'max:200',
            Rule::unique('products', 'name')->ignore($product->id),
        ],
        'code' => [
            'nullable',
            'string',
            'max:50',
            Rule::unique('products', 'code')->ignore($product->id),
        ],
        'product_group_id' => 'nullable|exists:product_groups,id',
        'base_unit_id' => 'required|exists:units,id',
        'description' => 'nullable|string',
        'opening_quantity' => 'nullable|numeric|min:0',
        'opening_rate' => 'nullable|numeric|min:0',
        'opening_date' => 'nullable|date',
        'inventory_account_id' => 'nullable|exists:accounts,id',
        'minimum_stock' => 'nullable|numeric|min:0',
        'maximum_stock' => 'nullable|numeric|min:0',
        'reorder_level' => 'nullable|numeric|min:0',
        'purchase_price' => 'nullable|numeric|min:0',
        'selling_price' => 'nullable|numeric|min:0',
        'mrp' => 'nullable|numeric|min:0',
        'is_active' => 'nullable|boolean',
        'alt_unit_id' => 'nullable|array',
        'alt_unit_id.*' => 'exists:units,id',
        'conversion_factor' => 'nullable|array',
        'conversion_factor.*' => 'numeric|min:0.0001',
        'is_purchase_unit' => 'nullable|array',
        'is_sales_unit' => 'nullable|array',
    ]);

    DB::beginTransaction();
    try {
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;
        
        // Check if opening stock values changed
        $openingStockChanged = (
            $product->opening_quantity != $validated['opening_quantity'] ||
            $product->opening_rate != $validated['opening_rate'] ||
            $product->inventory_account_id != $validated['inventory_account_id']
        );
        
        // Update product
        $product->update($validated);

        // Sync alternative units (remove old, add new)
        $product->alternativeUnits()->detach();
        
        if ($request->has('alt_unit_id') && is_array($request->alt_unit_id)) {
            foreach ($request->alt_unit_id as $index => $unitId) {
                if ($unitId && isset($request->conversion_factor[$index]) && $request->conversion_factor[$index] > 0) {
                    $product->alternativeUnits()->attach($unitId, [
                        'conversion_factor' => $request->conversion_factor[$index],
                        'is_default' => false,
                        'is_purchase_unit' => isset($request->is_purchase_unit[$index]) ? 1 : 0,
                        'is_sales_unit' => isset($request->is_sales_unit[$index]) ? 1 : 0,
                    ]);
                }
            }
        }

        // Update opening stock transaction if values changed
        if ($openingStockChanged) {
            $transaction = $product->updateOpeningStockJournalEntry();
            
            if ($transaction) {
                $product->update(['opening_stock_transaction_id' => $transaction->id]);
            } else {
                // If no transaction created, clear the reference
                $product->update(['opening_stock_transaction_id' => null]);
            }
        }

        DB::commit();

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully!');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Error updating product: ' . $e->getMessage());
    }
}


    /**
     * Remove the specified product
     */
public function destroy(Product $product)
{
    try {
        DB::beginTransaction();

        // Check if product has transactions (purchases/sales)
        // TODO: Uncomment when you build sales/purchase system
        // if ($product->hasPurchases() || $product->hasSales()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Cannot delete product. It has transaction history.',
        //     ], 422);
        // }

        // The deleting event in the Product model will handle:
        // 1. Deleting the opening stock transaction
        // 2. Detaching alternative units
        $product->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Product and associated transaction deleted successfully!',
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'success' => false,
            'message' => 'Error deleting product: ' . $e->getMessage(),
        ], 500);
    }
}

    /**
     * Helper: Create opening stock journal entry
     * TODO: Implement when you create Journal Entry system
     */
    private function createOpeningStockJournalEntry(Product $product)
    {
        // Will be implemented with Journal Entry feature
        // Debit: Inventory Account
        // Credit: Owner's Capital (Account ID 22 from your seeder)
    }
    /**
 * Quick add product via AJAX (for purchase order form)
 */
public function quickAdd(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:200|unique:products,name',
        'base_unit' => 'required|string|max:50',
    ]);

    DB::beginTransaction();
    
    try {
        // Find or create the unit
        $unit = Unit::firstOrCreate(
            ['symbol' => strtoupper($validated['base_unit'])],
            [
                'name' => $validated['base_unit'],
                'symbol' => strtoupper($validated['base_unit']),
                'is_base_unit' => true,
                'is_active' => true,
            ]
        );

        // Create product with minimal data
        $product = Product::create([
            'name' => $validated['name'],
            'base_unit_id' => $unit->id,
            'is_active' => true,
        ]);

        DB::commit();

return response()->json([
    'success' => true,
    'id' => $product->id,
    'name' => $product->name,
    'unit' => $unit->symbol,
    'message' => 'Product created successfully!',
]);


    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'success' => false,
            'message' => 'Error creating product: ' . $e->getMessage(),
        ], 500);
    }
}
public function getMovementsDatatable(Request $request, Product $product)
{
    if ($request->ajax()) {
        $movements = ProductMovement::where('product_id', $product->id)
            ->with(['createdBy', 'product.baseUnit'])
            ->select('product_movements.*');
        
        return DataTables::of($movements)
            ->addIndexColumn()
            ->addColumn('date_formatted', function ($row) {
                return '<strong>' . $row->movement_date->format('d M Y') . '</strong><br>' .
                       '<small class="text-muted">' . $row->created_at->format('h:i A') . '</small>';
            })
            ->addColumn('type_badge', function ($row) {
                $badges = [
                    'purchase' => 'badge-success',
                    'sale' => 'badge-danger',
                    'adjustment' => 'badge-warning',
                    'opening_stock' => 'badge-info',
                    'return' => 'badge-primary',
                ];
                $icons = [
                    'purchase' => 'fa-cart-plus',
                    'sale' => 'fa-shopping-cart',
                    'adjustment' => 'fa-adjust',
                    'opening_stock' => 'fa-box-open',
                    'return' => 'fa-undo',
                ];
                
                return '<span class="badge ' . ($badges[$row->type] ?? 'badge-secondary') . '">' .
                       '<i class="fas ' . ($icons[$row->type] ?? 'fa-question') . '"></i> ' .
                       ucfirst(str_replace('_', ' ', $row->type)) .
                       '</span>';
            })
            ->addColumn('reference', function ($row) {
                if ($row->reference_type && $row->reference_id) {
                    $refClass = class_basename($row->reference_type);
                    return '<small class="text-muted">' . $refClass . '</small><br>' .
                           'de>#' . $row->reference_id . '</code>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('quantity_formatted', function ($row) {
                $class = $row->type == 'purchase' ? 'text-success' : ($row->type == 'sale' ? 'text-danger' : '');
                $sign = $row->type == 'purchase' ? '+' : ($row->type == 'sale' ? '-' : '');
                
                return '<strong class="' . $class . '">' .
                       $sign . number_format($row->quantity, 2) .
                       '</strong> ' .
                       '<small class="text-muted">' . $row->product->baseUnit->symbol . '</small>';
            })
            ->addColumn('rate_formatted', function ($row) {
                return $row->rate ? '৳ ' . number_format($row->rate, 2) : '<span class="text-muted">-</span>';
            })
            ->addColumn('stock_before_formatted', function ($row) {
                return number_format($row->stock_before, 2);
            })
            ->addColumn('stock_after_formatted', function ($row) {
                return '<strong>' . number_format($row->stock_after, 2) . '</strong>';
            })
            ->addColumn('created_by_name', function ($row) {
                return $row->createdBy ? $row->createdBy->name : '<small class="text-muted">System</small>';
            })
            ->rawColumns(['date_formatted', 'type_badge', 'reference', 'quantity_formatted', 'rate_formatted', 'stock_after_formatted', 'created_by_name'])
            ->make(true);
    }
}


 /**
     * Get product details with alternative unit conversion
     * Returns current stock in all units
     */
    public function getProductDetails(Request $request)
    {
        try {
            $productId = $request->get('product_id');
            $product = Product::with(['baseUnit', 'alternativeUnits'])
                ->findOrFail($productId);

            // Get current stock in base unit
            $baseStock = $product->currentstock ?? 0;

            // Build alternative unit stocks
            $alternativeUnitStocks = [];
            foreach ($product->alternativeUnits as $altUnit) {
                $conversionFactor = $altUnit->pivot->conversion_factor;
                $altStock = $baseStock / $conversionFactor;
                
                $alternativeUnitStocks[] = [
                    'unit_id' => $altUnit->id,
                    'unit_name' => $altUnit->name,
                    'unit_symbol' => $altUnit->symbol,
                    'conversion_factor' => $conversionFactor,
                    'stock' => round($altStock, 4),
                    'is_purchase_unit' => (bool) $altUnit->pivot->is_purchase_unit,
                    'is_sales_unit' => (bool) $altUnit->pivot->is_sales_unit,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'base_unit' => [
                        'id' => $product->baseUnit->id,
                        'name' => $product->baseUnit->name,
                        'symbol' => $product->baseUnit->symbol,
                    ],
                    'current_stock' => [
                        'base_unit' => round($baseStock, 4),
                        'base_unit_symbol' => $product->baseUnit->symbol,
                        'alternative_units' => $alternativeUnitStocks,
                    ],
                    'purchase_price' => $product->purchase_price,
                    'selling_price' => $product->selling_price,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get product details error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching product details',
            ], 500);
        }
    }
/**
     * Calculate quantity in base unit from alternative unit
     * Used when receiving purchase orders or creating invoices
     */
    public function convertToBaseUnit(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|numeric|min:0.001',
                'unit_id' => 'required|exists:units,id',
            ]);

            $product = Product::with('alternativeUnits', 'baseUnit')
                ->findOrFail($validated['product_id']);

            // If unit is the base unit
            if ($validated['unit_id'] == $product->base_unit_id) {
                return response()->json([
                    'success' => true,
                    'quantity_in_base_unit' => $validated['quantity'],
                    'base_unit_symbol' => $product->baseUnit->symbol,
                ]);
            }

            // Find conversion factor for alternative unit
            $altUnit = $product->alternativeUnits()
                ->where('unit_id', $validated['unit_id'])
                ->first();

            if (!$altUnit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit not configured for this product',
                ], 422);
            }

            $conversionFactor = $altUnit->pivot->conversion_factor;
            $quantityInBase = $validated['quantity'] * $conversionFactor;

            return response()->json([
                'success' => true,
                'quantity_in_base_unit' => round($quantityInBase, 4),
                'base_unit_symbol' => $product->baseUnit->symbol,
                'conversion_factor' => $conversionFactor,
            ]);
        } catch (\Exception $e) {
            Log::error('Convert to base unit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error converting quantity',
            ], 500);
        }
    }
/**
     * Get DataTables data for products with stock in alternative units
     */
    public function getProductsWithAlternativeStock(Request $request)
    {
        try {
            $products = Product::with([
                'productGroup',
                'baseUnit',
                'alternativeUnits',
                'inventoryAccount'
            ])->get();

            // Transform data to include alternative unit stocks
            $transformedProducts = $products->map(function ($product) {
                $baseStock = $product->currentstock ?? 0;
                
                // Prepare alternative unit display
                $altUnitDisplay = '';
                foreach ($product->alternativeUnits as $altUnit) {
                    if ($altUnit->pivot->is_sales_unit) {
                        $conversionFactor = $altUnit->pivot->conversion_factor;
                        $altStock = $baseStock / $conversionFactor;
                        $altUnitDisplay .= round($altStock, 2) . ' ' . $altUnit->symbol . ' | ';
                    }
                }
                $altUnitDisplay = rtrim($altUnitDisplay, ' | ');

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'group_name' => $product->productGroup ? $product->productGroup->fullpath : 'No Group',
                    'base_unit' => $product->baseUnit->symbol,
                    'current_stock' => round($baseStock, 4),
                    'alternative_units_display' => $altUnitDisplay ?: 'N/A',
                    'is_active' => $product->is_active,
                ];
            });

            return DataTables::of($transformedProducts)
                ->addIndexColumn()
                ->rawColumns(['alternative_units_display'])
                ->make(true);
        } catch (\Exception $e) {
            Log::error('Get products with alternative stock error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching product data',
            ], 500);
        }
    }
 /**
     * Record product movement with alternative unit support
     * Call this when stock is updated (purchase, sale, adjustment)
     */
    private function recordProductMovement(
        $productId,
        $movementType,
        $quantityInBaseUnit,
        $rate = null,
        $referenceType = null,
        $referenceId = null,
        $notes = null,
        $unitId = null
    ) {
        try {
            $product = Product::findOrFail($productId);
            $stockBefore = $product->currentstock ?? 0;

            // Update product stock
            if ($movementType == 'purchase' || $movementType == 'opening_stock') {
                $newStock = $stockBefore + $quantityInBaseUnit;
            } else if ($movementType == 'sale' || $movementType == 'return') {
                $newStock = $stockBefore - $quantityInBaseUnit;
            } else if ($movementType == 'adjustment') {
                $newStock = $quantityInBaseUnit; // Direct set for adjustments
            }

            $newStock = max(0, $newStock); // Prevent negative stock

            // If unit was provided and it's not the base unit, get the conversion factor
            $conversionNote = '';
            if ($unitId && $unitId != $product->base_unit_id) {
                $altUnit = $product->alternativeUnits()
                    ->where('unit_id', $unitId)
                    ->first();
                
                if ($altUnit) {
                    $conversionFactor = $altUnit->pivot->conversion_factor;
                    $quantityInAltUnit = $quantityInBaseUnit / $conversionFactor;
                    $conversionNote = " ({$quantityInAltUnit} {$altUnit->symbol})";
                }
            }

            // Create movement record
            ProductMovement::create([
                'product_id' => $productId,
                'type' => $movementType,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity' => $quantityInBaseUnit,
                'rate' => $rate,
                'stock_before' => $stockBefore,
                'stock_after' => $newStock,
                'movement_date' => now(),
                'notes' => ($notes ?? '') . $conversionNote,
                'created_by' => auth()->id(),
            ]);

            // Update product stock
            $product->update(['currentstock' => $newStock]);

            return true;
        } catch (\Exception $e) {
            Log::error('Record product movement error: ' . $e->getMessage());
            throw $e;
        }
    }

 /**
     * Get stock status with alternative units
     * Useful for dashboard/reports showing stock in different units
     */
    public function getStockStatus(Request $request)
    {
        try {
            $productId = $request->get('product_id');
            $product = Product::with([
                'baseUnit',
                'alternativeUnits',
                'inventoryAccount'
            ])->findOrFail($productId);

            $baseStock = $product->currentstock ?? 0;
            $value = $baseStock * ($product->purchase_price ?? 0);

            // Get minimum/reorder/maximum in base units
            $stockLevels = [
                'minimum' => $product->minimum_stock,
                'reorder' => $product->reorder_level,
                'maximum' => $product->maximum_stock,
                'status' => 'normal',
            ];

            if ($baseStock < $stockLevels['minimum']) {
                $stockLevels['status'] = 'critical';
            } else if ($baseStock < $stockLevels['reorder']) {
                $stockLevels['status'] = 'low';
            } else if ($baseStock > $stockLevels['maximum']) {
                $stockLevels['status'] = 'overstock';
            }

            // Build alternative unit representation
            $alternativeUnits = [];
            foreach ($product->alternativeUnits as $altUnit) {
                $conversionFactor = $altUnit->pivot->conversion_factor;
                $altStock = $baseStock / $conversionFactor;
                
                $alternativeUnits[] = [
                    'unit_name' => $altUnit->name,
                    'unit_symbol' => $altUnit->symbol,
                    'stock' => round($altStock, 4),
                    'is_purchase_unit' => (bool) $altUnit->pivot->is_purchase_unit,
                    'is_sales_unit' => (bool) $altUnit->pivot->is_sales_unit,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'product_name' => $product->name,
                    'base_unit' => [
                        'symbol' => $product->baseUnit->symbol,
                        'stock' => round($baseStock, 4),
                    ],
                    'alternative_units' => $alternativeUnits,
                    'stock_value' => round($value, 2),
                    'stock_levels' => $stockLevels,
                    'inventory_account' => $product->inventoryAccount ? [
                        'code' => $product->inventoryAccount->code,
                        'name' => $product->inventoryAccount->name,
                    ] : null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get stock status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching stock status',
            ], 500);
        }
    }
}