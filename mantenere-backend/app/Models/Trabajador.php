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
        'admin_autonomo_id',
        'creador_id',
        'proveedor_id',
        'es_proveedor',
        'fecha_nacimiento',
        'direccion',
        'rfc'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Trabajador::class, 'proveedor_id');
    }

    public function escuadron()
    {
        return $this->hasMany(Trabajador::class, 'proveedor_id');
    }

    public function trabajos()
    {
        return $this->hasMany(Trabajo::class);
    }
}
