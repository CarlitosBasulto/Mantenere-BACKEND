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
        'descripcion'
    ];

    public function trabajo()
    {
        return $this->belongsTo(Trabajo::class);
    }
}
