<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistEquipo extends Model
{
    use HasFactory;

    // Con esta propiedad evitamos que Laravel busque 'checklist_equipos' en vez de 'checklist_equipo'
    protected $table = 'checklist_equipo';

    protected $fillable = [
        'trabajo_id',
        'tipo',
        'nombre',
        'checked'
    ];

    public function trabajo()
    {
        return $this->belongsTo(Trabajo::class);
    }
}
