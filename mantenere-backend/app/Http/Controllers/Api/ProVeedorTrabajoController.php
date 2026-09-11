<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trabajo;
use App\Models\Trabajador;
use App\Models\Cotizacion;
use App\Models\Notificacion;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

class ProVeedorTrabajoController extends Controller
{
    private function getMiTrabajadorProVeedor(Request $request)
    {
        $user = $request->user();
        return Trabajador::where('user_id', $user->id)
            ->orWhere('correo', $user->email)
            ->first();
    }

    /**
     * 1. LISTAR TRABAJOS DE LA RED PARA ESTE PRO-VEEDOR
     */
    public function index(Request $request)
    {
        $miTrabajador = $this->getMiTrabajadorProVeedor($request);
        if (!$miTrabajador) {
            return response()->json([]);
        }

        // Obtener IDs de todos los miembros de su cuadrilla además de él mismo
        $cuadrillaIds = Trabajador::where('proveedor_id', $miTrabajador->id)->pluck('id')->toArray();
        $todosIds = array_merge([$miTrabajador->id], $cuadrillaIds);

        $trabajos = Trabajo::with(['negocio', 'trabajador', 'cotizaciones'])
            ->whereIn('trabajador_id', $todosIds)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($trabajos);
    }

    /**
     * 2. REASIGNAR / DESPACHAR TRABAJO A UN TÉCNICO DE SU CUADRILLA
     * Puede despacharlo para 'Visita' (cotización en sitio) o 'Trabajo' (ejecución directa)
     */
    public function asignarCuadrilla(Request $request, $trabajoId)
    {
        $request->validate([
            'tecnico_id' => 'required|exists:trabajadores,id',
            'tipo' => 'nullable|string|in:Visita,Trabajo',
            'fecha' => 'nullable|string',
            'hora' => 'nullable|string'
        ]);

        $miTrabajador = $this->getMiTrabajadorProVeedor($request);
        if (!$miTrabajador) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Validar que el técnico asignado sea de su cuadrilla o él mismo
        $tecnicoDestino = Trabajador::findOrFail($request->tecnico_id);
        if ($tecnicoDestino->id !== $miTrabajador->id && $tecnicoDestino->proveedor_id !== $miTrabajador->id && $tecnicoDestino->creador_id !== $request->user()->id) {
            return response()->json(['message' => 'El técnico seleccionado no pertenece a tu cuadrilla.'], 403);
        }

        $trabajo = Trabajo::findOrFail($trabajoId);
        $trabajo->trabajador_id = $tecnicoDestino->id;
        $trabajo->tecnico = $tecnicoDestino->nombre;
        if ($request->fecha) $trabajo->fecha_programada = $request->fecha;
        if ($request->hora) $trabajo->hora_llegada = $request->hora;

        $tipoAsignacion = $request->input('tipo', $trabajo->tipo ?: 'Visita');
        $trabajo->tipo = $tipoAsignacion;

        if ($tipoAsignacion === 'Visita') {
            $trabajo->estado = 'En Espera';
            $trabajo->visitado = false;
        } else {
            $trabajo->estado = 'Asignado';
        }

        $trabajo->save();

        // 1. Notificar al técnico asignado de la cuadrilla
        if ($tecnicoDestino->user_id) {
            $enlaceDestino = $tipoAsignacion === 'Visita'
                ? "/tecnico/trabajo-detalle/{$trabajo->id}?tab=cotizacion"
                : "/tecnico/trabajo-detalle/{$trabajo->id}?tab=trabajo";

            $accionMsg = $tipoAsignacion === 'Visita'
                ? 'acudir a la sucursal y realizar la cotización con el cotizador oficial.'
                : 'ejecutar el trabajo programado.';

            Notificacion::create([
                'user_id' => $tecnicoDestino->user_id,
                'titulo' => $tipoAsignacion === 'Visita' ? '📝 Visita para Cotizar Asignada' : '🔨 Trabajo Directo Asignado',
                'mensaje' => "Tu Pro-Veedor ({$request->user()->name}) te ha despachado al trabajo #{$trabajo->id} ({$trabajo->titulo}) para {$accionMsg}",
                'leido' => false,
                'enlace' => $enlaceDestino
            ]);
        }

        // 2. Notificar al Administrador General
        $adminRoles = Role::whereIn('name', ['admin', 'root', 'administrador-general'])->pluck('id');
        $admins = User::whereIn('role_id', $adminRoles)->get();
        foreach ($admins as $admin) {
            Notificacion::create([
                'user_id' => $admin->id,
                'titulo' => '👷 Cuadrilla Despachada por Pro-Veedor',
                'mensaje' => "El Pro-Veedor {$request->user()->name} ha despachado al técnico {$tecnicoDestino->nombre} para " . ($tipoAsignacion === 'Visita' ? 'realizar la cotización' : 'ejecutar el trabajo') . " en #{$trabajo->id} ({$trabajo->titulo}).",
                'leido' => false,
                'enlace' => "/menu/trabajo-detalle/{$trabajo->id}"
            ]);
        }

        // 3. Notificar al Administrador Autónomo si aplica
        if ($trabajo->admin_autonomo_id) {
            Notificacion::create([
                'user_id' => $trabajo->admin_autonomo_id,
                'titulo' => '👷 Cuadrilla Despachada por Pro-Veedor',
                'mensaje' => "El Pro-Veedor {$request->user()->name} ha despachado al técnico {$tecnicoDestino->nombre} para " . ($tipoAsignacion === 'Visita' ? 'realizar la cotización' : 'ejecutar el trabajo') . " en #{$trabajo->id} ({$trabajo->titulo}).",
                'leido' => false,
                'enlace' => "/autonomo/trabajo-detalle/{$trabajo->id}"
            ]);
        }

        return response()->json([
            'message' => "Trabajo despachado exitosamente a {$tecnicoDestino->nombre} de tu cuadrilla (" . ($tipoAsignacion === 'Visita' ? 'Visita para Cotizar' : 'Trabajo Directo') . ").",
            'trabajo' => $trabajo
        ]);
    }

