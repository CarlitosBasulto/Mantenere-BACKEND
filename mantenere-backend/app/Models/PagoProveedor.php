<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoProveedor extends Model
{
    use HasFactory;

    protected $table = 'pagos_proveedores';

    const BANCO_OFICIAL = 'BBVA México';
    const CUENTA_OFICIAL = '1567890123';
    const CLABE_OFICIAL = '012180015678901234';
    const BENEFICIARIO_OFICIAL = 'Mantenere Servicios S.A. de C.V.';
    const MONTO_SUSCRIPCION = 1499.00;

    const ESTADO_PENDIENTE = 'Pendiente';
    const ESTADO_APROBADO = 'Aprobado';
    const ESTADO_RECHAZADO = 'Rechazado';

    protected $fillable = [
        'user_id',
        'trabajador_id',
        'nombre_empresa',
        'telefono',
        'monto',
        'banco_origen',
        'folio_referencia',
        'fecha_transferencia',
        'comprobante_url',
        'notas',
        'es_prueba_gratis',
        'dias_prueba',
        'prueba_inicio_at',
        'prueba_fin_at',
        'estado',
        'motivo_rechazo',
        'aprobado_por',
        'aprobado_at',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_transferencia' => 'date',
        'aprobado_at' => 'datetime',
        'es_prueba_gratis' => 'boolean',
        'prueba_inicio_at' => 'datetime',
        'prueba_fin_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    // Alias para compatibilidad
    public function revisadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }
}
