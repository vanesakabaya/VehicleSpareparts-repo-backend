<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemStatus extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_item_id', 'status', 'created_by', 'updated_by', 'deleted_by'
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
