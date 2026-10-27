<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    

    protected $fillable = [
        'order_id',
        'status',
        'amount',
    ];
    
    public function order(): BelongsTo
    {
        return $this->BelongsTo(Order::class);
    }

}

/*
php artisan make:controller Api/AddressController --api
php artisan make:controller Api/OrderController --api
php artisan make:controller Api/PaymentController --api

*/
