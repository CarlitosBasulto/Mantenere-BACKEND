<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Events\NotificationSent;

class NotificacionController extends Controller
{
    // 🔔 1. Obtener todas las notificaciones de un usuario
    public function indexByUsuario($user_id)
    {

        Log::info("Ingresó el usuario correctamente ID: " . $user_id);
        $notificaciones = Notificacion::where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->limit(30) // Limitamos a las últimas 30 por rendimiento
            ->get();
            
        return response()->json($notificaciones);
    }

    // 📩 2. Crear una nueva notificación
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'titulo' => 'required|string',
            'mensaje' => 'required|string',
            'enlace' => 'nullable|string',
        ]);

        $notificacion = Notificacion::create([
            'user_id' => $request->user_id,
            'titulo' => $request->titulo,
            'mensaje' => $request->mensaje,
            'enlace' => $request->enlace,
            'leido' => false
        ]);

        try {
            broadcast(new NotificationSent($notificacion));
        } catch (\Throwable $e) {
            Log::warning("Error broadcasting notification: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Notificación creada exitosamente',
            'data' => $notificacion
        ], 201);
    }

    // ✅ 3. Marcar una sóla notificación como leída
    public function markAsRead($id)
    {
        $notificacion = Notificacion::findOrFail($id);
        $notificacion->update(['leido' => true]);

        return response()->json([
            'message' => 'Notificación marcada como leída',
            'data' => $notificacion
        ]);
    }

    // 🧹 4. Marcar TODAS las de un usuario como leídas
    public function markAllAsRead($user_id)
    {
        Notificacion::where('user_id', $user_id)
            ->where('leido', false)
            ->update(['leido' => true]);

        return response()->json([
            'message' => 'Todas las notificaciones fueron marcadas como leídas'
        ]);
    }

    /**
     * 📣 5. Notificar a todos los usuarios de un ROL específico (p.ej. 'admin', 'tecnico', 'cliente')
     * Soporta diferentes ecosistemas y variantes de nombres de rol (Admin, tecnico-normal, etc.)
     */
    /**
     * Notificar a todos los usuarios de un ROL específico con aislamiento por ecosistema/tenant
     * Si el evento pertenece a un negocio autónomo o el usuario es autónomo, solo notifica a ese tenant.
     * Si el evento es del sistema central/base, solo notifica al admin/usuarios base.
     */
    public function notifyByRole(Request $request)
    {
        $request->validate([
            'role' => 'required|string',
            'titulo' => 'required|string',
            'mensaje' => 'required|string',
            'enlace' => 'nullable|string',
            'negocio_id' => 'nullable|integer',
            'admin_autonomo_id' => 'nullable|integer',
        ]);

        $roleInput = strtolower($request->role);
        $authUser = $request->user();

        // 1. Determinar el contexto del ecosistema (admin_autonomo_id)
        $adminAutonomoId = $request->admin_autonomo_id;

        if (!$adminAutonomoId && $request->negocio_id) {
            $negocio = \App\Models\Negocio::with('user.role')->find($request->negocio_id);
            if ($negocio) {
                if ($negocio->admin_autonomo_id) {
                    $adminAutonomoId = $negocio->admin_autonomo_id;
                } elseif ($negocio->user) {
                    $ownerRole = strtolower($negocio->user->role->name ?? '');
                    if (in_array($ownerRole, ['propietario-autonomo', 'administrador-general', 'admin-autonomo', 'autonomo'])) {
                        $adminAutonomoId = $negocio->user->admin_autonomo_id ?? $negocio->user->id;
                    }
                }
            }
        }

        if (!$adminAutonomoId && $authUser) {
            $authRole = strtolower($authUser->role->name ?? '');
            if (in_array($authRole, ['propietario-autonomo', 'administrador-general', 'admin-autonomo', 'autonomo', 'gerente-sucursal', 'tecnico-autonomo'])) {
                $adminAutonomoId = $authUser->admin_autonomo_id ?? (in_array($authRole, ['propietario-autonomo', 'autonomo', 'admin-autonomo']) ? $authUser->id : null);
                if (!$adminAutonomoId && $authUser->negocio_id) {
                    $neg = \App\Models\Negocio::find($authUser->negocio_id);
                    $adminAutonomoId = $neg?->admin_autonomo_id ?? $neg?->user_id;
                }
            }
        }

        // 2. Filtrar los destinatarios según el rol pedido Y el ecosistema correspondiente
        $usersQuery = \App\Models\User::query();

        if ($roleInput === 'admin') {
            if ($adminAutonomoId) {
                // NOTIFICAR EXCLUSIVAMENTE AL ECOSISTEMA AUTÓNOMO (Propietario y su Administrador General)
                $usersQuery->where(function ($q) use ($adminAutonomoId) {
                    $q->where('id', $adminAutonomoId)
                      ->orWhere(function ($sub) use ($adminAutonomoId) {
                          $sub->where('admin_autonomo_id', $adminAutonomoId)
                              ->whereHas('role', function ($r) {
                                  $r->whereIn('name', ['propietario-autonomo', 'administrador-general', 'gerente-general', 'admin-autonomo', 'autonomo']);
                              });
                      });
                });
            } else {
                // NOTIFICAR EXCLUSIVAMENTE AL ADMIN BASE (Central)
                $usersQuery->whereHas('role', function ($q) {
                    $q->whereIn('name', ['Admin', 'admin', 'root']);
                });
            }
        } elseif ($roleInput === 'cliente') {
            if ($adminAutonomoId) {
                if ($request->negocio_id) {
                    $usersQuery->where('negocio_id', $request->negocio_id);
                } else {
                    $usersQuery->where('admin_autonomo_id', $adminAutonomoId)
                        ->whereHas('role', function ($q) {
                            $q->whereIn('name', ['gerente-sucursal', 'encargado', 'cliente', 'Cliente']);
                        });
                }
            } else {
                $usersQuery->whereHas('role', function ($q) {
                    $q->whereIn('name', ['Cliente', 'cliente']);
                });
            }
        } elseif ($roleInput === 'tecnico') {
            if ($adminAutonomoId) {
                $usersQuery->where('admin_autonomo_id', $adminAutonomoId)
                    ->whereHas('role', function ($q) {
                        $q->whereIn('name', ['tecnico-autonomo']);
                    });
            } else {
                $usersQuery->whereHas('role', function ($q) {
                    $q->whereIn('name', ['tecnico-normal', 'tecnico', 'Tecnico']);
                });
            }
        } else {
            $usersQuery->whereHas('role', function ($q) use ($request, $roleInput) {
                $q->whereIn('name', [$request->role, ucfirst($roleInput), strtolower($request->role)]);
            });
            if ($adminAutonomoId) {
                $usersQuery->where(function($q) use ($adminAutonomoId) {
                    $q->where('admin_autonomo_id', $adminAutonomoId)->orWhere('id', $adminAutonomoId);
                });
            }
        }

        $users = $usersQuery->get();

        $notifications = [];
        foreach ($users as $user) {
            $notif = Notificacion::create([
                'user_id' => $user->id,
                'titulo' => $request->titulo,
                'mensaje' => $request->mensaje,
                'enlace' => $request->enlace,
                'leido' => false
            ]);
            try {
                broadcast(new NotificationSent($notif));
            } catch (\Throwable $e) {
                Log::warning("Error broadcasting notification: " . $e->getMessage());
            }
            $notifications[] = $notif;
        }

        return response()->json([
            'message' => 'Notificaciones enviadas al rol ' . $request->role,
            'count' => count($notifications),
            'admin_autonomo_id' => $adminAutonomoId
        ]);
    }

    public function notifyEcosistema(Request $request)
    {
        $request->validate([
            'admin_autonomo_id' => 'required|integer',
            'titulo' => 'required|string',
            'mensaje' => 'required|string',
            'enlace' => 'nullable|string',
        ]);

        $users = \App\Models\User::where('admin_autonomo_id', $request->admin_autonomo_id)
            ->whereHas('role', function ($query) {
                $query->whereIn('name', ['propietario-autonomo', 'administrador-general']);
            })
            ->orWhere('id', $request->admin_autonomo_id)
            ->get();

        $notifications = [];
        foreach ($users as $user) {
            $notif = Notificacion::create([
                'user_id' => $user->id,
                'titulo' => $request->titulo,
                'mensaje' => $request->mensaje,
                'enlace' => $request->enlace,
                'leido' => false
            ]);
            try {
                broadcast(new NotificationSent($notif));
            } catch (\Throwable $e) {
                Log::warning("Error broadcasting notification: " . $e->getMessage());
            }
            $notifications[] = $notif;
        }

        return response()->json([
            'message' => 'Notificaciones enviadas al ecosistema',
            'count' => count($notifications)
        ]);
    }

    public function notifyNegocio(Request $request)
    {
        $request->validate([
            'negocio_id' => 'required|integer',
            'titulo' => 'required|string',
            'mensaje' => 'required|string',
            'enlace' => 'nullable|string',
        ]);

        $users = \App\Models\User::where('negocio_id', $request->negocio_id)->get();

        $notifications = [];
        foreach ($users as $user) {
            $notif = Notificacion::create([
                'user_id' => $user->id,
                'titulo' => $request->titulo,
                'mensaje' => $request->mensaje,
                'enlace' => $request->enlace,
                'leido' => false
            ]);
            try {
                broadcast(new NotificationSent($notif));
            } catch (\Throwable $e) {
                Log::warning("Error broadcasting notification: " . $e->getMessage());
            }
            $notifications[] = $notif;
        }

        return response()->json([
            'message' => 'Notificaciones enviadas a usuarios del negocio',
            'count' => count($notifications)
        ]);
    }
}
