<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'address_id',
        'users_id',
        'status',
        'subtotal',
        'total',
        'shipping_cost',
    ];
    
    public function address(): BelongsTo
    {
        return $this->BelongsTo(Address::class);
    }

    public function user(): BelongsTo
    {
        return $this->BelongsTo(User::class);
    }
    
    


    
}
