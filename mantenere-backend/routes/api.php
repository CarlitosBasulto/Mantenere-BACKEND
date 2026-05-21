<?php

use Illuminate\Support\Facades\Route;

Route::get('/drop-table', function() {
    \Illuminate\Support\Facades\Schema::dropIfExists('equipo_historial_refaccions');
    \Illuminate\Support\Facades\DB::table('migrations')->where('migration', 'like', '%equipo_historial_refaccions%')->delete();
    return "Dropped";
});
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\TrabajadorController;
use App\Http\Controllers\Api\NegocioController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\TrabajoController;
use App\Http\Controllers\ActividadController; // <-- FIJATE QUE YA NO DICE \Api\
use App\Http\Controllers\Api\MantenimientoSolicitudController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\CategoriaEquipoController;

/*Route::post('/ping', function () {
 return response()->json(['pong' => true]); });*/

Route::get('/ping', function () {
    return response()->json(['pong' => true]);
});



Route::middleware(['auth:sanctum', 'role.hierarchy'])
    ->group(function () {
        Route::get('/users', [UserController::class , 'index']);
        Route::get('/users/{user}', [UserController::class , 'show']);
        Route::post('/users', [UserController::class , 'store']);
        Route::put('/users/{user}', [UserController::class , 'update']);
        Route::delete('/users/{user}', [UserController::class , 'destroy']);
    });

Route::post('/login', [AuthController::class , 'login']);
Route::post('/register', [AuthController::class , 'register']);

Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class , 'logout']);


// Rutas para Trabajadores
Route::get('/trabajadores', [TrabajadorController::class , 'index']);
Route::get('/trabajadores/{id}', [TrabajadorController::class , 'show']);
Route::post('/trabajadores', [TrabajadorController::class , 'store']);
Route::put('/trabajadores/{id}', [TrabajadorController::class , 'update']);
Route::patch('/trabajadores/{id}/estado', [TrabajadorController::class , 'toggleEstado']);

// 🛠️ RUTAS DE TRABAJOS
Route::get('/trabajos', [App\Http\Controllers\Api\TrabajoController::class , 'index']);
Route::post('/trabajos', [App\Http\Controllers\Api\TrabajoController::class , 'store']);
Route::get('/trabajos/{id}', [App\Http\Controllers\Api\TrabajoController::class , 'show']);
Route::put('/trabajos/{id}', [App\Http\Controllers\Api\TrabajoController::class , 'update']);
Route::put('/trabajos/{id}/asignar', [App\Http\Controllers\Api\TrabajoController::class , 'asignarTrabajador']);
Route::put('/trabajos/{id}/estado', [App\Http\Controllers\Api\TrabajoController::class , 'cambiarEstado']);
Route::delete('/trabajos/{id}', [App\Http\Controllers\Api\TrabajoController::class , 'destroy']);


// 🏢 RUTAS DE NEGOCIOS
Route::get('/negocios', [NegocioController::class , 'index']); // Listar todos
Route::post('/negocios', [NegocioController::class , 'store']); // Crear nuevo
Route::get('/negocios/{id}', [NegocioController::class , 'show']); // Ver uno
Route::put('/negocios/{id}', [NegocioController::class , 'update']); // Editar

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/negocios/{id}/encargado', [NegocioController::class, 'asignarEncargado']);
    Route::get('/negocios/{id}/encargado', [NegocioController::class, 'getEncargado']);
});

// 📋 RUTAS DE REPORTES
Route::get('/reportes/trabajo/{trabajo_id}', [App\Http\Controllers\Api\ReporteController::class , 'showByTrabajo']);
Route::post('/reportes', [App\Http\Controllers\Api\ReporteController::class , 'store']);

// 💰 RUTAS DE COTIZACIONES
Route::get('/cotizaciones/trabajo/{trabajo_id}', [App\Http\Controllers\Api\CotizacionController::class , 'showByTrabajo']);
Route::post('/cotizaciones', [App\Http\Controllers\Api\CotizacionController::class , 'store']);
Route::put('/cotizaciones/{id}', [App\Http\Controllers\Api\CotizacionController::class , 'update']);
Route::put('/cotizaciones/{id}/estado', [App\Http\Controllers\Api\CotizacionController::class , 'updateStatus']);
Route::delete('/cotizaciones/{id}', [App\Http\Controllers\Api\CotizacionController::class , 'destroy']);

// 🔔 RUTAS DE NOTIFICACIONES
Route::get('/notificaciones/usuario/{user_id}', [App\Http\Controllers\Api\NotificacionController::class , 'indexByUsuario']);
Route::post('/notificaciones', [App\Http\Controllers\Api\NotificacionController::class , 'store']);
Route::post('/notificaciones/rol', [App\Http\Controllers\Api\NotificacionController::class , 'notifyByRole']);
Route::put('/notificaciones/{id}/leer', [App\Http\Controllers\Api\NotificacionController::class , 'markAsRead']);
Route::put('/notificaciones/usuario/{user_id}/leer-todas', [App\Http\Controllers\Api\NotificacionController::class , 'markAllAsRead']);

// 🧰 RUTAS DE CHECKLIST DE EQUIPO
Route::get('/checklist/trabajo/{trabajo_id}', [App\Http\Controllers\Api\ChecklistEquipoController::class , 'showByTrabajo']);
Route::post('/checklist', [App\Http\Controllers\Api\ChecklistEquipoController::class , 'store']);

Route::post('/actividades', [ActividadController::class , 'store']);
Route::get('/trabajos/{id}/actividades', [ActividadController::class , 'getByTrabajo']);
Route::delete('/actividades/{id}', [ActividadController::class , 'destroy']);

// 🛠️ RUTAS DE SOLICITUDES DE MANTENIMIENTO
    Route::get('/mantenimiento-solicitudes', [MantenimientoSolicitudController::class, 'index']);
    Route::post('/mantenimiento-solicitudes', [MantenimientoSolicitudController::class, 'store']);
    Route::get('/mantenimiento-solicitudes/{id}', [MantenimientoSolicitudController::class, 'show']);
    Route::post('/mantenimiento-solicitudes/{id}/asignar-visita', [MantenimientoSolicitudController::class, 'asignarVisita']);
    Route::post('/mantenimiento-solicitudes/{id}/asignar-reparacion', [MantenimientoSolicitudController::class, 'asignarReparacion']);
// 🖼️ RUTA PARA SUBIDA DE IMÁGENES GENÉRICA
Route::post('/upload-imagen', [ImageController::class, 'upload']);

// 🏷️ RUTAS DE CATEGORÍAS DE EQUIPOS
Route::get('/categorias-equipos', [CategoriaEquipoController::class, 'index']);
Route::post('/categorias-equipos', [CategoriaEquipoController::class, 'store']);
Route::delete('/categorias-equipos/{id}', [CategoriaEquipoController::class, 'destroy']);
Route::get('/equipos-consumo', [CategoriaEquipoController::class, 'consumoReporte']);
Route::post('/equipos-consumo', [CategoriaEquipoController::class, 'addConsumoManual']);
Route::put('/equipos-consumo/{id}/categoria', [CategoriaEquipoController::class, 'updateConsumoCategoria']);

// 🔧 RUTAS DE EQUIPOS INDIVIDUALES (Admin)
Route::put('/equipos/{id}', [NegocioController::class, 'updateEquipo']);
Route::get('/equipos/{id}/historial', [NegocioController::class, 'getEquipoHistorial']);