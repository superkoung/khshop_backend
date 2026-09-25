<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'method',
        'amount_paid',
        'tendered_amount',
        'change_amount',
        'transaction_id',
        'status',
        'paid_at',
        'qr',
        'md5',
    ];

    protected $casts = [
        'amount_paid'    => 'decimal:2',
        'tendered_amount' => 'decimal:2',
        'change_amount'   => 'decimal:2',
        'paid_at'         => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
