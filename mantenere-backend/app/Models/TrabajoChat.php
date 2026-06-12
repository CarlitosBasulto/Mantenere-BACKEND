<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrabajoChat extends Model
{
    protected $fillable = ['trabajo_id', 'sender_id', 'message'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
