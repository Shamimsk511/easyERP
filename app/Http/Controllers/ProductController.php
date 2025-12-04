<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Account;
use App\Models\Product;
use App\Models\ProductGroup;
use Illuminate\Http\Request;
use App\Models\ProductMovement;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

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
            ->rawColumns(['group_name', 'unit_name', 'current_stock', 'stock_value', 'rate_info', 'is_active', 'action'])
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
}
