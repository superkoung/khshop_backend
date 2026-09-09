<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $table = 'product_images';

    public function variants()
    {
        return $this->hasMany(Product_variant::class, 'image_id');
    }
}
