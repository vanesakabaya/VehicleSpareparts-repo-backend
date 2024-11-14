<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'shop_type', 'shop_name', 'address', 'phone', 'email', 'active', 'created_by', 'updated_by', 'deleted_by'
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function spareParts()
    {
        return $this->hasMany(SparePart::class);
    }
}
