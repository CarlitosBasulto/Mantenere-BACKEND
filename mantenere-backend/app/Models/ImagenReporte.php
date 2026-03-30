<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagenReporte extends Model
{
    // Asegúrarnos de que apunte a la tabla correcta por si Laravel se confunde con el plural
    protected $table = 'imagenes_reportes';

    protected $fillable = [
        'reporte_id',
        'ruta' // Coincide con tu migración
    ];

    public function reporte()
    {
        return $this->belongsTo(Reporte::class);
    }
}
