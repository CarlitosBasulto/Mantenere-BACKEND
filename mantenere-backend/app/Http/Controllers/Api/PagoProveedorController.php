<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PagoProveedor;
use App\Models\Trabajador;
use App\Models\User;
use App\Models\Role;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PagoProveedorController extends Controller
{
    /**
     * 1. DATOS BANCARIOS OFICIALES PARA TRANSFERENCIA
     */
    public function datosBancarios()
    {
        return response()->json([
            'banco'           => PagoProveedor::BANCO_OFICIAL,
            'cuenta'          => PagoProveedor::CUENTA_OFICIAL,
            'clabe'           => PagoProveedor::CLABE_OFICIAL,
            'beneficiario'    => PagoProveedor::BENEFICIARIO_OFICIAL,
            'monto_suscripcion' => PagoProveedor::MONTO_SUSCRIPCION,
            'instrucciones'   => [
                'Realiza la transferencia por el monto exacto de $1,499.00 MXN.',
                'En el concepto de la transferencia escribe tu nombre completo o correo.',
                'Toma una captura de pantalla o foto clara de tu comprobante.',
                'Sube el comprobante en esta plataforma para ser verificado por el Administrador General.',
                'Tu cuenta de Técnico Pro-Veedor se activará una vez validado el pago.',
            ],
        ]);
    }

    /**
     * 2. REGISTRAR SOLICITUD DE PRUEBA GRATUITA (6 MESES)
     */
    public function solicitarPruebaGratis(Request $request)
    {
        $user = $request->user();

        // Verificar si ya tiene una solicitud o prueba activa/aprobada
        $pagoExistente = PagoProveedor::where('user_id', $user->id)
            ->whereIn('estado', [PagoProveedor::ESTADO_PENDIENTE, PagoProveedor::ESTADO_APROBADO])
            ->first();

        if ($pagoExistente) {
            if ($pagoExistente->estado === PagoProveedor::ESTADO_APROBADO) {
                return response()->json([
                    'message' => 'Ya tienes una membresía o prueba activa de Técnico Pro-Veedor.',
                    'pago'    => $pagoExistente,
                ], 400);
            }
            return response()->json([
                'message' => 'Ya tienes una solicitud pendiente de revisión por el Administrador General.',
                'pago'    => $pagoExistente,
            ], 400);
        }

        $validated = $request->validate([
            'nombre_empresa' => 'required|string|max:150',
            'telefono'       => 'required|string|max:20',
            'notas'          => 'nullable|string|max:500',
        ]);

        $pago = PagoProveedor::create([
            'user_id'             => $user->id,
            'nombre_empresa'      => $validated['nombre_empresa'],
            'telefono'            => $validated['telefono'],
            'monto'               => 0.00,
            'banco_origen'        => 'Cortesía Mantenere',
            'folio_referencia'    => 'PRUEBA-GRATIS-' . strtoupper(Str::random(6)),
            'fecha_transferencia' => now()->toDateString(),
            'comprobante_url'     => 'PRUEBA_GRATIS_6_MESES',
            'notas'               => $validated['notas'] ?? 'Solicitud de prueba de cortesía por 6 meses.',
            'estado'              => PagoProveedor::ESTADO_PENDIENTE,
            'es_prueba_gratis'    => true,
            'dias_prueba'         => 180,
        ]);

        // Notificar a los administradores generales
        $adminRoleIds = Role::whereIn('name', ['Admin', 'administrador-general'])->pluck('id');
        $admins = User::whereIn('role_id', $adminRoleIds)->get();

        foreach ($admins as $admin) {
            Notificacion::create([
                'user_id' => $admin->id,
                'titulo'  => '🎁 Nueva Solicitud de Prueba Gratis (6 Meses)',
                'mensaje' => "El técnico {$user->name} ha solicitado activar la prueba gratuita de 6 meses como Técnico Pro-Veedor ({$validated['nombre_empresa']}). Requiere tu visto bueno.",
                'enlace'  => '/admin/pagos-proveedor',
                'leido'   => false,
            ]);
        }

        return response()->json([
            'message' => '¡Solicitud de prueba gratuita de 6 meses enviada con éxito! El Administrador General la revisará para dar su visto bueno.',
            'pago'    => $pago,
        ], 201);
    }

    /**
     * 3. REGISTRAR PAGO POR TRANSFERENCIA (SUBIDA DE COMPROBANTE)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Verificar si ya tiene un pago pendiente o aprobado
        $pagoExistente = PagoProveedor::where('user_id', $user->id)
            ->whereIn('estado', [PagoProveedor::ESTADO_PENDIENTE, PagoProveedor::ESTADO_APROBADO])
            ->first();

        if ($pagoExistente) {
            if ($pagoExistente->estado === PagoProveedor::ESTADO_APROBADO) {
                return response()->json([
                    'message' => 'Ya eres Técnico Pro-Veedor activo.',
                    'pago'    => $pagoExistente,
                ], 400);
            }
            return response()->json([
                'message' => 'Ya tienes una solicitud de pago pendiente de revisión.',
                'pago'    => $pagoExistente,
            ], 400);
        }

        $validated = $request->validate([
            'nombre_empresa'      => 'required|string|max:150',
            'telefono'            => 'required|string|max:20',
            'banco_origen'        => 'nullable|string|max:100',
            'folio_referencia'    => 'nullable|string|max:100',
            'referencia'          => 'nullable|string|max:100',
            'fecha_transferencia' => 'nullable|date',
            'comprobante'         => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'notas'               => 'nullable|string|max:500',
        ]);

        // Guardar comprobante
        $path = $request->file('comprobante')->store('comprobantes_proveedores', 'public');
        $ref = $validated['folio_referencia'] ?? $validated['referencia'] ?? strtoupper(Str::random(8));

        $pago = PagoProveedor::create([
            'user_id'             => $user->id,
            'nombre_empresa'      => $validated['nombre_empresa'],
            'telefono'            => $validated['telefono'],
            'monto'               => PagoProveedor::MONTO_SUSCRIPCION,
            'banco_origen'        => $validated['banco_origen'] ?? 'BBVA México',
            'folio_referencia'    => strtoupper(trim($ref)),
            'fecha_transferencia' => $validated['fecha_transferencia'] ?? now()->toDateString(),
            'comprobante_url'     => $path,
            'notas'               => $validated['notas'] ?? null,
            'estado'              => PagoProveedor::ESTADO_PENDIENTE,
            'es_prueba_gratis'    => false,
        ]);

        // Notificar a los administradores
        $adminRoleIds = Role::whereIn('name', ['Admin', 'administrador-general'])->pluck('id');
        $admins = User::whereIn('role_id', $adminRoleIds)->get();

        foreach ($admins as $admin) {
            Notificacion::create([
                'user_id' => $admin->id,
                'titulo'  => '💳 Nuevo Comprobante de Pago Pro-Veedor',
                'mensaje' => "El técnico {$user->name} ha subido su comprobante de transferencia ($1,499.00 MXN) para Técnico Pro-Veedor ({$validated['nombre_empresa']}). Ref: {$ref}",
                'enlace'  => '/admin/pagos-proveedor',
                'leido'   => false,
            ]);
        }

        return response()->json([
            'message' => 'Comprobante recibido exitosamente. Será verificado por el Administrador General.',
            'pago'    => $pago,
        ], 201);
    }

    /**
     * 4. CONSULTAR ESTADO DE MI SOLICITUD / PRUEBA / PAGO
     */
    public function miEstado(Request $request)
    {
        $user = $request->user();
        $user->load('role');

        $ultimoPago = PagoProveedor::where('user_id', $user->id)
            ->latest()
            ->first();

        $roleName = $user->role ? strtolower($user->role->name) : '';
        $esProveedor = ($roleName === 'tecnico-proveedor' || $user->role_id === 9);

        $pruebaInfo = null;
        if ($ultimoPago && $ultimoPago->es_prueba_gratis) {
            $inicio = $ultimoPago->prueba_inicio_at ? \Carbon\Carbon::parse($ultimoPago->prueba_inicio_at) : null;
            $fin = $ultimoPago->prueba_fin_at ? \Carbon\Carbon::parse($ultimoPago->prueba_fin_at) : null;
            $ahora = \Carbon\Carbon::now();

            $haExpirado = false;
            $diasRestantes = 0;
            $mesesRestantes = 0;
            $tiempoRestanteTexto = '';

            if ($ultimoPago->estado === PagoProveedor::ESTADO_APROBADO && $fin) {
                if ($ahora->greaterThan($fin)) {
                    $haExpirado = true;
                    // Si ya expiró, regresar a tecnico-autonomo
                    if ($esProveedor) {
                        $rolAutonomo = Role::where('name', 'tecnico-autonomo')->first();
                        if ($rolAutonomo) {
                            $user->role_id = $rolAutonomo->id;
                            $user->save();
                        }
                        $esProveedor = false;
                    }
                } else {
                    $diff = $ahora->diff($fin);
                    $diasRestantes = (int) $ahora->diffInDays($fin, false);
                    $mesesRestantes = $diff->m;
                    $diasRestantesMes = $diff->d;
                    $horasRestantes = $diff->h;

                    if ($diff->m > 0) {
                        $tiempoRestanteTexto = "{$diff->m} mes" . ($diff->m > 1 ? 'es' : '') . " y {$diff->d} día" . ($diff->d > 1 ? 's' : '');
                    } elseif ($diff->d > 0) {
                        $tiempoRestanteTexto = "{$diff->d} día" . ($diff->d > 1 ? 's' : '') . " y {$diff->h} hr" . ($diff->h > 1 ? 's' : '');
                    } else {
                        $tiempoRestanteTexto = "{$diff->h} horas y {$diff->i} minutos";
                    }
                }
            }

            $pruebaInfo = [
                'es_prueba_gratis'       => true,
                'dias_totales'           => $ultimoPago->dias_prueba ?? 180,
                'prueba_inicio_at'       => $ultimoPago->prueba_inicio_at,
                'prueba_fin_at'          => $ultimoPago->prueba_fin_at,
                'ha_expirado'            => $haExpirado,
                'dias_restantes'         => max(0, $diasRestantes),
                'meses_restantes'        => max(0, $mesesRestantes),
                'tiempo_restante_texto'  => $tiempoRestanteTexto,
            ];
        }

        return response()->json([
            'es_proveedor' => $esProveedor,
            'ultimo_pago'  => $ultimoPago,
            'pago'         => $ultimoPago,
            'prueba_info'  => $pruebaInfo,
        ]);
    }

    /**
     * 5. [ADMIN] LISTAR TODAS LAS SOLICITUDES DE PAGO Y PRUEBAS
     */
    public function index(Request $request)
    {
        $query = PagoProveedor::with(['user:id,name,email,telefono,avatar', 'aprobador:id,name'])
            ->latest();

        if ($request->has('estado') && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        }

        if ($request->has('tipo') && $request->tipo === 'prueba') {
            $query->where('es_prueba_gratis', true);
        } elseif ($request->has('tipo') && $request->tipo === 'pago') {
            $query->where('es_prueba_gratis', false);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * 6. [ADMIN] VER DETALLE DE UNA SOLICITUD
     */
    public function show($id)
    {
        $pago = PagoProveedor::with(['user', 'aprobador'])->findOrFail($id);
        return response()->json($pago);
    }

    /**
     * 7. [ADMIN] APROBAR SOLICITUD / PAGO / PRUEBA GRATIS
     */
    public function aprobar(Request $request, $id)
    {
        $pago = PagoProveedor::findOrFail($id);

        if ($pago->estado === PagoProveedor::ESTADO_APROBADO) {
            return response()->json(['message' => 'Esta solicitud ya había sido aprobada anteriormente.'], 400);
        }

        $user = $pago->user;
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $pago->estado = PagoProveedor::ESTADO_APROBADO;
        $pago->aprobado_por = $request->user()->id;
        $pago->aprobado_at = now();
        $pago->motivo_rechazo = null;

        // Si es prueba gratis, configurar fechas de 6 meses
        if ($pago->es_prueba_gratis) {
            $pago->prueba_inicio_at = now();
            $pago->prueba_fin_at = now()->addMonths(6);
            $pago->dias_prueba = 180;
        }

        $pago->save();

        // 1. Asignar role_id de tecnico-proveedor (id 9)
        $rolProveedor = Role::where('name', 'tecnico-proveedor')->first();
        if ($rolProveedor) {
            $user->role_id = $rolProveedor->id;
        }
        $user->admin_autonomo_id = null;
        $user->save();

        // 2. Crear o actualizar registro en tabla trabajadores para que aparezca como Pro-Veedor en la RED
        $trabajador = Trabajador::where('user_id', $user->id)->first();
        if (!$trabajador) {
            $trabajador = Trabajador::create([
                'nombre'            => $user->name,
                'correo'            => $user->email,
                'telefono'          => $pago->telefono ?? $user->telefono ?? 'S/N',
                'user_id'           => $user->id,
                'puesto'            => 'Técnico Pro-Veedor (' . $pago->nombre_empresa . ')',
                'estado'            => 'Activo',
                'es_proveedor'      => true,
                'admin_autonomo_id' => null,
                'creador_id'        => null,
            ]);
        } else {
            $trabajador->telefono          = $pago->telefono ?? $user->telefono ?? $trabajador->telefono;
            $trabajador->puesto            = 'Técnico Pro-Veedor (' . $pago->nombre_empresa . ')';
            $trabajador->es_proveedor      = true;
            $trabajador->admin_autonomo_id = null;
            $trabajador->creador_id        = null;
            $trabajador->estado            = 'Activo';
            $trabajador->save();
        }

        $pago->trabajador_id = $trabajador->id;
        $pago->save();

        // 3. Notificar al usuario solicitante
        $mensajeNotif = $pago->es_prueba_gratis
            ? "¡Felicidades! Tu prueba gratuita de 6 meses como Técnico Pro-Veedor ha sido aprobada por el Administrador General. Disfruta de todos los beneficios hasta el " . $pago->prueba_fin_at->format('d/m/Y') . "."
            : "¡Felicidades! Tu pago de suscripción ha sido aprobado y verificado. Tu cuenta ha sido elevada a Técnico Pro-Veedor con éxito.";

        Notificacion::create([
            'user_id' => $user->id,
            'titulo'  => $pago->es_prueba_gratis ? '🎁 ¡Prueba Gratuita (6 Meses) Activada!' : '🎉 ¡Tu pago ha sido aprobado!',
            'mensaje' => $mensajeNotif,
            'enlace'  => '/tecnico-proveedor/dashboard',
            'leido'   => false,
        ]);

        return response()->json([
            'message' => $pago->es_prueba_gratis
                ? '¡Prueba gratuita de 6 meses aprobada exitosamente! El usuario ahora es Técnico Pro-Veedor.'
                : 'Pago aprobado exitosamente. El usuario ahora es Técnico Pro-Veedor.',
            'pago'    => $pago->fresh(['user', 'aprobador']),
        ]);
    }

    /**
     * 8. [ADMIN] RECHAZAR SOLICITUD / PAGO / PRUEBA GRATIS
     */
    public function rechazar(Request $request, $id)
    {
        $request->validate([
            'motivo_rechazo' => 'required|string|max:500',
        ]);

        $pago = PagoProveedor::findOrFail($id);

        $pago->estado = PagoProveedor::ESTADO_RECHAZADO;
        $pago->aprobado_por = $request->user()->id;
        $pago->aprobado_at = now();
        $pago->motivo_rechazo = $request->motivo_rechazo;
        $pago->save();

        // Notificar al usuario
        $tipoTexto = $pago->es_prueba_gratis ? 'solicitud de prueba gratis' : 'comprobante de pago';
        Notificacion::create([
            'user_id' => $pago->user_id,
            'titulo'  => '❌ Solicitud de Técnico Pro-Veedor no aprobada',
            'mensaje' => "Tu {$tipoTexto} como Técnico Pro-Veedor fue rechazada. Motivo: {$request->motivo_rechazo}",
            'enlace'  => '/perfil',
            'leido'   => false,
        ]);

        return response()->json([
            'message' => 'Solicitud rechazada correctamente.',
            'pago'    => $pago->fresh(['user', 'aprobador']),
        ]);
    }
}
