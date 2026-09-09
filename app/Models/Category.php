<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'image_path',
        'slug'
    ];

    public function products(){
        return $this->hasMany(Product::class);
    }
    public function children(){
        return $this->hasMany(Category::class,'parent_id','id');
    }
    public function parent(){
        return $this->belongsTo(Category::class,'parent_id','id');
    }
    public function banner(){
        return $this->hasOne(Banner::class,'menu_id');
    }
}
