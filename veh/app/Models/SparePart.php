<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SparePart extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id', 'sparepart_name', 'slug', 'vehicle_make_id', 'unit_id', 'price', 'description', 'created_by', 'updated_by', 'deleted_by'
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

    public function images()
    {
        return $this->hasMany(SparePartImage::class, 'spare_part_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function vehicleMake()
    {
        return $this->belongsTo(VehicleMake::class, 'vehicle_make_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function vehicleCategories()
    {
        return $this->belongsToMany(VehicleCategory::class, 'spare_part_vehicle_categories', 'spare_part_id', 'vehicle_category_id');
    }
}
