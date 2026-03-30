<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    // 👇 AGREGA ESTO 👇
    protected $fillable = [
        'trabajo_id',
        'descripcion',
        'solucion',
        'fecha'
    ];

    public function trabajo()
    {
        return $this->belongsTo(Trabajo::class);
    }

    public function imagenes()
    {
        return $this->hasMany(ImagenReporte::class);
    }
}
