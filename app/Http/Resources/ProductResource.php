<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'category_id'=>$this->category_id,
            'brand_id'=>$this->brand_id,
            'gender_id'=>$this->gender_id,
            'name'=>$this->name,
            'slug'=>$this->slug,
            'price'=>$this->price,
            'description'=>$this->description,
            'is_active'=>$this->is_active
        ];
    }
}
