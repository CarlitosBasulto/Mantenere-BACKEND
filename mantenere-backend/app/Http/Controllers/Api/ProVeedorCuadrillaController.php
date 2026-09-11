<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trabajador;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProVeedorCuadrillaController extends Controller
{
    /**
     * Helper para obtener el registro de Trabajador del Pro-Veedor autenticado
     */
    private function getMiTrabajadorProVeedor(Request $request)
    {
        $user = $request->user();
        $trabajador = Trabajador::where('user_id', $user->id)
            ->orWhere('correo', $user->email)
            ->first();

        return $trabajador;
    }

    /**
     * 1. LISTAR TÉCNICOS DE LA CUADRILLA DEL PRO-VEEDOR
     */
    public function index(Request $request)
    {
        $miTrabajador = $this->getMiTrabajadorProVeedor($request);
        if (!$miTrabajador) {
            return response()->json([]);
        }

        $cuadrilla = Trabajador::with(['user.role'])
            ->where(function($q) use ($miTrabajador, $request) {
                $q->where('proveedor_id', $miTrabajador->id)
                  ->orWhere('creador_id', $request->user()->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($cuadrilla);
    }

    /**
     * 2. REGISTRAR UN NUEVO TÉCNICO EN LA CUADRILLA
     * Se crea su cuenta de usuario con rol exclusivo 'tecnico-cuadrilla'
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'especialidad' => 'nullable|string|max:100',
            'correo' => 'nullable|email|max:255|unique:users,email',
            'password' => 'nullable|string|min:6',
            'foto' => 'nullable|file|max:10240',
        ]);

        $miTrabajador = $this->getMiTrabajadorProVeedor($request);
        if (!$miTrabajador) {
            return response()->json(['message' => 'No tienes perfil de Pro-Veedor configurado.'], 403);
        }

        $avatarUrl = null;
        if ($request->hasFile('foto')) {
            $isLocal = app()->environment('local');
            if ($isLocal) {
                $path = $request->file('foto')->store('cuadrilla/fotos', 'public');
                $avatarUrl = asset('storage/' . $path);
            } else {
                try {
                    $uploaded = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::upload(
                        $request->file('foto')->getRealPath(),
                        ['folder' => 'mantenere/cuadrilla/fotos']
                    )->getSecurePath();
                    $avatarUrl = $uploaded;
                } catch (\Exception $e) {
                    $path = $request->file('foto')->store('cuadrilla/fotos', 'public');
                    $avatarUrl = asset('storage/' . $path);
                }
            }
        }

        // Crear usuario con el rol exclusivo de la jerarquía 'tecnico-cuadrilla' si se envían credenciales
        $nuevoUserId = null;
        if ($request->filled('correo') && $request->filled('password')) {
            $roleCuadrilla = Role::firstOrCreate(
                ['name' => 'tecnico-cuadrilla'],
                ['hierarchy_level' => 9]
            );

            $user = User::create([
                'name' => $request->nombre,
                'email' => $request->correo,
                'password' => Hash::make($request->password),
                'role_id' => $roleCuadrilla->id,
            ]);

            $nuevoUserId = $user->id;
        }

        $nuevoTecnico = Trabajador::create([
            'nombre' => $request->nombre,
            'correo' => $request->correo,
            'telefono' => $request->telefono,
            'puesto' => $request->input('especialidad', 'Técnico de Cuadrilla'),
            'estado' => 'Disponible',
            'proveedor_id' => $miTrabajador->id,
            'creador_id' => $request->user()->id,
            'user_id' => $nuevoUserId,
            'avatar' => $avatarUrl,
            'es_proveedor' => false
        ]);

        return response()->json([
            'message' => 'Técnico de cuadrilla agregado exitosamente con rol asignado.',
            'tecnico' => $nuevoTecnico->load('user.role')
        ], 201);
    }

    /**
     * 3. ACTUALIZAR UN TÉCNICO DE LA CUADRILLA
     */
    public function update(Request $request, $id)
    {
        $miTrabajador = $this->getMiTrabajadorProVeedor($request);
        $tecnico = Trabajador::where('id', $id)
            ->where(function($q) use ($miTrabajador, $request) {
                if ($miTrabajador) $q->where('proveedor_id', $miTrabajador->id);
                $q->orWhere('creador_id', $request->user()->id);
            })
            ->firstOrFail();

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'especialidad' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:50',
            'correo' => 'nullable|email|max:255|unique:users,email,' . ($tecnico->user_id ?? 0),
            'password' => 'nullable|string|min:6'
        ]);

        if ($request->has('nombre')) $tecnico->nombre = $request->nombre;
        if ($request->has('telefono')) $tecnico->telefono = $request->telefono;
        if ($request->has('especialidad')) $tecnico->puesto = $request->especialidad;
        if ($request->has('estado')) $tecnico->estado = $request->estado;
        if ($request->has('correo')) $tecnico->correo = $request->correo;

        // Actualizar o crear usuario si se proveen credenciales
        if ($tecnico->user_id) {
            $user = User::find($tecnico->user_id);
            if ($user) {
                if ($request->has('nombre')) $user->name = $request->nombre;
                if ($request->has('correo') && $request->correo) $user->email = $request->correo;
                if ($request->has('password') && $request->password) $user->password = Hash::make($request->password);
                $user->save();
            }
        } elseif ($request->filled('correo') && $request->filled('password')) {
            $roleCuadrilla = Role::firstOrCreate(
                ['name' => 'tecnico-cuadrilla'],
                ['hierarchy_level' => 9]
            );
            $user = User::create([
                'name' => $tecnico->nombre,
                'email' => $request->correo,
                'password' => Hash::make($request->password),
                'role_id' => $roleCuadrilla->id,
            ]);
            $tecnico->user_id = $user->id;
        }

        if ($request->hasFile('foto')) {
            $isLocal = app()->environment('local');
            if ($isLocal) {
                $path = $request->file('foto')->store('cuadrilla/fotos', 'public');
                $tecnico->avatar = asset('storage/' . $path);
            } else {
                try {
                    $uploaded = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::upload(
                        $request->file('foto')->getRealPath(),
                        ['folder' => 'mantenere/cuadrilla/fotos']
                    )->getSecurePath();
                    $tecnico->avatar = $uploaded;
                } catch (\Exception $e) {
                    $path = $request->file('foto')->store('cuadrilla/fotos', 'public');
                    $tecnico->avatar = asset('storage/' . $path);
                }
            }
        }

        $tecnico->save();

        return response()->json([
            'message' => 'Datos del técnico actualizados correctamente.',
            'tecnico' => $tecnico->load('user.role')
        ]);
    }

    /**
     * 4. ELIMINAR TÉCNICO DE LA CUADRILLA
     */
    public function destroy(Request $request, $id)
    {
        $miTrabajador = $this->getMiTrabajadorProVeedor($request);
        $tecnico = Trabajador::where('id', $id)
            ->where(function($q) use ($miTrabajador, $request) {
                if ($miTrabajador) $q->where('proveedor_id', $miTrabajador->id);
                $q->orWhere('creador_id', $request->user()->id);
            })
            ->firstOrFail();

        // Si tiene usuario asociado con rol tecnico-cuadrilla, eliminarlo también
        if ($tecnico->user_id) {
            $u = User::find($tecnico->user_id);
            if ($u) {
                $u->tokens()->delete();
                $u->delete();
            }
        }

        $tecnico->delete();

        return response()->json([
            'message' => 'Técnico eliminado de tu cuadrilla correctamente.'
        ]);
    }
}
