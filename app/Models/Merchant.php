<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Merchant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'owner_user_id',
        'name',
        'slug',
        'logo',
        'cover_image',
        'email',
        'phone',
        'address',
        'description',
        'opening_hours',
        'is_open',
        'accepts_pickup',
        'accepts_delivery',
        'max_delivery_distance_km',
        'latitude',
        'longitude',
        'minimum_order_amount',
        'status',
    ];

    protected $casts = [
        'owner_user_id' => 'integer',
        'opening_hours' => 'array',
        'is_open' => 'boolean',
        'accepts_pickup' => 'boolean',
        'accepts_delivery' => 'boolean',
        'max_delivery_distance_km' => 'double',
        'latitude' => 'double',
        'longitude' => 'double',
        'minimum_order_amount' => 'double',
        'status' => 'integer',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(MerchantProduct::class, 'merchant_id', 'id');
    }

    public function categories()
    {
        return $this->hasMany(MerchantCategory::class, 'merchant_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(MerchantOrder::class, 'merchant_id', 'id');
    }
}
