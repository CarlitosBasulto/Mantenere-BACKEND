<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trabajo extends Model
{
    // Permitimos que Laravel llene todas estas columnas cuando enviemos datos desde React
    protected $fillable = [
        'titulo',
        'descripcion',
        'prioridad',
        'estado',
        'tipo',
        'fechaAsignada',
        'horaAsignada',
        'visitado',
        'trabajador_id',
        'negocio_id',
        'fecha_programada'
    ];

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function negocio()
    {
        return $this->belongsTo(Negocio::class);
    }

    public function reporte()
    {
        return $this->hasOne(Reporte::class);
    }
}
