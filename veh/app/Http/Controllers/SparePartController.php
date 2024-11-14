<?php

namespace App\Http\Controllers;

use App\Models\SparePart;
use App\Models\SparePartVehicleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SparePartController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        try {
            // Start with querying all spare parts
            $query = SparePart::with('images', 'shop', 'vehicleMake', 'unit', 'vehicleCategories');

            // Filter by shop_id if it's provided in the parameters
            if ($request->has('shop_id')) {
                $query->where('shop_id', $request->shop_id);
            }

            // Filter by vehicle_make_id if it's provided in the parameters
            if ($request->has('vehicle_make_id')) {
                $query->where('vehicle_make_id', $request->vehicle_make_id);
            }

            // Filter by category_id if it's provided in the parameters
            if ($request->has('category_id')) {
                $query->whereHas('vehicleCategories', function ($q) use ($request) {
                    $q->where('vehicle_categories.id', $request->category_id);
                });
            }

            // Fetch spare parts based on the applied filters
            $spareParts = $query->get();

            return response()->json([
                'status' => true,
                'message' => 'Spare parts retrieved successfully',
                'result' => $spareParts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve spare parts',
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
            // Validate request data
            $validator = Validator::make($request->all(), [
                'sparepart_name' => 'required',
                'shop_id' => 'required|exists:shops,id',
                'vehicle_make_id' => 'required|exists:vehicle_makes,id',
                'unit_id' => 'required|exists:units,id',
                'description' => 'required',
                'price' => 'required|numeric',
                'vehicle_categories' => 'required|array|min:0',
            ]);

            // If validation fails, return error response
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $slug = Str::slug($request->input('sparepart_name'), '-');
            $unique_slug = $this->getUniqueSlug($slug, SparePart::class, 'slug');
            $user = $request->user();

            // Start a database transaction
            DB::beginTransaction();

            // Create new spare part
            $sparePart = new SparePart();
            $sparePart->sparepart_name = $request->input('sparepart_name');
            $sparePart->slug = $unique_slug;
            $sparePart->shop_id = $request->input('shop_id');
            $sparePart->vehicle_make_id = $request->input('vehicle_make_id');
            $sparePart->unit_id = $request->input('unit_id');
            $sparePart->description = $request->input('description');
            $sparePart->price = $request->input('price');
            $sparePart->created_by = $user->id;

            // Save the spare part
            $sparePart->save();

            // Handle spare part images
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                foreach ($images as $index => $image) {
                    $filename = 'vsl-' . $unique_slug . '-' . $index . '.' . $image->getClientOriginalExtension();
                    $path = $image->move(\public_path('images/spareparts'), $filename);
                    $url = asset("images/spareparts/" . $filename);
                    $sparePart->images()->create(['spare_part_id' => $sparePart->id, 'image_url' => $url, 'created_by' => $user->id]);
                }
            }

            // Create vehicle categories for spare part
            foreach ($request->input('vehicle_categories') as $item) {
                $sparePartVehicleCategory = new SparePartVehicleCategory([
                    'spare_part_id' => $sparePart->id,
                    'vehicle_category_id' => $item,
                    'created_by' => $user->id,
                ]);
                $sparePartVehicleCategory->save();
            }

            // Commit the transaction
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Spare part created successfully',
                'result' => $sparePart
            ], 201);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an exception
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create spare part',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SparePart $sparePart)
    {
        try {
            // Start with querying all spare parts
            $sparePart->load('images', 'shop', 'vehicleMake', 'unit', 'vehicleCategories');

            return response()->json([
                'status' => true,
                'message' => 'Spare part retrieved successfully',
                'result' => $sparePart
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve spare parts',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SparePart $sparePart)
    {
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'sparepart_name' => 'required',
                'shop_id' => 'required|exists:shops,id',
                'vehicle_make_id' => 'required|exists:vehicle_makes,id',
                'unit_id' => 'required|exists:units,id',
                'description' => 'required',
                'price' => 'required|numeric'
            ]);

            if ($request->user()->role != 'admin' && $request->user()->id != $sparePart->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only admin or owner of spare part can delete it.',
                    'error' => 'Unauthorized',
                ]);
            }
            // If validation fails, return error response
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            $slug = Str::slug($request->input('sparepart_name'), '-');
            $unique_slug = $this->getUniqueSlug($slug, SparePart::class, 'slug');
            $user = $request->user();

            // Start a database transaction
            DB::beginTransaction();

            // Update spare part attributes
            $sparePart->sparepart_name = $request->input('sparepart_name');
            $sparePart->slug = $unique_slug;
            $sparePart->shop_id = $request->input('shop_id');
            $sparePart->vehicle_make_id = $request->input('vehicle_make_id');
            $sparePart->unit_id = $request->input('unit_id');
            $sparePart->description = $request->input('description');
            $sparePart->price = $request->input('price');
            $sparePart->updated_by = $user->id;

            // Save the updated spare part
            $sparePart->save();

            // Create vehicle categories for spare part
            foreach ($request->input('vehicle_categories') as $item) {
                $sparePartVehicleCategory = new SparePartVehicleCategory([
                    'spare_part_id' => $sparePart->id,
                    'vehicle_category_id' => $item,
                    'created_by' => $user->id,
                ]);
                $sparePartVehicleCategory->save();
            }

            // Commit the transaction
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Spare part updated successfully',
                'result' => $sparePart
            ]);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an exception
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to update spare part',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, SparePart $sparePart)
    {
        try {
            if ($request->user()->role != 'admin' && $request->user()->id != $sparePart->shop->owner->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only admin or owner of spare part can delete it.',
                    'error' => 'Unauthorized',
                ]);
            }
            $sparePart->update(['deleted_by' => $request->user()->id]);
            $sparePart->delete();

            return response()->json([
                'status' => true,
                'message' => 'Spare part deleted successfully.',
                'result' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete spare part.',
                'error' => $e,
            ]);
        }
    }

    function getUniqueSlug($slug, $model, $column = 'slug')
    {
        $originalSlug = $slug;
        $count = 2;

        while ($model::where($column, '=', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
