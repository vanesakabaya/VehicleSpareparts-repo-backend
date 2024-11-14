<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id', 'spare_part_id', 'quantity', 'price', 'paid', 'created_by', 'updated_by', 'deleted_by'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }

    public function orderItemStatus()
    {
        return $this->hasMany(OrderItemStatus::class);
    }
}
