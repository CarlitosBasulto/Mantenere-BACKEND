<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LevantamientoEquipo extends Model
{
    use HasFactory;
    
    protected $table = 'levantamiento_equipos'; // Forzar nombre de tabla
    
    protected $fillable = [
        'levantamiento_area_id', 'nombre', 'marca', 'modelo', 
        'serie', 'anioFabricacion', 'anioUso', 'foto', 'fotoPlaca'
    ];
}
