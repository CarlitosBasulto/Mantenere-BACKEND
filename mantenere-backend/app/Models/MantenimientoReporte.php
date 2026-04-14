<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MantenimientoReporte extends Model
{
    use HasFactory;

    protected $table = 'mantenimiento_reportes';

    protected $fillable = [
        'mantenimiento_solicitud_id',
        'tecnico_id',
        'diagnostico_final',
        'observaciones',
        'materiales_usados',
        'evidencia_antes',
        'evidencia_durante',
        'evidencia_despues',
        'archivo_firmado'
    ];

    protected $casts = [
        'evidencia_antes' => 'array',
        'evidencia_durante' => 'array',
        'evidencia_despues' => 'array',
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
