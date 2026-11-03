<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    

    protected $fillable = [
        'user_id',
        'guest_token',
    ];

    public function user(): BelongsTo
    {
        return $this->BelongsTo(User::class);
    }

}

/*
            $table->foreignId('user_id')->constrained();
            $table->string("guest_token")->nullable();

*/
