<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
        protected $fillable = [
        'users_id',
        'recipient_name',
        'description',
        'zip_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
    ];

    public function user(): BelongsTo
    {
        return $this->BelongsTo(User::class);
    }


}
