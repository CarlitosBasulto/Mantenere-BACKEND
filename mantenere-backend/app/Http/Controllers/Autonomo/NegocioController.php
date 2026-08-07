<?php

namespace App\Http\Controllers\Autonomo;

use App\Http\Controllers\Controller;
use App\Models\Negocio;
use App\Models\User;
use App\Models\Role;
use App\Models\Trabajador;
use App\Models\Trabajo;
use Illuminate\Http\Request;

/**
 * NegocioController — Ecosistema AUTÓNOMO
 * Maneja negocios del ecosistema autónomo (admin_autonomo_id IS NOT NULL).
 * Roles: propietario-autonomo (4), administrador-general (5), gerente-sucursal (6)
 */
class NegocioController extends Controller
{
    private function resolveAdminId($user): ?int
    {
        return strtolower($user->role->name) === 'propietario-autonomo'
            ? $user->id
            : ($user->admin_autonomo_id ?? null);
    }

    public function index(Request $request)
    {
        $user     = $request->user();
        $roleName = strtolower($user->role->name);

        $query = Negocio::with('areas.equipos.categoria')->whereNotNull('admin_autonomo_id');

        if (in_array($roleName, ['root', 'admin'])) {
            // Sin filtro: supervisan todo
        } elseif ($roleName === 'gerente-sucursal') {
            if ($user->negocio_id) $query->where('id', $user->negocio_id);
        } else {
            $adminId = $this->resolveAdminId($user);
            if ($adminId) $query->where('admin_autonomo_id', $adminId);
        }

        return response()->json($query->get());
    }

    public function show($id)
    {
        $negocio = Negocio::whereNotNull('admin_autonomo_id')->find($id);
        if (!$negocio) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }
        return response()->json($negocio);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'             => 'required|string|max:255',
            'tipo'               => 'required|string|max:255',
            'gerente'            => 'nullable|string',
            'telefonoGerente'    => 'nullable|string',
            'subgerente'         => 'nullable|string',
            'telefonoSubgerente' => 'nullable|string',
        ]);

        $authUser = $request->user();
        $adminId  = $this->resolveAdminId($authUser);

        $data = $request->all();
        $data['admin_autonomo_id'] = $adminId;

        $negocio = Negocio::create($data);
        return response()->json(['message' => 'Negocio creado', 'data' => $negocio], 201);
    }

    public function update(Request $request, $id)
    {
        $negocio = Negocio::whereNotNull('admin_autonomo_id')->find($id);
        if (!$negocio) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }

        $negocio->update($request->except(['levantamiento', 'user_id', 'admin_autonomo_id']));

        if ($request->has('levantamiento')) {
            $this->syncLevantamiento($negocio, $request->input('levantamiento', []));
        }

        $negocio->load('areas.equipos.categoria');
        return response()->json(['message' => 'Negocio actualizado', 'data' => $negocio]);
    }

    // ── Asignar Gerente de Sucursal ────────────────────────────────────────
    public function asignarGerenteSucursal(Request $request, $id)
    {
        $request->validate([
            'email'    => 'required|email',
            'name'     => 'required|string',
            'password' => 'required|min:8',
        ]);

        $negocio = Negocio::whereNotNull('admin_autonomo_id')->findOrFail($id);

        $roleGerente = Role::where('name', 'gerente-sucursal')->first();
        if (!$roleGerente) {
            return response()->json(['message' => 'Rol gerente-sucursal no existe en el sistema'], 500);
        }

        $gerenteSucursal = User::where('negocio_id', $id)->where('role_id', $roleGerente->id)->first();

        if ($gerenteSucursal) {
            $gerenteSucursal->update([
                'email'    => $request->email,
                'name'     => $request->name,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            ]);
        } else {
            $existing = User::where('email', $request->email)->first();
            if ($existing) {
                return response()->json(['message' => 'El correo ya está en uso'], 422);
            }
            $gerenteSucursal = User::create([
                'name'              => $request->name,
                'email'             => $request->email,
                'password'          => \Illuminate\Support\Facades\Hash::make($request->password),
                'role_id'           => $roleGerente->id,
                'negocio_id'        => $id,
                'admin_autonomo_id' => $negocio->admin_autonomo_id,
                'active'            => 1,
            ]);
        }

        return response()->json(['message' => 'Gerente de sucursal asignado', 'gerente' => $gerenteSucursal]);
    }

    public function getGerenteSucursal($id)
    {
        $roleGerente = Role::where('name', 'gerente-sucursal')->first();
        if (!$roleGerente) return response()->json(['gerente' => null]);

        $gerente = User::where('negocio_id', $id)->where('role_id', $roleGerente->id)->first();
        return response()->json([
            'gerente' => $gerente ? ['name' => $gerente->name, 'email' => $gerente->email] : null
        ]);
    }

    private function syncLevantamiento(Negocio $negocio, array $areasData): void
    {
        $incomingAreaIds = collect($areasData)->pluck('id')->filter(fn($id) => is_numeric($id))->toArray();
        $negocio->areas()->whereNotIn('id', $incomingAreaIds)->delete();

        foreach ($areasData as $areaInput) {
            $area = is_numeric($areaInput['id']) ? $negocio->areas()->find($areaInput['id']) : new \App\Models\LevantamientoArea();
            if (!$area && is_numeric($areaInput['id'])) continue;

            $area->nombreArea = $areaInput['nombreArea'];
            $area->sub_areas_json = collect($areaInput['subAreas'] ?? [])->map(fn($sa) => ['id' => $sa['id'] ?? null, 'nombreSubArea' => $sa['nombreSubArea'] ?? null])->toArray();
            $negocio->areas()->save($area);

            $rawEquipos = array_merge($areaInput['equipos'] ?? [], ...array_map(fn($sa) => $sa['equipos'] ?? [], $areaInput['subAreas'] ?? []));
            $uniqueEquipos = collect($rawEquipos)->keyBy(fn($eq) => isset($eq['id']) && is_numeric($eq['id']) ? 'id_'.$eq['id'] : 'name_'.($eq['nombre'] ?? ''))->values();

            $area->equipos()->whereNotIn('id', $uniqueEquipos->pluck('id')->filter(fn($id) => is_numeric($id))->toArray())->delete();

            foreach ($uniqueEquipos as $eqInput) {
                $equipo = is_numeric($eqInput['id'] ?? null) ? $area->equipos()->find($eqInput['id']) : new \App\Models\LevantamientoEquipo();
                if (!$equipo && is_numeric($eqInput['id'] ?? null)) continue;
                $equipo->fill(['nombre' => $eqInput['nombre'], 'marca' => $eqInput['marca'], 'modelo' => $eqInput['modelo'], 'serie' => $eqInput['serie'] ?? null, 'anioFabricacion' => $eqInput['anioFabricacion'] ?? null, 'anioUso' => $eqInput['anioUso'] ?? null, 'foto' => $eqInput['foto'] ?? null, 'fotoPlaca' => $eqInput['fotoPlaca'] ?? null, 'categoria_id' => $eqInput['categoria_id'] ?? null, 'subAreaId' => $eqInput['subAreaId'] ?? null, 'nombreSubArea' => $eqInput['nombreSubArea'] ?? null, 'subCategoria' => $eqInput['subCategoria'] ?? null]);
                $area->equipos()->save($equipo);
            }
        }
    }
}
