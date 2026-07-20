<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'name',
        'slug',
        'position',
        'status',
    ];

    protected $casts = [
        'merchant_id' => 'integer',
        'position' => 'integer',
        'status' => 'integer',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(MerchantProduct::class, 'category_id', 'id');
    }
}
