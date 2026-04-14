<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MantenimientoVisita extends Model
{
    use HasFactory;

    protected $table = 'mantenimiento_visitas';

    protected $fillable = [
        'mantenimiento_solicitud_id',
        'tecnico_id',
        'diagnostico',
        'pieza_danada',
        'reparacion_necesaria',
        'cotizacion_tecnico'
    ];

    public function solicitud()
    {
        return $this->belongsTo(MantenimientoSolicitud::class, 'mantenimiento_solicitud_id');
    }

    public function tecnico()
    {
        return $this->belongsTo(Trabajador::class, 'tecnico_id');
    }
}
