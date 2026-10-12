<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use App\Models\Departamentos;
use App\Models\Entradas;
use App\Models\EntradasDetalle;
use App\Models\InformacionGeneral;
use App\Models\Materiales;
use App\Models\Salidas;
use App\Models\SalidasDetalle;
use App\Models\TipoSalida;
use App\Models\UnidadMedida;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SalidasController extends Controller
{

    public function indexRegistroSalida()
    {
        $arrayTipoSalida    = TipoSalida::orderBy('nombre')->get();
        $arrayDepartamentos = Departamentos::orderBy('nombre')->get();

        return view('backend.admin.repuestos.salidas.vistasalidaregistro',
            compact('arrayTipoSalida', 'arrayDepartamentos'));
    }



    public function guardarSalida(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contenedorArray' => 'required',
        ]);

        if ($validator->fails()) return ['success' => 0];

        $contenedor = json_decode($request->contenedorArray, true);
        if (empty($contenedor)) return ['success' => 0];

        // Validar que todos los items traigan tiposalida
        foreach ($contenedor as $item) {
            if (empty($item['infoTipoSalida'])) return ['success' => 0];
        }

        DB::beginTransaction();

        try {

            // ── Acumular cantidades por id_entrada_detalle ────────────────
            $acumulado = [];
            foreach ($contenedor as $item) {
                $id = $item['infoIdEntradaDeta'];
                if (!isset($acumulado[$id])) $acumulado[$id] = 0;
                $acumulado[$id] += (int) $item['infoCantidad'];
            }

            // ── Validar stock acumulado + obtener fecha de entrada ────────
            $fechasEntrada = []; // cache: id_entrada_detalle => fecha de entrada

            foreach ($acumulado as $idEntradaDetalle => $cantidadSalida) {

                $entradaInfo = DB::table('entradas_detalle as ed')
                    ->join('entradas as e', 'e.id', '=', 'ed.id_entradas')
                    ->where('ed.id', $idEntradaDetalle)
                    ->select('ed.cantidad_inicial', 'e.fecha')
                    ->first();

                if (is_null($entradaInfo)) {
                    DB::rollback();
                    return ['success' => 0];
                }

                $fechasEntrada[$idEntradaDetalle] = $entradaInfo->fecha;

                $totalSalido = DB::table('salidas_detalle')
                    ->where('id_entrada_detalle', $idEntradaDetalle)
                    ->sum('cantidad_salida');

                $disponible = (int) $entradaInfo->cantidad_inicial - (int) $totalSalido;

                if ($cantidadSalida > $disponible) {
                    DB::rollback();

                    $nombreMaterial = DB::table('entradas_detalle as ed')
                        ->join('materiales as m', 'm.id', '=', 'ed.id_material')
                        ->where('ed.id', $idEntradaDetalle)
                        ->value('m.nombre');

                    return [
                        'success'         => 2,
                        'nombre_material' => $nombreMaterial ?? 'Material desconocido',
                        'cantidad_pedida' => $cantidadSalida,
                        'disponible'      => $disponible,
                    ];
                }
            }

            // ── Validar fecha de salida no menor a la fecha de entrada (por ítem) ──
            foreach ($contenedor as $item) {
                $idEntradaDetalle = $item['infoIdEntradaDeta'];
                $fechaSalidaItem  = $item['infoFechaItem'] ?? null;

                if (empty($fechaSalidaItem)) continue;

                $fechaEntrada = $fechasEntrada[$idEntradaDetalle] ?? null;
                if (empty($fechaEntrada)) continue;

                $fEntrada = substr($fechaEntrada, 0, 10);
                $fSalida  = substr($fechaSalidaItem, 0, 10);

                if (strtotime($fSalida) < strtotime($fEntrada)) {
                    DB::rollback();

                    $nombreMaterial = DB::table('entradas_detalle as ed')
                        ->join('materiales as m', 'm.id', '=', 'ed.id_material')
                        ->where('ed.id', $idEntradaDetalle)
                        ->value('m.nombre');

                    return [
                        'success'         => 3,
                        'nombre_material' => $nombreMaterial ?? 'Material desconocido',
                        'fecha_entrada'   => date('d-m-Y', strtotime($fEntrada)),
                        'fecha_salida'    => date('d-m-Y', strtotime($fSalida)),
                    ];
                }
            }

            // ── Guardar cada ítem directo en salidas_detalle ──────────────
            foreach ($contenedor as $item) {
                $detalle                     = new SalidasDetalle();
                $detalle->id_entrada_detalle = (int) $item['infoIdEntradaDeta'];
                $detalle->id_tiposalida      = (int) $item['infoTipoSalida'];
                $detalle->cantidad_salida    = (int) $item['infoCantidad'];
                $detalle->estado             = in_array($item['infoEstado'], ['pendiente', 'finalizado'])
                    ? $item['infoEstado']
                    : 'pendiente';

                // Campos por ítem (ya vienen resueltos desde el JS — global sobreescribe fila)
                $detalle->fecha            = !empty($item['infoFechaItem'])       ? $item['infoFechaItem']          : null;
                $detalle->numero_solicitud = !empty($item['infoSolicitudItem'])   ? $item['infoSolicitudItem']      : null;
                $detalle->descripcion      = !empty($item['infoDescripcionItem']) ? $item['infoDescripcionItem']    : null;
                $detalle->id_departamento  = !empty($item['infoDepartamento'])    ? (int) $item['infoDepartamento'] : null;

                $detalle->save();
            }

            DB::commit();
            return ['success' => 10];

        } catch (\Throwable $e) {
            DB::rollback();
            Log::error('guardarSalida: ' . $e);
            return ['success' => 99];
        }
    }


    public function buscadorMaterialDisponible(Request $request)
    {
        if (!$request->get('query')) return '';

        $query = $request->get('query');

        $materiales = Materiales::where('nombre', 'LIKE', "%{$query}%")->pluck('id');

        if ($materiales->isEmpty()) return '';

        $listado = DB::table('entradas_detalle as ed')
            ->leftJoin(
                DB::raw('(
                SELECT id_entrada_detalle, SUM(cantidad_salida) as total_salido
                FROM salidas_detalle
                GROUP BY id_entrada_detalle
            ) as sd'),
                'sd.id_entrada_detalle', '=', 'ed.id'
            )
            ->select(
                'ed.id_material',
                DB::raw('SUM(ed.cantidad_inicial) as total_inicial'),
                DB::raw('COALESCE(SUM(sd.total_salido), 0) as total_salido'),
                DB::raw('(SUM(ed.cantidad_inicial) - COALESCE(SUM(sd.total_salido), 0)) as disponible')
            )
            ->whereIn('ed.id_material', $materiales)
            ->groupBy('ed.id_material')
            ->havingRaw('disponible > 0')
            ->orderBy('ed.id_material')
            ->get();

        if ($listado->isEmpty()) return '';

        // Una sola consulta en vez de un find() por cada fila
        $infoMateriales = Materiales::with(['unidadMedida', 'marca'])
            ->whereIn('id', $listado->pluck('id_material'))
            ->get()
            ->keyBy('id');

        $output = '<ul class="dropdown-menu" style="display:block;position:relative;overflow:auto;max-height:300px;width:800px">';

        foreach ($listado as $row) {
            $infoMaterial = $infoMateriales->get($row->id_material);
            if (!$infoMaterial) continue;

            $medida = optional($infoMaterial->unidadMedida)->nombre;
            $marca  = optional($infoMaterial->marca)->nombre;

            $nombreCompleto = e($infoMaterial->nombre)
                . ' (' . e($medida) . ')'
                . ($marca ? ' — Marca: ' . e($marca) : '');

            $output .= '
        <li class="cursor-pointer" onclick="modificarValor(this)"
            id="' . $row->id_material . '"
            data-tipo="material">
            ' . $nombreCompleto . ' — Disponible: ' . $row->disponible . '
        </li>
        <hr>';
        }

        $output .= '</ul>';
        return $output;
    }



    public function infoBodegaMaterialDetalleFila(Request $request)
    {
        $validar = Validator::make($request->all(), ['id' => 'required']);
        if ($validar->fails()) return ['success' => 0];

        $infoMaterial = Materiales::find($request->id);
        if (!$infoMaterial) return ['success' => 0];

        $infoMedida = UnidadMedida::find($infoMaterial->id_medida);

        $listado = DB::table('entradas_detalle as ed')
            ->leftJoin(
                DB::raw('(
                SELECT id_entrada_detalle, SUM(cantidad_salida) as total_salido
                FROM salidas_detalle
                GROUP BY id_entrada_detalle
            ) as sd'),
                'sd.id_entrada_detalle', '=', 'ed.id'
            )
            ->join('entradas as e', 'e.id', '=', 'ed.id_entradas')
            ->select(
                'ed.id',
                'ed.id_entradas',
                'ed.cantidad_inicial',
                'ed.precio',
                'e.fecha',
                'ed.codigo',
                DB::raw('COALESCE(sd.total_salido, 0) as total_salido'),
                DB::raw('(ed.cantidad_inicial - COALESCE(sd.total_salido, 0)) as cantidadActual')
            )
            ->where('ed.id_material', $request->id)
            ->havingRaw('cantidadActual > 0')
            ->orderBy('ed.id')
            ->get();

        foreach ($listado as $fila) {
            $fila->fechaIngreso = date('d-m-Y', strtotime($fila->fecha));
            $fila->precioFormat = '$' . number_format($fila->precio, 2, '.', ',');
        }

        return [
            'success'        => 1,
            'nombreMaterial' => $infoMaterial->nombre ?? '',
            'nombreMedida'   => $infoMedida->nombre   ?? '',
            'arrayIngreso'   => $listado,
            'disponible'     => $listado->isEmpty() ? 1 : 0,
        ];
    }



