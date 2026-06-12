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
        'fecha_programada',
        'foto_url',
        'admin_autonomo_id'
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

    public function mantenimientoSolicitudVisita()
    {
        return $this->hasOne(MantenimientoSolicitud::class, 'visita_trabajo_id');
    }

    public function mantenimientoSolicitudReparacion()
    {
        return $this->hasOne(MantenimientoSolicitud::class, 'reparacion_trabajo_id');
    }
}
