<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipoHistorialRefaccion extends Model
{
    protected $fillable = [
        'actividad_id',
        'levantamiento_equipo_id',
        'categoria_id',
        'pieza',
        'cantidad',
        'costo_estimado',
    ];

    public function actividad()
    {
        return $this->belongsTo(Actividad::class, 'actividad_id');
    }

    public function equipo()
    {
        return $this->belongsTo(LevantamientoEquipo::class, 'levantamiento_equipo_id');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaEquipo::class, 'categoria_id');
    }
}
