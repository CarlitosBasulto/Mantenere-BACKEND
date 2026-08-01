<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudProveedor extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_proveedor';

    protected $fillable = [
        'user_id',
        'nombre_empresa',
        'telefono',
        'identificacion_proveedor_url',
        'estado',
        'motivo_rechazo',
        'escuadron_json'
    ];

    protected $casts = [
        'escuadron_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
