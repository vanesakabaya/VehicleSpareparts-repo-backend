<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        try {
            // Retrieve filtering parameters from the request
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $shopId = $request->input('shop_id');
            $sparePartId = $request->input('spare_part_id');
            $status = $request->input('status');
            $paid = $request->input('paid');

            // Start building query to retrieve order items
            $query = OrderItem::with('order', 'sparePart.unit', 'orderItemStatus');

            // If not admin limit by own orders
            if ($request->user()->role != 'admin') {
                $query->whereHas('order', function ($q) use ($request) {
                    $q->where('user_id', $request->user()->id);
                });
            }

            // Filter by date range
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            // Filter by shop id
            if ($shopId) {
                $query->where('shop_id', $shopId);
            }

            // Filter by spare part id
            if ($sparePartId) {
                $query->where('spare_part_id', $sparePartId);
            }

            // Filter by customer
            if ($status) {
                $query->whereHas('orderItemStatus', function ($q) use ($status) {
                    $q->where('status', $status);
                });
            }

            // Filter by paid status
            if ($paid !== null) {
                $query->where('paid', $paid);
            }

            // Retrieve order items
            $orderItems = $query->get();

            // Return JSON response
            return response()->json([
                'status' => true,
                'message' => 'Order items retrieved successfully',
                'result' => $orderItems
            ]);
        } catch (\Exception $e) {
            // Handle exception
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve order items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrderItem $orderItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderItem $orderItem)
    {
        //
    }
}
