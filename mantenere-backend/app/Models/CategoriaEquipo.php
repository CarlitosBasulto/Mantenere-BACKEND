<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaEquipo extends Model
{
    use HasFactory;

    protected $table = 'categorias_equipos';

    protected $fillable = ['nombre'];

    public function equipos()
    {
        return $this->hasMany(LevantamientoEquipo::class, 'categoria_id');
    }
}