// ── Index: listar pendientes ──────────────────────────────────────────────────
    public function indexPendienteEntrega()
    {
        // salidas_detalle ya tiene fecha, numero_solicitud, descripcion directamente
        // (sin join a tabla salidas que ya no existe)
        $pendientes = DB::table('salidas_detalle as sd')
            ->join('entradas_detalle as ed', 'ed.id', '=', 'sd.id_entrada_detalle')
            ->join('materiales as m', 'm.id', '=', 'ed.id_material')
            ->leftJoin('departamentos as dep', 'dep.id', '=', 'sd.id_departamento')
            ->select(
                'sd.id as id_salida_detalle',
                'sd.fecha',
                'sd.numero_solicitud',
                'sd.descripcion',
                'm.nombre as material',
                'sd.cantidad_salida',
                'dep.nombre as departamento'
            )
            ->where('sd.estado', 'pendiente')
            ->orderBy('sd.fecha', 'asc')
            ->orderBy('sd.id', 'asc')
            ->get();

        $arrayDepartamentos = Departamentos::orderBy('nombre')->get();

        return view('backend.admin.repuestos.pendiente.vistapendiente',
            compact('pendientes', 'arrayDepartamentos'));
    }

// ── Agregar nueva entrega a un ítem pendiente ─────────────────────────────────
    public function registrarSalidaParcial(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_salida_detalle' => 'required|exists:salidas_detalle,id',
            'id_departamento'   => 'nullable|exists:departamentos,id',
            'cantidad'          => 'required|integer|min:1',
            'fecha_entrega'     => 'required|date',
            'observacion'       => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) return ['success' => 0];

        try {
            DB::table('salidas_detalle_entregas')->insert([
                'id_salida_detalle' => $request->id_salida_detalle,
                'id_departamento'   => $request->id_departamento ?: null,
                'cantidad'          => $request->cantidad,
                'fecha_entrega'     => $request->fecha_entrega,
                'observacion'       => $request->observacion ?: null,
                'numero_solicitud' => $request->numero_solicitud,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            return ['success' => 10];

        } catch (\Throwable $e) {
            Log::error('registrarSalidaParcial: ' . $e);
            return ['success' => 99];
        }
    }

// ── Marcar ítem como finalizado manualmente ───────────────────────────────────
    public function finalizarDetalle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_salida_detalle' => 'required|exists:salidas_detalle,id',
        ]);

        if ($validator->fails()) return ['success' => 0];

        try {
            DB::table('salidas_detalle')
                ->where('id', $request->id_salida_detalle)
                ->update(['estado' => 'finalizado']);

            return ['success' => 10];

        } catch (\Throwable $e) {
            Log::error('finalizarDetalle: ' . $e);
            return ['success' => 99];
        }
    }


    public function detalleEntregas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_salida_detalle' => 'required|exists:salidas_detalle,id',
        ]);

        if ($validator->fails()) return ['success' => 0];

        // ── Info del kit (cabecera del modal) ─────────────────────────────
        $kit = DB::table('salidas_detalle as sd')
            ->join('entradas_detalle as ed', 'ed.id', '=', 'sd.id_entrada_detalle')
            ->join('materiales as m', 'm.id', '=', 'ed.id_material')
            ->leftJoin('departamentos as dep', 'dep.id', '=', 'sd.id_departamento')
            ->leftJoin('tipo_salida as ts', 'ts.id', '=', 'sd.id_tiposalida')
            ->select(
                'm.nombre as material',
                'sd.fecha',
                'sd.numero_solicitud',
                'sd.descripcion',
                'sd.cantidad_salida',
                'dep.nombre as departamento',
                'ts.nombre as tipo_salida'
            )
            ->where('sd.id', $request->id_salida_detalle)
            ->first();

        // ── Entregas registradas ──────────────────────────────────────────
        $entregas = DB::table('salidas_detalle_entregas as sde')
            ->leftJoin('departamentos as dep', 'dep.id', '=', 'sde.id_departamento')
            ->select(
                'sde.id',
                'sde.id_departamento',
                'sde.cantidad',
                'sde.fecha_entrega',
                'sde.observacion',
                'dep.nombre as departamento',
                'sde.numero_solicitud',
            )
            ->where('sde.id_salida_detalle', $request->id_salida_detalle)
            ->orderBy('sde.created_at', 'asc')
            ->get();

        return ['success' => 10, 'kit' => $kit, 'entregas' => $entregas];
    }

