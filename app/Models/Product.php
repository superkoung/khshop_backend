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
        'name',
        'slug',
        'description',
        'price',
        'discount_type',
        'discount_value',
        'sale_price',
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
    public function wishlists(){
        return $this->hasMany(Wishlist::class);
    }
    public function reviews(){
        return $this->hasMany(Review::class);
    }
    public function suppliers(){
        return $this->belongsToMany(Supplier::class, 'product_suppliers')
                    ->withPivot('supplier_sku', 'cost_price', 'is_primary')
                    ->withTimestamps();
    }
}
