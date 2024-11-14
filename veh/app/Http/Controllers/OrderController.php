<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Mail\CustomerOrderCreated;
use App\Mail\VendorOrderCreated;
use Illuminate\Support\Facades\Mail;


class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try { // Get filtering parameters from the request
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $shopId = $request->input('shop_id');
            $status = $request->input('status');
            $paid = $request->input('paid');

            // Start building query to retrieve orders
            $query = Order::with('client', 'orderItems.sparePart.unit', 'orderItems.sparePart.images', 'orderItems.orderItemStatus');

            // Filter by created_at range
            if ($fromDate && $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            }

            // Join with order items table to filter by shop_id, status, and paid
            $query->whereHas('orderItems', function ($q) use ($shopId, $status, $paid) {
                if ($shopId) {
                    $q->where('shop_id', $shopId);
                }
                if ($status) {
                    $q->whereHas('orderItemStatus', function ($q) use ($status) {
                        $q->where('status', $status);
                    });
                }
                if ($paid !== null) {
                    $q->where('paid', $paid);
                }
            });

            // Retrieve orders
            $orders = $query->get();

            // Return JSON response
            return response()->json([
                'status' => true,
                'message' => 'Orders retrieved successfully',
                'result' => $orders
            ]);
        } catch (\Exception $e) {
            // Handle exception
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage()
            ], 500);
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
                'total_amount' => 'required|numeric',
                'order_items' => 'required|array|min:1',
                'order_items.*.id' => 'required|exists:spare_parts,id',
                'order_items.*.quantity' => 'required|integer|min:1',
                'order_items.*.price' => 'required|numeric|min:0',
            ]);

            // If validation fails, return error response
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Start a database transaction
            DB::beginTransaction();

            // Create new order
            $order = Order::create([
                'user_id' => $request->input('user_id'),
                'total_amount' => $request->input('total_amount'),
            ]);

            // Create order items and their initial status
            foreach ($request->input('order_items') as $item) {
                $orderItem = new OrderItem([
                    'order_id' => $order->id,
                    'spare_part_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
                $orderItem->save();

                // Create initial status for the order item
                OrderItemStatus::create([
                    'order_item_id' => $orderItem->id,
                    'status' => 'pending',
                    'placed_at' => now()
                ]);
            }

            // Commit the transaction
            DB::commit();

            // Send emails
            // Mail::to($request->user()->email)->send(new CustomerOrderCreated($order));

            // $shops = $order->orderItems->pluck('sparePart.shop')->unique();

            // foreach ($shops as $shop) {
            //     Mail::to($shop->owner->email)->send(new VendorOrderCreated($order, $shop));
            // }

            // Return JSON response with success message and the created order
            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an exception
            DB::rollBack();

            // Handle exception
            return response()->json([
                'status' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
