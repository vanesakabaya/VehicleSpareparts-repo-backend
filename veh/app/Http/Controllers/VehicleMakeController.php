<?php

namespace App\Http\Controllers;

use App\Models\VehicleMake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleMakeController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        try {
            // Fetch all vehicle makes with related models
            $makes = VehicleMake::with(['spareParts', 'createdBy', 'updatedBy', 'deletedBy'])->get();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle makes retrieved successfully',
                'result' => $makes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve vehicle makes',
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
                'manufacturer_name' => 'required|unique:vehicle_makes',
                'manufacturer_image' => 'required|image'
            ]);

            // If validation fails, return error response
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $manufacturer_name = $request->input('manufacturer_name');
            $manufacturer_image = $request->file('manufacturer_image');

            $filename = 'sparepart-connect-' . $manufacturer_name . '.' . $manufacturer_image->getClientOriginalExtension();
            $path = $manufacturer_image->move(\public_path('images/makes'), $filename);
            $url = asset("images/makes/" . $filename);


            // Create new vehicle make
            $make = VehicleMake::create([
                'manufacturer_name' => $manufacturer_name,
                'manufacturer_image_path' => $url,
                'created_by' => $request->user()->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Vehicle make created successfully',
                'result' => $make
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create vehicle make',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VehicleMake $vehicleMake)
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
                'manufacturer_name' => 'required|unique:vehicle_makes,manufacturer_name,' . $vehicleMake->id
            ]);

            // If validation fails, return error response
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update vehicle make using update method
            $vehicleMake->update([
                'manufacturer_name' => $request->input('manufacturer_name'),
                'updated_by' => $request->user()->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Vehicle make updated successfully',
                'result' => $vehicleMake
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update vehicle make',
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
                'manufacturer_image' => 'file',
            ]);
            $vehicleMake = VehicleMake::find($id);
            // If validation fails, return error response
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->hasFile('manufacturer_image')) {
                // Delete existing image in storage

                $manufacturer_image = $request->file('manufacturer_image');

                $filename = 'sparepart-connect-' . $vehicleMake->manufacturer_name . '.' . $manufacturer_image->getClientOriginalExtension();
                $path = $manufacturer_image->move(\public_path('images/categories'), $filename);
                $url = asset("images/categories/" . $filename);

                // Update vehicle category image
                $vehicleMake->update([
                    'manufacturer_image_path' => $url,
                    'updated_by' => $request->user()->id
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Vehicle category image updated successfully',
                'result' => $vehicleMake
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
     * Display a listing of the resource.
     */

    public function show(VehicleMake $vehicleMake)
    {
        try {
            // Fetch all vehicle makes with related models
            $vehicleMake->load(['spareParts', 'createdBy', 'updatedBy', 'deletedBy'])->get();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle makes retrieved successfully',
                'result' => $vehicleMake
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve vehicle makes',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, VehicleMake $vehicleMake)
    {
        try {
            if ($request->user()->role != 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only admin can perform this action.',
                    'error' => 'Unauthorized',
                ]);
            }
            $vehicleMake->update(['deleted_by' => $request->user()->id]);
            $vehicleMake->delete();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle make deleted successfully.',
                'error' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete vehicle make.',
                'error' => $e,
            ]);
        }
    }
}
