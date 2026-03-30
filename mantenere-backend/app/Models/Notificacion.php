<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones'; // Asegurándonos de que Laravel no se confunda con el plural

    protected $fillable = [
        'user_id',
        'mensaje',
        'leido'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
