<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        try {
            // Fetch all Units with related models
            $makes = Unit::with(['createdBy', 'updatedBy', 'deletedBy'])->get();

            return response()->json([
                'status' => true,
                'message' => 'Units retrieved successfully',
                'result' => $makes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve Units',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            if ($request->user()->role != 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only admin can perform this action.',
                    'error' => 'Unauthorized',
                ]);
            }
            // Validate request data
            $validator = Validator::make($request->all(), [
                'unit_name' => 'required|unique:units'
            ]);

            // If validation fails, return error response
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $unit_name = $request->input('unit_name');

            // Create new unit
            $make = Unit::create([
                'unit_name' => $unit_name,
                'created_by' => $request->user()->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Unit created successfully',
                'result' => $make
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        try {
            if ($request->user()->role != 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only admin can perform this action.',
                    'error' => 'Unauthorized',
                ]);
            }
            // Validate request data
            $validator = Validator::make($request->all(), [
                'unit_name' => 'required|unique:units,unit_name,' . $unit->id
            ]);

            // If validation fails, return error response
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update unit using update method
            $unit->update([
                'unit_name' => $request->input('unit_name'),
                'updated_by' => $request->user()->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Unit updated successfully',
                'result' => $unit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Unit $unit)
    {
        try {
            if ($request->user()->role != 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only admin can perform this action.',
                    'error' => 'Unauthorized',
                ]);
            }
            $unit->update(['deleted_by' => $request->user()->id]);
            $unit->delete();

            return response()->json([
                'status' => true,
                'message' => 'Unit deleted successfully.',
                'result' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete unit.',
                'error' => $e,
            ]);
        }
    }
}
