<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_order_id',
        'merchant_product_id',
        'product_name',
        'quantity',
        'unit_price',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'merchant_order_id' => 'integer',
        'merchant_product_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'double',
        'total_price' => 'double',
    ];

    public function order()
    {
        return $this->belongsTo(MerchantOrder::class, 'merchant_order_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(MerchantProduct::class, 'merchant_product_id', 'id');
    }
}
