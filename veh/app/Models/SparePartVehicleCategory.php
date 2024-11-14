<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SparePartVehicleCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'spare_part_id', 'vehicle_category_id', 'created_by', 'updated_by', 'deleted_by'
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

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }

    public function vehicleCategory()
    {
        return $this->belongsTo(VehicleCategory::class);
    }
}
