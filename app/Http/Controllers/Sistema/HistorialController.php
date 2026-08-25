<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use App\Models\Departamentos;
use App\Models\Entradas;
use App\Models\EntradasDetalle;
use App\Models\Equipos;
use App\Models\InformacionGeneral;
use App\Models\Materiales;
use App\Models\Proveedor;
use App\Models\Reserva;
use App\Models\Salidas;
use App\Models\SalidasDetalle;
use App\Models\TipoCompra;
use App\Models\TipoEntrada;
use App\Models\TipoProyecto;
use App\Models\TipoSalida;
use App\Models\Transferencia;
use App\Models\TransferenciaDetalle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class HistorialController extends Controller
{

    public function indexHistorialEntradas()
    {
        $arrayTipoCompra = TipoCompra::orderBy('nombre')->get();
        $arrayProveedores = Proveedor::orderBy('nombre')->get();

        return view('backend.admin.historial.entradas.vistahistorialentradas',
            compact('arrayTipoCompra', 'arrayProveedores'));
    }

    public function tablaHistorialEntradas(Request $request)
    {
        $arrayEntradas = Entradas::with(['tipoCompra', 'proveedor'])
            ->when($request->fecha_desde, fn($q) => $q->whereDate('fecha', '>=', $request->fecha_desde))
            ->when($request->fecha_hasta, fn($q) => $q->whereDate('fecha', '<=', $request->fecha_hasta))
            ->when($request->tipocompra,  fn($q) => $q->where('id_tipocompra', $request->tipocompra))
            ->when($request->proveedor,   fn($q) => $q->where('id_proveedor',  $request->proveedor))
            // NUEVOS:
            ->when($request->factura, fn($q) => $q->where('lote', 'like', '%' . $request->factura . '%'))
            ->when($request->material, function ($q) use ($request) {
                $q->whereHas('detalle.material', function ($q2) use ($request) {
                    $q2->where('nombre', 'like', '%' . $request->material . '%');
                });
            })
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function ($item) {
                $item->fecha_fmt = date('d/m/Y', strtotime($item->fecha));
                return $item;
            });

        return view('backend.admin.historial.entradas.tablahistorialentradas',
            compact('arrayEntradas'));
    }

    public function informacionEntrada(Request $request)
    {
        $entrada = Entradas::find($request->id);

        if (!$entrada) {
            return response()->json(['success' => 0]);
        }

        return response()->json([
            'success' => 1,
            'entrada' => [
                'id'           => $entrada->id,
                'fecha'        => $entrada->fecha,
                'lote'      =>    $entrada->lote,
                'descripcion'  => $entrada->descripcion,
                'id_tipocompra'=> $entrada->id_tipocompra,
                'id_proveedor' => $entrada->id_proveedor,
            ]
        ]);
    }


    public function editarEntrada(Request $request)
    {
        $entrada = Entradas::find($request->id);

        if (!$entrada) {
            return response()->json(['success' => 0]);
        }

        $entrada->fecha         = $request->fecha;
        $entrada->lote       =    $request->factura      ?: null;
        $entrada->descripcion   = $request->descripcion  ?: null;
        $entrada->id_tipocompra = $request->id_tipocompra;
        $entrada->id_proveedor  = $request->id_proveedor ?: null;
        $entrada->save();

        return response()->json(['success' => 1]);
    }



    public function eliminarEntrada(Request $request)
    {
        $entrada = Entradas::find($request->id);

        if (!$entrada) {
            return response()->json(['success' => 0]);
        }

        DB::beginTransaction();

        try {
            $idsDetalle = $entrada->detalle()->pluck('id');

            if ($idsDetalle->isNotEmpty()) {
                $tieneSalidas = SalidasDetalle::whereIn('id_entrada_detalle', $idsDetalle)->exists();

                if ($tieneSalidas) {
                    DB::rollback();
                    return response()->json([
                        'success' => 2,
                        'msg'     => 'Esta entrada tiene salidas registradas y no puede eliminarse.',
                    ]);
                }

                $entrada->detalle()->delete();
            }

            $entrada->delete();

            DB::commit();
            return response()->json(['success' => 1]);

        } catch (\Throwable $e) {
            DB::rollback();
            Log::error('eliminarEntrada: ' . $e->getMessage());
            return response()->json(['success' => 99]);
        }
    }

    public function detalleEntrada(Request $request)
    {
        $entrada = Entradas::find($request->id);

        if (!$entrada) {
            return response()->json(['success' => 0]);
        }

        $detalle = $entrada->detalle()
            ->with('material')
            ->get()
            ->map(function ($item) {
                $tieneSalidas = SalidasDetalle::where('id_entrada_detalle', $item->id)->exists();
                return [
                    'id'               => $item->id,
                    'codigo'           => $item->codigo ?? '',
                    'nombre'           => $item->nombre ?? '',
                    'material'         => $item->material->nombre ?? $item->nombre ?? '',
                    'cantidad_inicial' => $item->cantidad_inicial,
                    'precio'           => number_format($item->precio, 4),
                    'precio_raw'       => $item->precio,
                    'tiene_salidas'    => $tieneSalidas ? 1 : 0,
                ];
            });

        return response()->json([
            'success' => 1,
            'detalle' => $detalle,
        ]);
    }

    public function editarDetalleEntrada(Request $request)
    {
        $detalle = EntradasDetalle::find($request->id);

        if (!$detalle) {
            return response()->json(['success' => 0]);
        }

        $detalle->codigo = $request->codigo ?: null;
        $detalle->precio = $request->precio;

        if ($request->filled('cantidad')) {
            $tieneSalidas = SalidasDetalle::where('id_entrada_detalle', $detalle->id)->exists();
            if ($tieneSalidas) {
                return response()->json([
                    'success' => 2,
                    'msg'     => 'No se puede modificar la cantidad porque este material ya tiene salidas registradas.',
                ]);
            }
            $detalle->cantidad_inicial = (int) $request->cantidad;
        }

        $detalle->save();

        return response()->json(['success' => 1]);
    }

    public function eliminarDetalleEntrada(Request $request)
    {
        $detalle = EntradasDetalle::find($request->id);

        if (!$detalle) {
            return response()->json(['success' => 0]);
        }

        $tieneSalidas = SalidasDetalle::where('id_entrada_detalle', $detalle->id)->exists();
        if ($tieneSalidas) {
            return response()->json([
                'success' => 4,
                'msg'     => 'Este material ya tiene salidas registradas y no puede eliminarse.',
            ]);
        }

        DB::beginTransaction();
        try {
            $entradaId = $detalle->id_entradas;
            $detalle->delete();

            $quedan = EntradasDetalle::where('id_entradas', $entradaId)->count();

            if ($quedan === 0) {
                Entradas::where('id', $entradaId)->delete();
                DB::commit();
                return response()->json(['success' => 1, 'entrada_borrada' => true]);
            }

            DB::commit();
            return response()->json(['success' => 1, 'entrada_borrada' => false]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('eliminarDetalleEntrada: ' . $e->getMessage());
            return response()->json(['success' => 99, 'msg' => 'Error al eliminar.']);
        }
    }


    public function vistaExtrasEntrada($id)
    {
        $entrada = Entradas::find($id);

        if (!$entrada) {
            return redirect()->route('admin.historial.entradas.index');
        }

        return view('backend.admin.historial.entradas.vistaextras', compact('entrada'));
    }

    public function guardarExtrasEntrada(Request $request)
    {
        $entrada = Entradas::find($request->id_entrada);

        if (!$entrada) {
            return response()->json(['success' => 0]);
        }

        $contenedor = json_decode($request->contenedorArray, true);

        if (empty($contenedor)) {
            return response()->json(['success' => 0]);
        }

        DB::beginTransaction();
        try {
            foreach ($contenedor as $item) {
                $detalle = new EntradasDetalle();
                $detalle->id_entradas      = $entrada->id;
                $detalle->id_material      = $item['idMaterial'];
                $detalle->cantidad_inicial = $item['infoCantidad'];
                $detalle->codigo           = $item['infoCodigo'] ?: null;
                $detalle->precio           = $item['infoPrecio'];
                $detalle->nombre           = $item['infoNombre'] ?? null;
                $detalle->save();
            }

            DB::commit();
            return response()->json(['success' => 1]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('guardarExtrasEntrada: ' . $e->getMessage());
            return response()->json(['success' => 99]);
        }
    }

    //***** ========================================================================================= **********


// ── Index ─────────────────────────────────────────────────────────────────────
    public function indexHistorialSalidas()
    {
        $arrayTipoSalida        = TipoSalida::orderBy('nombre')->get();
        $arrayDepartamentos     = Departamentos::orderBy('nombre')->get();
        $arrayObjetoEspecifico  = DB::table('objeto_especifico')->orderBy('codigo')->get();
        $arraySalidas           = collect();

        return view('backend.admin.historial.salidas.vistahistorialsalidas',
            compact('arrayTipoSalida', 'arrayDepartamentos', 'arrayObjetoEspecifico', 'arraySalidas'));
    }

    // ── Tabla (cargada vía jQuery .load()) ───────────────────────────────────────
    public function tablaHistorialSalidas(Request $request)
    {
        $arraySalidas = DB::table('salidas_detalle as sd')
            ->join('entradas_detalle as ed', 'ed.id', '=', 'sd.id_entrada_detalle')
            ->join('materiales as m', 'm.id', '=', 'ed.id_material')
            ->leftJoin('tipo_salida as ts', 'ts.id', '=', 'sd.id_tiposalida')
            ->leftJoin('departamentos as dep', 'dep.id', '=', 'sd.id_departamento')
            ->leftJoin('objeto_especifico as oe', 'oe.id', '=', 'm.id_objespecifico') // ← ajusta la FK según tu schema
            ->select(
                'sd.id',
                'sd.fecha',
                'sd.numero_solicitud',
                'sd.descripcion',
                'sd.cantidad_salida',
                'sd.estado',
                'm.nombre as material',
                'ts.nombre as tipo_salida',
                'dep.nombre as departamento',
                'ed.precio',
                'oe.codigo as objeto_codigo',       // ← NUEVO
                'oe.nombre as objeto_nombre',        // ← NUEVO
                DB::raw('(sd.cantidad_salida * ed.precio) as subtotal'),
                DB::raw('(SELECT COUNT(*) FROM salidas_detalle_entregas WHERE id_salida_detalle = sd.id) as total_entregas')
            )
            ->when($request->tiposalida, fn($q) =>
            $q->where('sd.id_tiposalida', $request->tiposalida)
            )
            ->when($request->departamento, fn($q) =>
            $q->where('sd.id_departamento', $request->departamento)
            )
            ->when($request->fecha_desde, fn($q) =>
            $q->whereDate('sd.fecha', '>=', $request->fecha_desde)
            )
            ->when($request->fecha_hasta, fn($q) =>
            $q->whereDate('sd.fecha', '<=', $request->fecha_hasta)
            )
            ->when($request->material, fn($q) =>
            $q->where('m.nombre', 'LIKE', '%' . $request->material . '%')
            )
            ->when($request->solicitud, fn($q) =>
            $q->where('sd.numero_solicitud', 'LIKE', '%' . $request->solicitud . '%')
            )
            ->when($request->objeto_especifico, fn($q) =>   // ← NUEVO filtro
            $q->where('oe.id', $request->objeto_especifico)
            )
            ->orderBy('sd.fecha', 'desc')
            ->orderBy('sd.id', 'desc')
            ->get();

        return view('backend.admin.historial.salidas.tablahistorialsalidas',
            compact('arraySalidas'));
    }

// ── Información de una salida para editar ─────────────────────────────────────
    public function informacionSalida(Request $request)
    {
        $salida = DB::table('salidas_detalle as sd')
            ->leftJoin('tipo_salida as ts', 'ts.id', '=', 'sd.id_tiposalida')
            ->leftJoin('departamentos as dep', 'dep.id', '=', 'sd.id_departamento')
            ->select(
                'sd.id',
                'sd.fecha',
                'sd.numero_solicitud',
                'sd.descripcion',
                'sd.id_tiposalida',
                'sd.id_departamento',
                'sd.estado',
                'ts.nombre as tipo_salida',
                'dep.nombre as departamento'
            )
            ->where('sd.id', $request->id)
            ->first();

        if (!$salida) return ['success' => 0];

        return ['success' => 1, 'salida' => $salida];
    }

// ── Editar salida ─────────────────────────────────────────────────────────────
    public function editarSalida(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'            => 'required|exists:salidas_detalle,id',
            'fecha'         => 'required|date',
            'id_tiposalida' => 'required|exists:tipo_salida,id',
        ]);

        if ($validator->fails()) return ['success' => 0];

        try {
            DB::table('salidas_detalle')
                ->where('id', $request->id)
                ->update([
                    'fecha'            => $request->fecha,
                    'id_tiposalida'    => $request->id_tiposalida,
                    'id_departamento'  => $request->id_departamento ?: null,
                    'numero_solicitud' => $request->numero_solicitud ?: null,
                    'descripcion'      => $request->descripcion      ?: null,
                    'estado'           => in_array($request->estado, ['pendiente', 'finalizado'])
                        ? $request->estado : 'finalizado',
                ]);

            return ['success' => 1];

        } catch (\Throwable $e) {
            Log::error('editarSalida: ' . $e);
            return ['success' => 99];
        }
    }

