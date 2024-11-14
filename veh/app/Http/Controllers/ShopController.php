<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shop;
use App\Models\SparePart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Start with querying all shops
            $query = Shop::with(['owner', 'spareParts']);

            // Filter by user_id if it's provided in the parameters
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by shop_type if it's provided in the parameters
            if ($request->has('shop_type')) {
                $query->where('shop_type', $request->shop_type);
            }

            // Filter by date range of created_at if provided in the parameters
            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
            }

            // Fetch shops based on the applied filters
            $shops = $query->get();

            return response()->json([
                'status' => true,
                'message' => 'Shops retrieved successfully',
                'result' => $shops
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve shops',
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
                'user_id' => 'required|exists:users,id',
                'shop_type' => 'required|in:spareparts,garage',
                'shop_name' => 'required',
                'address' => 'required',
                'phone' => 'required',
                'email' => 'nullable'
            ]);

            // If validation fails, return error response
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }
            $phone = preg_replace('/[^0-9]/', '', $request->input('phone'));
            // Check if phone number starts with '07' and has a length of 10
            if (substr($phone, 0, 2) == '07' && strlen($phone) == 10) {
                // Add '25' before the phone number
                $phone = '25' . $phone;
            }

            if (!(substr($phone, 0, 4) == '2507' && strlen($phone) == 12)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid business phone number. Please check phone number.',
                    'result' => null,
                ]);
            }

            // Create new shop instance
            $shop = new Shop();
            $shop->user_id = $request->input('user_id');
            $shop->shop_type = $request->input('shop_type');
            $shop->shop_name = $request->input('shop_name');
            $shop->address = $request->input('address');
            $shop->phone = $phone;
            $shop->email = $request->input('email');
            $shop->active = 'yes';
            $shop->created_by = $request->user()->id;

            // Save the shop
            $shop->save();

            return response()->json([
                'status' => true,
                'message' => 'Shop created successfully',
                'result' => $shop
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create shop',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Shop $shop)
    {
        try {
            // Start with querying all shops
            $shop->load(['owner', 'spareParts']);
            return response()->json([
                'status' => true,
                'message' => 'Shops retrieved successfully',
                'result' => $shop
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve shops',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shop $shop)
    {
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'shop_type' => 'required|in:spareparts,garage',
                'shop_name' => 'required',
                'address' => 'required',
                'phone' => 'required',
                'email' => 'nullable'
            ]);

            // If validation fails, return error response
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $phone = preg_replace('/[^0-9]/', '', $request->input('phone'));
            // Check if phone number starts with '07' and has a length of 10
            if (substr($phone, 0, 2) == '07' && strlen($phone) == 10) {
                // Add '25' before the phone number
                $phone = '25' . $phone;
            }

            if (!(substr($phone, 0, 4) == '2507' && strlen($phone) == 12)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid business phone number. Please check phone number.',
                    'result' => null,
                ]);
            }
            // Update shop attributes
            $shop->user_id = $request->input('user_id');
            $shop->shop_type = $request->input('shop_type');
            $shop->shop_name = $request->input('shop_name');
            $shop->address = $request->input('address');
            $shop->phone = $phone;
            $shop->email = $request->input('email');
            $shop->active = $request->input('active');
            $shop->updated_by = $request->user()->id;

            // Save the updated shop
            $shop->save();

            return response()->json([
                'status' => true,
                'message' => 'Shop updated successfully',
                'result' => $shop
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update shop',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Shop $shop)
    {
        try {
            if ($request->user()->role != 'admin' && $request->user()->id != $shop->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only admin or owner of shop can delete it.',
                    'error' => 'Unauthorized',
                ]);
            }
            $shop->update(['deleted_by' => $request->user()->id]);
            $shop->delete();

            return response()->json([
                'status' => true,
                'message' => 'Shop deleted successfully.',
                'result' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete shop.',
                'error' => $e,
            ]);
        }
    }

    public function statistics(Request $request)
    {
        $user = $request->user();
        $shop_id = null;

        if ($user->role != 'admin') {
            // Fetch shop based on user ID
            $userShop = Shop::where('user_id', $user->id)->first();
            if ($userShop) {
                $shop_id = $userShop->id;
            } else {
                return response([
                    'status' => false,
                    'message' => 'No shop you have on VSL'
                ]);
            }
        } else {
            $shop_id = $request->input('shop_id');
        }

        $total_orders = Order::whereHas('orderItems.sparePart', function ($query) use ($shop_id) {
            if ($shop_id) {
                $query->where('shop_id', $shop_id);
            }
        })
            ->count();

        $total_clients = User::where('role', 'vehicle_owner')->count();

        $total_active_items = SparePart::where(function ($query) use ($shop_id) {
            if ($shop_id) {
                $query->where('shop_id', $shop_id);
            }
        })
            ->count();

        $total_items = SparePart::withTrashed()->when($shop_id, function ($query) use ($shop_id) {
            if ($shop_id) {
                return $query->where('shop_id', $shop_id);
            }
        })
            ->count();

        $total_active_vendors = Shop::where('active', 'yes')->count();
        $total_vendors = Shop::count();

        $response = [
            'status' => true,
            'total_orders' => $total_orders,
            'total_clients' => $total_clients,
            'total_vendors' => $total_vendors,
            'total_active_vendors' => $total_active_vendors,
            'total_items' => $total_items,
            'total_active_items' => $total_active_items
        ];
        return response($response);
    }
}
