<?php

namespace App\Http\Controllers;

use App\Models\CustomerGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerGroupController extends Controller
{
    public function index()
    {
        return view('customer-groups.index');
    }

    public function getData(Request $request)
    {
        $query = CustomerGroup::withCount('customers');

        // Search
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $totalRecords = CustomerGroup::count();
        $filteredRecords = $query->count();

        // Sorting
        $orderColumn = $request->order[0]['column'] ?? 0;
        $orderDir = $request->order[0]['dir'] ?? 'asc';
        
        $columns = ['name', 'description', 'customers_count', 'is_active'];
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        // Pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $groups = $query->skip($start)->take($length)->get();

        $data = $groups->map(function($group) {
            $statusBadge = $group->is_active 
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-secondary">Inactive</span>';

            return [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description ?? '-',
                'customers_count' => '<span class="badge badge-info">' . $group->customers_count . ' customers</span>',
                'status' => $statusBadge,
                'actions' => view('customer-groups.partials.actions', compact('group'))->render()
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    public function create()
    {
        return view('customer-groups.create');
    }

public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255|unique:customer_groups,name',
        'description' => 'nullable|string',
    ]);

    try {
        CustomerGroup::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'), // Properly handle checkbox as boolean
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer group created successfully!'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error creating customer group: ' . $e->getMessage()
        ], 500);
    }
}


    public function edit(CustomerGroup $customerGroup)
    {
        return view('customer-groups.edit', compact('customerGroup'));
    }

public function update(Request $request, CustomerGroup $customerGroup)
{
    $request->validate([
        'name' => ['required', 'string', 'max:255', Rule::unique('customer_groups')->ignore($customerGroup->id)],
        'description' => 'nullable|string',
    ]);

    try {
        $customerGroup->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'), // Properly handle checkbox as boolean
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer group updated successfully!'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error updating customer group: ' . $e->getMessage()
        ], 500);
    }
}


    public function destroy(CustomerGroup $customerGroup)
    {
        try {
            // Check if group has customers
            if ($customerGroup->customers()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete group with existing customers!'
                ], 400);
            }

            $customerGroup->delete();

            return response()->json([
                'success' => true,
                'message' => 'Customer group deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting customer group: ' . $e->getMessage()
            ], 500);
        }
    }
}
