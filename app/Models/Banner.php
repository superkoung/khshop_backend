<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'title',
        'description',
        'image_path',
        'is_active',
    ];

    public function category(){
        return $this->belongsTo(Category::class,'menu_id');
    }
}