// ── Cargar datos de una entrega para editar ───────────────────────────────────
    public function editarEntrega(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:salidas_detalle_entregas,id',
        ]);

        if ($validator->fails()) return ['success' => 0];

        $entrega = DB::table('salidas_detalle_entregas')
            ->where('id', $request->id)
            ->first();

        return ['success' => 10, 'entrega' => $entrega];
    }

// ── Actualizar entrega ────────────────────────────────────────────────────────
    public function actualizarEntrega(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'              => 'required|exists:salidas_detalle_entregas,id',
            'id_departamento' => 'nullable|exists:departamentos,id',
            'cantidad'        => 'required|integer|min:1',
            'fecha_entrega'   => 'required|date',
            'observacion'     => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) return ['success' => 0];

        try {
            DB::table('salidas_detalle_entregas')
                ->where('id', $request->id)
                ->update([
                    'id_departamento' => $request->id_departamento ?: null,
                    'cantidad'        => $request->cantidad,
                    'fecha_entrega'   => $request->fecha_entrega,
                    'observacion'     => $request->observacion ?: null,
                    'numero_solicitud' => $request->solicitud,
                    'updated_at'      => now(),
                ]);

            return ['success' => 10];

        } catch (\Throwable $e) {
            Log::error('actualizarEntrega: ' . $e);
            return ['success' => 99];
        }
    }

// ── Eliminar entrega ──────────────────────────────────────────────────────────
    public function eliminarEntrega(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:salidas_detalle_entregas,id',
        ]);

        if ($validator->fails()) return ['success' => 0];

        try {
            DB::table('salidas_detalle_entregas')->where('id', $request->id)->delete();
            return ['success' => 10];
        } catch (\Throwable $e) {
            Log::error('eliminarEntrega: ' . $e);
            return ['success' => 99];
        }
    }

}
