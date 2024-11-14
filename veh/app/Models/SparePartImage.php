<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SparePartImage extends Model
{
    use HasFactory;

    protected $fillable = ['spare_part_id', 'image_url', 'created_by'];

    public function spare_part()
    {
        return $this->belongsTo(SparePart::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
