<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Sistema\LoginController;
use App\Http\Controllers\Sistema\ControlController;
use App\Http\Controllers\Sistema\RolesController;
use App\Http\Controllers\Sistema\PerfilController;
use App\Http\Controllers\Sistema\PermisoController;
use App\Http\Controllers\Sistema\ConfiguracionController;
use App\Http\Controllers\Sistema\RepuestosController;
use App\Http\Controllers\Sistema\SalidasController;
use App\Http\Controllers\Sistema\HistorialController;
use App\Http\Controllers\Sistema\ReportesController;


Route::get('/', [LoginController::class,'vistaLoginForm'])->name('login.admin');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('admin.logout');

// --- REDIRECCIONAMIENTO A OTROS SISTEMAS ---
Route::get('/redireccionamiento/sistemas', [LoginController::class,'vistaRedireccionOtrosSistemas'])->name('admin.redireccionamiento.index');


Route::middleware('auth:admin')->group(function () {

    // --- ROLES ----.
    Route::get('/admin/roles/index', [RolesController::class,'index'])->name('admin.roles.index');
    Route::get('/admin/roles/tabla', [RolesController::class,'tablaRoles']);
    Route::get('/admin/roles/lista/permisos/{id}', [RolesController::class,'vistaPermisos']);
    Route::get('/admin/roles/permisos/tabla/{id}', [RolesController::class,'tablaRolesPermisos']);
    Route::post('/admin/roles/permiso/borrar', [RolesController::class, 'borrarPermiso']);
    Route::post('/admin/roles/permiso/agregar', [RolesController::class, 'agregarPermiso']);
    Route::get('/admin/roles/permisos/lista', [RolesController::class,'listaTodosPermisos']);
    Route::get('/admin/roles/permisos-todos/tabla', [RolesController::class,'tablaTodosPermisos']);
    Route::post('/admin/roles/borrar-global', [RolesController::class, 'borrarRolGlobal']);

    // --- PERMISOS ---
    Route::get('/admin/permisos/index', [PermisoController::class,'index'])->name('admin.permisos.index');
    Route::get('/admin/permisos/tabla', [PermisoController::class,'tablaUsuarios']);
    Route::post('/admin/permisos/nuevo-usuario', [PermisoController::class, 'nuevoUsuario']);
    Route::post('/admin/permisos/info-usuario', [PermisoController::class, 'infoUsuario']);
    Route::post('/admin/permisos/editar-usuario', [PermisoController::class, 'editarUsuario']);
    Route::post('/admin/permisos/nuevo-rol', [PermisoController::class, 'nuevoRol']);
    Route::post('/admin/permisos/extra-nuevo', [PermisoController::class, 'nuevoPermisoExtra']);
    Route::post('/admin/permisos/extra-borrar', [PermisoController::class, 'borrarPermisoGlobal']);

    // --- PERFIL ---
    Route::get('/admin/editar-perfil/index', [PerfilController::class,'indexEditarPerfil'])->name('admin.perfil');
    Route::post('/admin/editar-perfil/actualizar', [PerfilController::class, 'editarUsuario']);

    Route::get('sin-permisos', [ControlController::class,'indexSinPermiso'])->name('no.permisos.index');

    // --- CONTROL WEB ---
    Route::get('/panel', [ControlController::class,'indexRedireccionamiento'])->name('admin.panel');


    // --- UNIDAD DE MEDIDA ---
    Route::get('/admin/unidadmedida/index', [ConfiguracionController::class,'indexUnidadMedida'])->name('admin.unidadmedida.index');
    Route::get('/admin/unidadmedida/tabla/index', [ConfiguracionController::class,'tablaUnidadMedida']);
    Route::post('/admin/unidadmedida/nuevo', [ConfiguracionController::class, 'nuevaUnidadMedida']);
    Route::post('/admin/unidadmedida/informacion', [ConfiguracionController::class, 'informacionUnidadMedida']);
    Route::post('/admin/unidadmedida/editar', [ConfiguracionController::class, 'editarUnidadMedida']);

    // --- TIPO DE COMPRA ---
    Route::get('/admin/tipodecompra/index', [ConfiguracionController::class,'indexTipoDeCompra'])->name('admin.tipodecompra.index');
    Route::get('/admin/tipodecompra/tabla/index', [ConfiguracionController::class,'tablaTipoDeCompra']);
    Route::post('/admin/tipodecompra/nuevo', [ConfiguracionController::class, 'nuevaTipoDeCompra']);
    Route::post('/admin/tipodecompra/informacion', [ConfiguracionController::class, 'informacionTipoDeCompra']);
    Route::post('/admin/tipodecompra/editar', [ConfiguracionController::class, 'editarTipoDeCompra']);

    // --- PROVEEDOR ---
    Route::get('/admin/proveedor/index', [ConfiguracionController::class,'indexProveedor'])->name('admin.proveedor.index');
    Route::get('/admin/proveedor/tabla/index', [ConfiguracionController::class,'tablaProveedor']);
    Route::post('/admin/proveedor/nuevo', [ConfiguracionController::class, 'nuevaProveedor']);
    Route::post('/admin/proveedor/informacion', [ConfiguracionController::class, 'informacionProveedor']);
    Route::post('/admin/proveedor/editar', [ConfiguracionController::class, 'editarProveedor']);

    // --- RUBRO ---
    Route::get('/admin/rubro/index', [ConfiguracionController::class,'indexRubro'])->name('admin.rubro.index');
    Route::get('/admin/rubro/tabla/index', [ConfiguracionController::class,'tablaRubro']);
    Route::post('/admin/rubro/nuevo', [ConfiguracionController::class, 'nuevaRubro']);
    Route::post('/admin/rubro/informacion', [ConfiguracionController::class, 'informacionRubro']);
    Route::post('/admin/rubro/editar', [ConfiguracionController::class, 'editarRubro']);

    // --- CUENTA ---
    Route::get('/admin/cuenta/index', [ConfiguracionController::class, 'indexCuenta'])->name('admin.cuenta.index');
    Route::get('/admin/cuenta/tabla/index', [ConfiguracionController::class, 'tablaCuenta']);
    Route::post('/admin/cuenta/nuevo', [ConfiguracionController::class, 'nuevaCuenta']);
    Route::post('/admin/cuenta/informacion', [ConfiguracionController::class, 'informacionCuenta']);
    Route::post('/admin/cuenta/editar', [ConfiguracionController::class, 'editarCuenta']);

    // --- OBJETO ESPECIFICO ---
    Route::get('/admin/objetoespecifico/index', [ConfiguracionController::class, 'indexObjetoEspecifico'])->name('admin.objetoespecifico.index');
    Route::get('/admin/objetoespecifico/tabla/index', [ConfiguracionController::class, 'tablaObjetoEspecifico']);
    Route::post('/admin/objetoespecifico/nuevo', [ConfiguracionController::class, 'nuevaObjetoEspecifico']);
    Route::post('/admin/objetoespecifico/informacion', [ConfiguracionController::class, 'informacionObjetoEspecifico']);
    Route::post('/admin/objetoespecifico/editar', [ConfiguracionController::class, 'editarObjetoEspecifico']);

    // --- INVENTARIO ---
    Route::get('/admin/inventario/index', [RepuestosController::class,'index'])->name('admin.materiales.index');
    Route::get('/admin/inventario/tabla/index', [RepuestosController::class,'tablaMateriales']);
    Route::post('/admin/inventario/nuevo', [RepuestosController::class, 'nuevoMaterial']);
    Route::post('/admin/inventario/informacion', [RepuestosController::class, 'informacionMaterial']);
    Route::post('/admin/inventario/editar', [RepuestosController::class, 'editarMaterial']);
    Route::post('/admin/inventario/catalogo', [RepuestosController::class, 'inventarioConteoDeMateriales']);

    // --- REGISTRAR ENTRADA ---
    Route::get('/admin/registro/entrada', [RepuestosController::class,'indexRegistroEntrada'])->name('admin.entrada.registro.index');
    Route::post('/admin/buscar/material',  [RepuestosController::class,'buscadorMaterial']);
    Route::post('/admin/entradas/guardar',  [RepuestosController::class,'guardarEntrada']);

    // --- REGISTRAR SALIDA ---
    Route::get('/admin/registro/salida', [SalidasController::class,'indexRegistroSalida'])->name('admin.salida.registro.index');
    Route::post('/admin/salida/guardar',  [SalidasController::class,'guardarSalida']);
    Route::post('/admin/buscar/material/disponible',  [SalidasController::class,'buscadorMaterialDisponible']);
    Route::post('/admin/buscar/material/disponibilidad', [SalidasController::class, 'infoBodegaMaterialDetalleFila']);

    // --- PENDIENTES ---
    Route::get('/admin/pendientes/entregas',          [SalidasController::class, 'indexPendienteEntrega'])->name('admin.pendientes.index');
    Route::post('/admin/pendientes/salida-parcial',   [SalidasController::class, 'registrarSalidaParcial'])->name('admin.pendientes.parcial');
    Route::post('/admin/pendientes/finalizar',        [SalidasController::class, 'finalizarDetalle'])->name('admin.pendientes.finalizar');
    Route::post('/admin/pendientes/detalle-entregas', [SalidasController::class, 'detalleEntregas'])->name('admin.pendientes.detalle');
    Route::post('/admin/pendientes/entrega/editar',     [SalidasController::class, 'editarEntrega'])->name('admin.pendientes.entrega.editar');
    Route::post('/admin/pendientes/entrega/actualizar', [SalidasController::class, 'actualizarEntrega'])->name('admin.pendientes.entrega.actualizar');
    Route::post('/admin/pendientes/entrega/eliminar',   [SalidasController::class, 'eliminarEntrega'])->name('admin.pendientes.entrega.eliminar');

    // --- HISTORIAL / ENTRADAS ---
    Route::get('/admin/historial/entradas', [HistorialController::class,'indexHistorialEntradas'])->name('admin.historial.entradas.index');
    Route::get('/admin/historial/entradas/tabla',  [HistorialController::class,'tablaHistorialEntradas']);
    Route::post('/admin/historial/entradas/informacion', [HistorialController::class, 'informacionEntrada']);
    Route::post('/admin/historial/entradas/editar',      [HistorialController::class, 'editarEntrada']);
    Route::post('/admin/historial/entradas/eliminar',    [HistorialController::class, 'eliminarEntrada']);
    Route::post('/admin/historial/entradas/detalle',        [HistorialController::class, 'detalleEntrada']);
    Route::post('/admin/historial/entradas/detalle/editar', [HistorialController::class, 'editarDetalleEntrada']);
    Route::post('/admin/historial/entradas/detalle/eliminar', [HistorialController::class, 'eliminarDetalleEntrada']);
    Route::get('/admin/historial/entradas/extras/{id}', [HistorialController::class, 'vistaExtrasEntrada'])->name('admin.historial.entradas.extras');
    Route::post('/admin/historial/entradas/extras/guardar', [HistorialController::class, 'guardarExtrasEntrada']);

    // --- HISTORIAL / SALIDAS ---
    Route::get('/admin/historial/salidas', [HistorialController::class,'indexHistorialSalidas'])->name('admin.historial.salidas.index');
    Route::get('/admin/historial/salidas/tabla',  [HistorialController::class,'tablaHistorialSalidas']);
    Route::post('/admin/historial/salidas/informacion', [HistorialController::class, 'informacionSalida']);
    Route::post('/admin/historial/salidas/editar',      [HistorialController::class, 'editarSalida']);
    Route::post('/admin/historial/salidas/eliminar',    [HistorialController::class, 'eliminarSalida']);
    Route::post('/admin/historial/salidas/detalle', [HistorialController::class, 'detalleSalida']);

    // --- REPORTES ---
    Route::get('/admin/reporte/generales', [ReportesController::class,'vistaReporteGenerales'])->name('admin.reporte.generales.index');
    Route::get('/admin/reporte/pdf/inventario', [ReportesController::class,'generarPDFExistencias']);
    Route::get('/admin/bodega/reportespdf/inicial/final/{desde}/{hasta}', [ReportesController::class, 'reportePDFInicialPorPeriodos']);

    // PDF - ITEM ENTREGADO A UNIDADES
    Route::get('/admin/reporte/entregadopdf/unidades/{idDep}/{desde?}/{hasta?}',
        [ReportesController::class, 'reportePDFEntregadoAunidades']);

    Route::get('/admin/reporte/entregadopdf/material/{idMat}/{idTipo}/{desde?}/{hasta?}',
        [ReportesController::class, 'reportePDFEntregadoPorMaterial']);

    Route::get('/admin/reporte/pendientes/material/{idMat}/{desde?}/{hasta?}',
        [ReportesController::class, 'reportePDFEntregasPendientesPorMaterial'])
        ->name('reporte.pendientes.material');











}); // end auth





