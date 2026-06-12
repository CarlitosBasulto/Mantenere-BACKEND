<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trabajador extends Model
{

    protected $table = 'trabajadores';

    protected $fillable = [
        'nombre',
        'correo',
        'avatar',
        'telefono',
        'puesto',
        'estado',
        'user_id',
        'admin_autonomo_id'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trabajos()
    {
        return $this->hasMany(Trabajo::class);
    }
}

