<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    /**
     * Fields ដែលអនុញ្ញាតឱ្យធ្វើ Mass Assignment
     */
    protected $fillable = [
        'name',
        'contact_name',
        'phone',
        'email',
        'address',
        'is_active',
        'notes',
    ];

    /**
     * កំណត់ Type Casting
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationship ទៅកាន់ Products (Many-to-Many)
     * តាមរយៈ Pivot Table: product_suppliers
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_suppliers')
                    ->withPivot('supplier_sku', 'cost_price', 'is_primary')
                    ->withTimestamps();
    }

    /**
     * Relationship ទៅកាន់ Pivot Model ដោយផ្ទាល់ (One-to-Many)
     */
    public function productSuppliers()
    {
        return $this->hasMany(ProductSupplier::class);
    }
}
