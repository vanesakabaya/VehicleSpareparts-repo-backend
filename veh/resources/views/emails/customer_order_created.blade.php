<!DOCTYPE html>
<html>

<head>
    <title>Order Confirmation</title>
</head>

<body>
    <h1>Order Confirmation</h1>
    <p>Thank you for your order!</p>
    <p>Order ID: {{ $order->id }}</p>
    <p>Order Details:</p>
    <ul>
        @foreach($order->orderItems as $item)
        <li>{{ $item->sparePart->sparepart_name }} - Quantity: {{ $item->quantity }} {{ $item->sparePart->unit->unit_name }} - Price: {{ $item->price }}RWF</li>
        @endforeach
    </ul>
    <p>Total: {{ $order->orderItems->sum('price') }}RWF</p>
</body>

</html>