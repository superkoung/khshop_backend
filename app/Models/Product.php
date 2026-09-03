<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'category_id',
        'brand_id',
        'gender_id',
        'name',
        'slug',
        'description',
        'price',
        'is_active',
    ];

    public function variants(){
        return $this->hasMany(Product_variant::class,'product_id');
    }
    public function category(){
        return $this->belongsTo(Category::class);
    }
    public function brand(){
        return $this->belongsTo(Brand::class);
    }
}