// ── Eliminar salida ───────────────────────────────────────────────────────────
    public function eliminarSalida(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:salidas_detalle,id',
        ]);

        if ($validator->fails()) return ['success' => 0];

        DB::beginTransaction();
        try {
            // Eliminar entregas adicionales primero
            DB::table('salidas_detalle_entregas')
                ->where('id_salida_detalle', $request->id)
                ->delete();

            DB::table('salidas_detalle')
                ->where('id', $request->id)
                ->delete();

            DB::commit();
            return ['success' => 1];

        } catch (\Throwable $e) {
            DB::rollback();
            Log::error('eliminarSalida: ' . $e);
            return ['success' => 99];
        }
    }

// ── Detalle de una salida (info + entregas adicionales) ───────────────────────
    public function detalleSalida(Request $request)
    {
        $salida = DB::table('salidas_detalle as sd')
            ->join('entradas_detalle as ed', 'ed.id', '=', 'sd.id_entrada_detalle')
            ->join('materiales as m', 'm.id', '=', 'ed.id_material')
            ->leftJoin('tipo_salida as ts', 'ts.id', '=', 'sd.id_tiposalida')
            ->leftJoin('departamentos as dep', 'dep.id', '=', 'sd.id_departamento')
            ->select(
                'sd.id',
                'sd.fecha',
                'sd.numero_solicitud',
                'sd.descripcion',
                'sd.cantidad_salida',
                'sd.estado',
                'm.nombre as material',
                'ts.nombre as tipo_salida',
                'dep.nombre as departamento'
            )
            ->where('sd.id', $request->id)
            ->first();

        if (!$salida) return ['success' => 0];

        $entregas = DB::table('salidas_detalle_entregas as sde')
            ->leftJoin('departamentos as dep', 'dep.id', '=', 'sde.id_departamento')
            ->select(
                'sde.id',
                'sde.cantidad',
                'sde.fecha_entrega',
                'sde.observacion',
                'dep.nombre as departamento'
            )
            ->where('sde.id_salida_detalle', $request->id)
            ->orderBy('sde.created_at', 'asc')
            ->get();

        return ['success' => 1, 'salida' => $salida, 'entregas' => $entregas];
    }








}
