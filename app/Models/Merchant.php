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
        'email',
        'phone',
        'address',
        'description',
        'status',
    ];

    protected $casts = [
        'owner_user_id' => 'integer',
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

    public function orders()
    {
        return $this->hasMany(MerchantOrder::class, 'merchant_id', 'id');
    }
}
