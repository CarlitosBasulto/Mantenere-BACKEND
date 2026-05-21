<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LevantamientoEquipo extends Model
{
    use HasFactory;
    
    protected $table = 'levantamiento_equipos'; // Forzar nombre de tabla
    
    protected $fillable = [
        'levantamiento_area_id', 'categoria_id', 'nombre', 'marca', 'modelo', 
        'serie', 'anioFabricacion', 'anioUso', 'foto', 'fotoPlaca'
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaEquipo::class, 'categoria_id');
    }

    public function area()
    {
        return $this->belongsTo(LevantamientoArea::class, 'levantamiento_area_id');
    }

    public function solicitudes()
    {
        return $this->hasMany(MantenimientoSolicitud::class, 'equipo_id');
    }
}
