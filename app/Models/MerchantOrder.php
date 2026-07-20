<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchantOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REFUSED = 'refused';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_DELIVERY_REQUESTED = 'delivery_requested';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        self::STATUS_REFUSED,
        self::STATUS_PREPARING,
        self::STATUS_READY,
        self::STATUS_DELIVERY_REQUESTED,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'user_id',
        'merchant_id',
        'status',
        'fulfillment_type',
        'subtotal_amount',
        'subtotal',
        'delivery_fee',
        'delivery_distance_km',
        'total_amount',
        'total',
        'delivery_address_id',
        'delivery_address',
        'delivery_address_line2',
        'delivery_city',
        'delivery_postal_code',
        'delivery_instructions',
        'pickup_time',
        'delivery_latitude',
        'delivery_longitude',
        'customer_name',
        'customer_phone',
        'notes',
        'accepted_at',
        'refused_at',
        'ready_at',
        'delivered_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'merchant_id' => 'integer',
        'subtotal_amount' => 'double',
        'subtotal' => 'double',
        'delivery_fee' => 'double',
        'delivery_distance_km' => 'double',
        'total_amount' => 'double',
        'total' => 'double',
        'delivery_address_id' => 'integer',
        'pickup_time' => 'datetime',
        'delivery_latitude' => 'double',
        'delivery_longitude' => 'double',
        'accepted_at' => 'datetime',
        'refused_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(MerchantOrderItem::class, 'merchant_order_id', 'id');
    }

    public function deliveryAddress()
    {
        return $this->belongsTo(UserAddress::class, 'delivery_address_id', 'id');
    }
}
