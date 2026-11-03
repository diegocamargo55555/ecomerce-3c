<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart_item extends Model
{
    

    protected $fillable = [
        'cart_id',
        'quantity',
    ];

    public function cart(): BelongsTo
    {
        return $this->BelongsTo(Cart::class);
    }

}

/*
            $table->id();
            $table->foreignId('cart_id')->constrained();
            $table->integer('quantity');
            $table->timestamps();

*/
