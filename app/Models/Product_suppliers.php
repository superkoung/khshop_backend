<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product_suppliers extends Model
{
    use HasFactory;

    /**
     * ឈ្មោះ Table ក្នុង Database (ករណី Model ប្រើ _ )
     */
    protected $table = 'product_suppliers';

    /**
     * Fields ដែលអនុញ្ញាតឱ្យធ្វើ Mass Assignment
     */
    protected $fillable = [
        'product_id',
        'supplier_id',
        'supplier_sku',
        'cost_price',
        'is_primary',
    ];

    /**
     * កំណត់ Type Casting ឱ្យត្រូវតាម Data Type
     */
    protected $casts = [
        'cost_price' => 'decimal:2',
        'is_primary' => 'boolean',
    ];

    /**
     * Relationship ទៅកាន់ Product Model
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship ទៅកាន់ Supplier Model
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
