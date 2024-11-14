<?php

namespace App\Http\Controllers;

use App\Models\VehicleCategory;
use App\Models\VehicleMake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Fetch all vehicle categories with related models
            $categories = VehicleCategory::with(['spareParts', 'createdBy', 'updatedBy', 'deletedBy'])->get();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle categories retrieved successfully',
                'result' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve vehicle categories',
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
                'category_name' => 'required|unique:vehicle_categories',
                'category_image' => 'required|file'
            ]);

            // If validation fails, return error response
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $category_name = $request->input('category_name');
            $category_image = $request->file('category_image');

            $filename = 'sparepart-connect-' . $category_name . '.' . $category_image->getClientOriginalExtension();
            $path = $category_image->move(\public_path('images/categories'), $filename);
            $url = asset("images/categories/" . $filename);

            // Create new vehicle category
            $category = VehicleCategory::create([
                'category_name' => $category_name,
                'category_image_path' => $url,
                'created_by' => $request->user()->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Vehicle category created successfully',
                'result' => $category
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create vehicle category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a single resource.
     */
    public function show(VehicleCategory $vehicleCategory)
    {
        try {
            // Fetch all vehicle categories with related models
            $vehicleCategory->load(['spareParts', 'createdBy', 'updatedBy', 'deletedBy'])->get();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle category retrieved successfully',
                'result' => $vehicleCategory
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve vehicle category',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VehicleCategory $vehicleCategory)
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
                'category_name' => 'required|unique:vehicle_categories,category_name,' . $vehicleCategory->id,
            ]);

            // If validation fails, return error response
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update vehicle category using update method
            $vehicleCategory->update([
                'category_name' => $request->input('category_name'),
                'updated_by' => $request->user()->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Vehicle category updated successfully',
                'result' => $vehicleCategory
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update vehicle category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update_image(Request $request, $id)
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
                'category_image' => 'file',
            ]);
            $vehicleCategory = VehicleCategory::find($id);
            // If validation fails, return error response
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->hasFile('category_image')) {
                // Delete existing image in storage

                $category_image = $request->file('category_image');

                $filename = 'sparepart-connect-' . $vehicleCategory->category_name . '.' . $category_image->getClientOriginalExtension();
                $path = $category_image->move(\public_path('images/categories'), $filename);
                $url = asset("images/categories/" . $filename);

                // Update vehicle category image
                $vehicleCategory->update([
                    'category_image_path' => $url,
                    'updated_by' => $request->user()->id
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Vehicle category image updated successfully',
                'result' => $vehicleCategory
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update vehicle category image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, VehicleCategory $vehicleCategory)
    {
        try {
            if ($request->user()->role != 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only admin can perform this action.',
                    'error' => 'Unauthorized',
                ]);
            }
            $vehicleCategory->update(['deleted_by' => $request->user()->id]);
            $vehicleCategory->delete();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle Category deleted successfully.',
                'result' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete vehicle category.',
                'error' => $e,
            ]);
        }
    }
}
