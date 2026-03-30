<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Negocio extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'tipo', 'encargado', 'nombrePlaza',
        'estado', 'ciudad', 'calle', 'numero', 'colonia', 'cp',
        'referencia', 'manzana', 'lote', 'calleAv',
        'telefono', 'correo', 'imagenPerfil', 'estado_aprobacion', 'user_id'
    ];

    /**
     * Get the user that owns the negocio.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
