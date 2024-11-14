<!DOCTYPE html>
<html>

<head>
    <title>New Order Received</title>
</head>

<body>
    <h1>New Order Received</h1>
    <p>You have received a new order!</p>
    <p>Order ID: {{ $order->id }}</p>
    <p>Order Details:</p>
    <ul>
        @foreach($order->orderItems as $item)
        @if($item->sparePart->shop_id == $shop->id)
        <li>{{ $item->sparePart->sparepart_name }} - Quantity: {{ $item->quantity }} - Price: ${{ $item->price }}</li>
        @endif
        @endforeach
    </ul>
    <p>Total: ${{ $order->orderItems->where('sparePart.shop_id', $shop->id)->sum('price') }}</p>
</body>

</html>