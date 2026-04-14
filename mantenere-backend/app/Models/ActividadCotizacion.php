<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActividadCotizacion extends Model
{
    use HasFactory;

    protected $table = 'actividad_cotizaciones';

    protected $fillable = [
        'actividad_id',
        'monto',
        'detalles'
    ];

    public function actividad()
    {
        return $this->belongsTo(Actividad::class);
    }
}
