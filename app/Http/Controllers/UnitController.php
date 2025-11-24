<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class UnitController extends Controller
{
    /**
     * Display a listing of the units.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Unit::query();
            
            return DataTables::eloquent($data)
                ->addIndexColumn()
                ->addColumn('is_base_unit', function ($row) {
                    return $row->is_base_unit 
                        ? '<span class="badge badge-success">Yes</span>' 
                        : '<span class="badge badge-secondary">No</span>';
                })
                ->addColumn('is_active', function ($row) {
                    return $row->is_active 
                        ? '<span class="badge badge-success">Active</span>' 
                        : '<span class="badge badge-danger">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    $editBtn = '<a href="' . route('units.edit', $row->id) . '" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>';
                    $deleteBtn = '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '"><i class="fas fa-trash"></i></button>';
                    
                    return '<div class="btn-group">' . $editBtn . ' ' . $deleteBtn . '</div>';
                })
                ->rawColumns(['is_base_unit', 'is_active', 'action'])
                ->make(true);
        }
        
        return view('units.index');
    }

    /**
     * Show the form for creating a new unit.
     */
    public function create()
    {
        return view('units.create');
    }

    /**
     * Store a newly created unit in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:units,name',
            'symbol' => 'required|string|max:10|unique:units,symbol',
            'type' => 'required|in:simple,compound',
            'is_base_unit' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        // Set default values for checkboxes
        $validated['is_base_unit'] = $request->has('is_base_unit') ? 1 : 0;
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        Unit::create($validated);

        return redirect()
            ->route('units.index')
            ->with('success', 'Unit created successfully!');
    }

    /**
     * Display the specified unit.
     */
    public function show(Unit $unit)
    {
        return view('units.show', compact('unit'));
    }

    /**
     * Show the form for editing the specified unit.
     */
    public function edit(Unit $unit)
    {
        return view('units.edit', compact('unit'));
    }

    /**
     * Update the specified unit in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('units', 'name')->ignore($unit->id),
            ],
            'symbol' => [
                'required',
                'string',
                'max:10',
                Rule::unique('units', 'symbol')->ignore($unit->id),
            ],
            'type' => 'required|in:simple,compound',
            'is_base_unit' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        // Set default values for checkboxes
        $validated['is_base_unit'] = $request->has('is_base_unit') ? 1 : 0;
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        $unit->update($validated);

        return redirect()
            ->route('units.index')
            ->with('success', 'Unit updated successfully!');
    }

    /**
     * Remove the specified unit from storage.
     */
    public function destroy(Unit $unit)
    {
        // Check if unit is in use
        if ($unit->isInUse()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete unit. It is being used by products.'
            ], 422);
        }

        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Unit deleted successfully!'
        ]);
    }
}
