<?php

namespace App\Http\Controllers;

use App\Models\ProductGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductGroupController extends Controller
{
    /**
     * Display tree view of product groups
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Return jsTree formatted data
            return response()->json($this->buildTree());
        }
        
        return view('product-groups.index');
    }

    /**
     * Build tree structure for jsTree
     */
    private function buildTree($parentId = null)
    {
        $groups = ProductGroup::where('parent_id', $parentId)
                             ->orderBy('name')
                             ->get();
        
        $tree = [];
        
        foreach ($groups as $group) {
            $children = $this->buildTree($group->id);
            
            $node = [
                'id' => $group->id,
                'text' => $group->name . 
                         ($group->description ? ' <small class="text-muted">(' . $group->description . ')</small>' : ''),
                'icon' => 'fas fa-folder text-warning',
                'state' => [
                    'opened' => false,
                    'disabled' => !$group->is_active
                ],
                'a_attr' => [
                    'data-id' => $group->id,
                    'data-active' => $group->is_active
                ]
            ];
            
            if (count($children) > 0) {
                $node['children'] = $children;
            }
            
            $tree[] = $node;
        }
        
        return $tree;
    }

    /**
     * Store a newly created product group
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:product_groups,name',
            'parent_id' => 'nullable|exists:product_groups,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        $group = ProductGroup::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product group created successfully!',
            'data' => $group,
        ]);
    }

    /**
     * Show the form for creating a new product group
     */
    public function create()
    {
        $parentGroups = ProductGroup::active()->orderBy('name')->get();
        return view('product-groups.create', compact('parentGroups'));
    }

    /**
     * Show the form for editing the specified product group
     */
    public function edit(ProductGroup $productGroup)
    {
        $parentGroups = ProductGroup::active()
                                    ->where('id', '!=', $productGroup->id)
                                    ->orderBy('name')
                                    ->get();
        
        return view('product-groups.edit', compact('productGroup', 'parentGroups'));
    }

    /**
     * Update the specified product group
     */
    public function update(Request $request, ProductGroup $productGroup)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_groups', 'name')->ignore($productGroup->id),
            ],
            'parent_id' => [
                'nullable',
                'exists:product_groups,id',
                Rule::notIn([$productGroup->id]),
            ],
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        $productGroup->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product group updated successfully!',
            'data' => $productGroup,
        ]);
    }

    /**
     * Remove the specified product group
     */
    public function destroy(ProductGroup $productGroup)
    {
        if (!$productGroup->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete group. It has sub-groups or products.',
            ], 422);
        }

        $productGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product group deleted successfully!',
        ]);
    }

    /**
     * Get groups for dropdown (AJAX)
     */
    public function getGroups(Request $request)
    {
        $groups = ProductGroup::active()
                             ->orderBy('name')
                             ->get()
                             ->map(function ($group) {
                                 return [
                                     'id' => $group->id,
                                     'text' => $group->full_path,
                                 ];
                             });

        return response()->json($groups);
    }
}
