<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory;

    protected $fillable = [
        'dealership_id', 'user_id',
    ];

    // relationships users
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
