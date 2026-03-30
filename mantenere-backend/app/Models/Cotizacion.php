<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'trabajo_id',
        'descripcion',
        'monto',
        'estado',
        'archivo'
    ];

    public function trabajo()
    {
        return $this->belongsTo(Trabajo::class);
    }
}
