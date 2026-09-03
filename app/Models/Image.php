<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $table = 'product_images';

    public function variant(){
        return $this->belongsTo(Product_variant::class,'variant_id');
    }
}
