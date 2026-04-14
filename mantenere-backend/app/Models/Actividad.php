<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    use HasFactory;

    // ESTA ES LA LÍNEA CLAVE PARA QUE NO DE ERROR 500
    protected $table = 'actividades';

    protected $fillable = [
        'trabajo_id',
        'tipo',
        'descripcion',
        'trabajador_id'
    ];

    // Relación con el técnico que realizó esta actividad específica
    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }

    public function trabajo()
    {
        return $this->belongsTo(Trabajo::class);
    }

    public function equipo()
    {
        return $this->hasOne(ActividadEquipo::class, 'actividad_id');
    }

    public function cotizacion()
    {
        return $this->hasOne(ActividadCotizacion::class, 'actividad_id');
    }

    public function refacciones()
    {
        return $this->hasMany(EquipoHistorialRefaccion::class, 'actividad_id');
    }
}