    /**
     * 3. ENVIAR COTIZACIÓN AL ADMINISTRADOR GENERAL Y AUTÓNOMO
     */
    public function enviarCotizacionAdmin(Request $request, $trabajoId)
    {
        $request->validate([
            'monto' => 'required|numeric|min:1',
            'concepto' => 'nullable|string|max:255',
            'notas' => 'nullable|string',
            'archivo' => 'nullable|file|max:10240'
        ]);

        $trabajo = Trabajo::findOrFail($trabajoId);
        $user = $request->user();

        $archivoUrl = null;
        if ($request->hasFile('archivo')) {
            $isLocal = app()->environment('local');
            if ($isLocal) {
                $path = $request->file('archivo')->store('cotizaciones/proveedor', 'public');
                $archivoUrl = asset('storage/' . $path);
            } else {
                try {
                    $uploaded = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::upload(
                        $request->file('archivo')->getRealPath(),
                        ['folder' => 'mantenere/cotizaciones/proveedor']
                    )->getSecurePath();
                    $archivoUrl = $uploaded;
                } catch (\Exception $e) {
                    $path = $request->file('archivo')->store('cotizaciones/proveedor', 'public');
                    $archivoUrl = asset('storage/' . $path);
                }
            }
        }

        $cotizacion = Cotizacion::create([
            'trabajo_id' => $trabajo->id,
            'monto' => $request->monto,
            'concepto' => $request->input('concepto', "Cotización Pro-Veedor: {$trabajo->titulo}"),
            'notas' => $request->notas,
            'archivo_url' => $archivoUrl,
            'estado' => 'Pendiente',
            'user_id' => $user->id
        ]);

        // Actualizar estado del trabajo a 'Cotización Enviada'
        $trabajo->estado = 'Cotización Enviada';
        $trabajo->cotizacion = $request->monto;
        $trabajo->save();

        $msgCotiz = 'El Pro-Veedor ' . $user->name . ' ha enviado una cotización de $' . $request->monto . ' para el trabajo #' . $trabajo->id . ' (' . $trabajo->titulo . ').';

        // Notificar a administradores generales
        $adminRoles = Role::whereIn('name', ['admin', 'root', 'administrador-general'])->pluck('id');
        $admins = User::whereIn('role_id', $adminRoles)->get();
        foreach ($admins as $admin) {
            Notificacion::create([
                'user_id' => $admin->id,
                'titulo' => '💼 Cotización de Técnico Pro-Veedor Recibida',
                'mensaje' => $msgCotiz,
                'leido' => false,
                'enlace' => "/menu/trabajo-detalle/{$trabajo->id}?tab=cotizacion"
            ]);
        }

        // Notificar al Administrador Autónomo si aplica
        if ($trabajo->admin_autonomo_id) {
            Notificacion::create([
                'user_id' => $trabajo->admin_autonomo_id,
                'titulo' => '💼 Cotización de Técnico Pro-Veedor Recibida',
                'mensaje' => $msgCotiz,
                'leido' => false,
                'enlace' => "/autonomo/trabajo-detalle/{$trabajo->id}?tab=cotizacion"
            ]);
        }

        return response()->json([
            'message' => 'Cotización enviada exitosamente al Administrador General y Autónomo.',
            'cotizacion' => $cotizacion
        ], 201);
    }

    /**
     * 4. OBTENER LISTA DE TÉCNICOS PRO-VEEDORES PARA LA OPCIÓN "RED"
     * Accesible por: Administrador General, Admin Base y Autónomo Dueño
     */
    public function getProVeedoresRed(Request $request)
    {
        $proveedores = Trabajador::where('es_proveedor', true)
            ->with(['user.role'])
            ->get()
            ->map(function ($p) {
                // Contar miembros de su cuadrilla
                $cuadrillaCount = Trabajador::where('proveedor_id', $p->id)->count();
                return [
                    'id' => $p->id,
                    'user_id' => $p->user_id,
                    'nombre' => $p->nombre,
                    'correo' => $p->correo,
                    'telefono' => $p->telefono,
                    'puesto' => $p->puesto,
                    'avatar' => $p->avatar,
                    'estado' => $p->estado,
                    'es_proveedor' => true,
                    'cuadrilla_total' => $cuadrillaCount,
                    'tipo_origen' => 'RED'
                ];
            });

        return response()->json($proveedores);
    }
}
