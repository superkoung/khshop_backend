<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product_variant extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'product_id',
        'size_id',
        'color_id',
        'stock',
        'price_modifier',
        'is_active',
        'sku'
    ];

    public function image()
    {
        return $this->belongsTo(Image::class, 'image_id');
    }

    public function cartItems(){
        return $this->hasMany(CartItem::class,'variant_id');
    }

    public function product(){
        return $this->belongsTo(Product::class,'product_id');
    }
    public function color(){
        return $this->belongsTo(Color::class,'color_id');
    }
    public function size(){
        return $this->belongsTo(Size::class,'size_id');
    }
    public function orderItems(){
        return $this->hasMany(OrderItem::class);
    }
}
