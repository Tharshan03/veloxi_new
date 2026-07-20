<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchantCheckoutRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|string|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'selected_address_id' => 'nullable|integer|exists:user_addresses,id',
            'new_delivery_address' => 'nullable|string|max:1000',
            'new_delivery_latitude' => 'nullable|numeric',
            'new_delivery_longitude' => 'nullable|numeric',
            'delivery_address' => 'nullable|string|max:1000',
            'delivery_address_line2' => 'nullable|string|max:1000',
            'delivery_city' => 'nullable|string|max:255',
            'delivery_postal_code' => 'nullable|string|max:20',
            'delivery_instructions' => 'nullable|string|max:1000',
            'pickup_time' => 'nullable|date',
        ];
    }
}
