<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActividadEquipo extends Model
{
    use HasFactory;

    protected $table = 'actividad_equipos';

    protected $fillable = [
        'actividad_id',
        'tipo',
        'marca',
        'modelo',
        'piezas',
        'garantia'
    ];

    public function actividad()
    {
        return $this->belongsTo(Actividad::class);
    }
}
