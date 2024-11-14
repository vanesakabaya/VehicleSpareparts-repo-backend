<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\OrderItemStatusController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SparePartController;
use App\Http\Controllers\SparePartImageController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleCategoryController;
use App\Http\Controllers\VehicleMakeController;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use App\Mail\CustomerOrderCreated;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'store']);
Route::post('/confirm_user', [UserController::class, 'confirmUser']);
Route::post('/forget_password', [UserController::class, 'forgetPassword']);
Route::get('/test-email', function () {
    $order = Order::first(); // Or create a dummy spare part object
    Mail::to('iraasaac@gmail.com')->send(new CustomerOrderCreated($order));
    return 'Email sent!';
});

// Partly Unauthorized resources
Route::apiResources([
    'vehicle_categories' => VehicleCategoryController::class,
    'vehicle_makes' => VehicleMakeController::class,
    'units' => UnitController::class,
    'shops' => ShopController::class,
    'spare_parts' => SparePartController::class,
], ['only' => ['index', 'show']]);

Route::middleware('auth:sanctum')->group(function () {
    //// Party mandatory Authorized ressources
    Route::apiResources([
        'users' => UserController::class,
        'vehicle_categories' => VehicleCategoryController::class,
        'vehicle_makes' => VehicleMakeController::class,
        'units' => UnitController::class,
        'shops' => ShopController::class,
        'spare_parts' => SparePartController::class,
        'spare_part_images' => SparePartImageController::class,
    ], ['except' => ['index', 'show']]);

    //// Full Mandatory Authorized ressources
    Route::apiResources([
        'orders' => OrderController::class,
        'order_items' => OrderItemController::class,
        'order_item_statuses' => OrderItemStatusController::class,
        'users' => UserController::class,
    ]);

    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/update_category_image/{category_id}', [VehicleCategoryController::class, 'update_image']);
    Route::post('/update_manufacturer_image/{make_id}', [VehicleMakeController::class, 'update_image']);
    Route::post('/statistics', [ShopController::class, 'statistics']);
});
