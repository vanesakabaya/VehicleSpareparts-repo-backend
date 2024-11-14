<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_name', 'category_image_path', 'created_by', 'updated_by', 'deleted_by'
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

    public function spareParts()
    {
        return $this->belongsToMany(SparePart::class, 'spare_part_vehicle_categories', 'vehicle_category_id', 'spare_part_id');
    }
}
