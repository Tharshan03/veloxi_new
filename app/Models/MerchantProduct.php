<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchantProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'name',
        'description',
        'price',
        'image',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'merchant_id' => 'integer',
        'price' => 'double',
        'status' => 'integer',
        'sort_order' => 'integer',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

    public function orderItems()
    {
        return $this->hasMany(MerchantOrderItem::class, 'merchant_product_id', 'id');
    }
}
