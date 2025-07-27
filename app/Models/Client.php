<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id', 'user_id',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // relationships users
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // relationships store
    public function store(){
        return $this->belongsTo(Store::class);
    }
}
