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
use App\Models\SalidasDetalleEntregas;
use App\Models\TipoProyecto;
use App\Models\TipoSalida;
use App\Models\Transferencia;
use App\Models\TransferenciaDetalle;
use App\Models\UnidadMedida;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReportesController extends Controller
{



    public function vistaReporteGenerales()
    {
        $arrayUnidades   = Departamentos::orderBy('nombre', 'ASC')->get();
        $arrayMateriales = Materiales::orderBy('nombre', 'ASC')->get();
        $arrayTipos      = TipoSalida::orderBy('id', 'ASC')->get();

        return view('backend.admin.reportes.vistareportegenerales',
            compact('arrayUnidades', 'arrayMateriales', 'arrayTipos'));
    }




    public function generarPDFExistencias($conteo = 0, $pendientes = 1)
    {
        $incluirConteo     = (bool) $conteo;
        $incluirPendientes = (bool) $pendientes;

        $fechaFormat  = date("d-m-Y", strtotime(Carbon::now('America/El_Salvador')));
        $logoalcaldia = 'images/logo.png';

        // ── Consulta principal: existencias actuales (entradas - salidas) ──────────
        $filas = DB::table('entradas_detalle as ed')
            ->join('materiales as m', 'm.id', '=', 'ed.id_material')
            ->leftJoin('unidadmedida as um', 'um.id', '=', 'm.id_medida')
            ->leftJoin('objeto_especifico as obj', 'obj.id', '=', 'm.id_objespecifico')
            ->leftJoin(DB::raw('(
        SELECT id_entrada_detalle, SUM(cantidad_salida) as total_salido
        FROM salidas_detalle GROUP BY id_entrada_detalle
    ) as sd'), 'sd.id_entrada_detalle', '=', 'ed.id')
            ->selectRaw('
        m.id as id_material,
        m.nombre,
        COALESCE(um.nombre, "—") as medida,
        COALESCE(obj.codigo, "SIN-CODIGO") as codigo,
        ed.precio as precio_unitario,
        (ed.cantidad_inicial - COALESCE(sd.total_salido, 0)) as disponible
    ')
            ->havingRaw('disponible > 0')
            ->orderBy('obj.codigo')
            ->orderBy('m.nombre')
            ->orderBy('ed.precio')
            ->get();

        // ── Agrupar por código → material → lotes ────────────────────────────────
        $porCodigo = [];

        foreach ($filas as $fila) {
            $codigo     = $fila->codigo;
            $idMaterial = $fila->id_material;
            $precio     = $fila->precio_unitario;

            if (!isset($porCodigo[$codigo])) {
                $porCodigo[$codigo] = ['codigo' => $codigo, 'materiales' => []];
            }

            if (!isset($porCodigo[$codigo]['materiales'][$idMaterial])) {
                $porCodigo[$codigo]['materiales'][$idMaterial] = [
                    'nombre' => $fila->nombre,
                    'medida' => $fila->medida,
                    'lotes'  => [],
                ];
            }

            if (isset($porCodigo[$codigo]['materiales'][$idMaterial]['lotes'][$precio])) {
                $porCodigo[$codigo]['materiales'][$idMaterial]['lotes'][$precio] += $fila->disponible;
            } else {
                $porCodigo[$codigo]['materiales'][$idMaterial]['lotes'][$precio]  = $fila->disponible;
            }
        }

        foreach ($porCodigo as &$grupo) {
            uasort($grupo['materiales'], fn($a, $b) => strcmp($a['nombre'], $b['nombre']));
            foreach ($grupo['materiales'] as &$mat) {
                ksort($mat['lotes']);
            }
            unset($mat);
        }
        unset($grupo);
        ksort($porCodigo);

        // ── Resumen valorizado por código ────────────────────────────────────────
        $resumenPorCodigo = [];
        $granTotal        = 0;

        foreach ($porCodigo as $codigo => $grupo) {
            $subtotal = 0;
            foreach ($grupo['materiales'] as $mat) {
                foreach ($mat['lotes'] as $precio => $stock) {
                    $subtotal += ($precio * $stock);
                }
            }
            $resumenPorCodigo[$codigo] = $subtotal;
            $granTotal += $subtotal;
        }

        // ── PENDIENTES / KITS ABIERTOS (solo si el toggle lo pide) ─────────────────
        $arrayPendientes = collect();

        if ($incluirPendientes) {
            $entradasDetalle = EntradasDetalle::with('material.unidadMedida')->get();

            foreach ($entradasDetalle as $ed) {
                $material = $ed->material;
                if (!$material) {
                    continue;
                }

                $pendientesQuery = SalidasDetalle::where('id_entrada_detalle', $ed->id)
                    ->where('estado', 'pendiente')
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($pendientesQuery as $pend) {
                    $entregas = SalidasDetalleEntregas::where('id_salida_detalle', $pend->id)
                        ->orderBy('fecha_entrega', 'asc')
                        ->get();

                    $arrayPendientes->push((object)[
                        'nombreMaterial'  => $material->nombre ?? '',
                        'cantidad_salida' => $pend->cantidad_salida,
                        'unidadMedida'    => $material->unidadMedida->nombre ?? '',
                        'descripcion'     => $pend->descripcion ?? '',
                        'entregas'        => $entregas,
                    ]);
                }
            }

            $arrayPendientes = $arrayPendientes->sortBy('nombreMaterial')->values();
        }

        // ── mPDF — HORIZONTAL ────────────────────────────────────────────────────
        $mpdf = new \Mpdf\Mpdf([
            'tempDir'     => sys_get_temp_dir(),
            'format'      => 'LETTER',
            'orientation' => 'L',
        ]);
        $mpdf->SetTitle('Inventario Actual de Materiales');
        $mpdf->showImageErrors = false;

        // ── Estilos ───────────────────────────────────────────────────────────────
        $thStyle = "font-weight:bold; font-size:10px; border:0.8px solid #000;
            padding:8px 4px; background:#d9e1f2; text-align:center;";
        $tdStyle = "font-size:10px; border:0.8px solid #000; padding:7px 4px;";
        $tdC     = $tdStyle . " text-align:center;";
        $tdR     = $tdStyle . " text-align:right;";
        $tdLote  = $tdStyle . " text-align:center; background:#f7f9fd;";
        $tdLoteR = $tdStyle . " text-align:right;  background:#f7f9fd;";

        // ── Encabezado ────────────────────────────────────────────────────────────
        $tabla = "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif;'>
<tr>
    <td style='width:20%; border:0.8px solid #000; padding:6px 8px;'>
        <table width='100%'>
            <tr>
                <td style='width:30%; text-align:left;'>
                    <img src='{$logoalcaldia}' style='height:38px'>
                </td>
                <td style='width:70%; text-align:left; color:#104e8c;
                            font-size:13px; font-weight:bold; line-height:1.3;'>
                    SANTA ANA NORTE<br>EL SALVADOR
                </td>
            </tr>
        </table>
    </td>
    <td style='width:60%; border-top:0.8px solid #000; border-bottom:0.8px solid #000;
               padding:6px 8px; text-align:center; font-size:15px; font-weight:bold;'>
        REPORTE INVENTARIO ACTUAL DE MATERIALES
    </td>
    <td style='width:20%; border:0.8px solid #000; padding:0; vertical-align:top;'>
        <table width='100%' style='font-size:10px;'>
            <tr>
                <td width='40%' style='border-right:0.8px solid #000;
                                       border-bottom:0.8px solid #000; padding:4px 6px;'>
                    <strong>Código:</strong>
                </td>
                <td width='60%' style='border-bottom:0.8px solid #000;
                                       padding:4px 6px; text-align:center;'></td>
            </tr>
            <tr>
                <td style='border-right:0.8px solid #000;
                           border-bottom:0.8px solid #000; padding:4px 6px;'>
                    <strong>Versión:</strong>
                </td>
                <td style='border-bottom:0.8px solid #000;
                           padding:4px 6px; text-align:center;'>000</td>
            </tr>
            <tr>
                <td style='border-right:0.8px solid #000; padding:4px 6px;'>
                    <strong>Fecha de vigencia:</strong>
                </td>
                <td style='padding:4px 6px; text-align:center;'></td>
            </tr>
        </table>
    </td>
</tr>
</table>
<br>";

        $tabla .= "
<table width='100%' style='margin-bottom:4px; border-collapse:collapse;'>
<tr>
    <td style='font-size:13px; padding:4px 0;'>
        <span style='font-weight:bold;'>Reporte:</span>
            Existencias actuales de materiales<br>
        <span style='font-weight:bold;'>Fecha de generación:</span> {$fechaFormat}
    </td>
</tr>
</table>";

        // ── Anchos de columna (se ajustan si hay columna de conteo) ────────────────
        if ($incluirConteo) {
            $wObj = '10%'; $wMat = '28%'; $wMed = '9%';
            $wPrecio = '11%'; $wStock = '8%'; $wTotal = '9%';
            $wConteo = '13%'; $wDiferencia = '12%';
            $colspan = 8;
        } else {
            $wObj = '12%'; $wMat = '38%'; $wMed = '11%';
            $wPrecio = '13%'; $wStock = '10%'; $wTotal = '10%';
            $wConteo = null; $wDiferencia = null;
            $colspan = 6;
        }

        // ── Tabla detalle ─────────────────────────────────────────────────────────
        $thConteo = $incluirConteo
            ? "<th style='{$thStyle} width:{$wConteo};'>Conteo Físico</th>
           <th style='{$thStyle} width:{$wDiferencia};'>Diferencia</th>"
            : "";

        $tabla .= "
<table width='100%' style='border-collapse:collapse;'>
<thead>
    <tr>
        <th style='{$thStyle} width:{$wObj};'>Obj. Espec.</th>
        <th style='{$thStyle} width:{$wMat};'>Material</th>
        <th style='{$thStyle} width:{$wMed};'>Medida</th>
        <th style='{$thStyle} width:{$wPrecio};'>Precio Unit.</th>
        <th style='{$thStyle} width:{$wStock};'>Stock Actual</th>
        <th style='{$thStyle} width:{$wTotal};'>Total</th>
        {$thConteo}
    </tr>
</thead>
<tbody>";

        if (empty($porCodigo)) {
            $tabla .= "
    <tr>
        <td colspan='{$colspan}' style='text-align:center; font-size:12px;
                                        border:0.8px solid #000; padding:12px; color:#888;'>
            No se encontraron materiales con existencias disponibles.
        </td>
    </tr>";
        } else {
            foreach ($porCodigo as $grupo) {

                $tabla .= "
    <tr>
        <td colspan='{$colspan}' style='font-weight:bold; font-size:10px;
                                        border:0.8px solid #000; padding:5px 8px;
                                        background:#e8eef8;'>
            Objeto Específico: " . e($grupo['codigo']) . "
        </td>
    </tr>";

                foreach ($grupo['materiales'] as $mat) {

                    $lotes       = $mat['lotes'];
                    $totalLotes  = count($lotes);
                    $esElPrimero = true;

                    foreach ($lotes as $precio => $stock) {

                        $totalLote   = $precio * $stock;
                        $esLoteExtra = !$esElPrimero;

                        if (!$esLoteExtra) {
                            $celdaNombre = "<td style='{$tdStyle}'>" . e($mat['nombre']) . "</td>
                                 <td style='{$tdC}'>" . e($mat['medida']) . "</td>";
                            $bgCodigo    = $tdC;
                        } else {
                            $celdaNombre = "<td style='{$tdLote}'></td>
                                 <td style='{$tdLote}'></td>";
                            $bgCodigo    = $tdLote;
                        }

                        $celdaConteo = $incluirConteo
                            ? "<td style='" . ($esLoteExtra ? $tdLote : $tdC) . "'>&nbsp;</td>
                           <td style='" . ($esLoteExtra ? $tdLote : $tdC) . "'>&nbsp;</td>"
                            : "";

                        $tabla .= "
    <tr>
        <td style='{$bgCodigo}'>" . (!$esLoteExtra ? e($grupo['codigo']) : '') . "</td>
        {$celdaNombre}
        <td style='" . ($esLoteExtra ? $tdLoteR : $tdR) . "'>
            $ " . number_format($precio, 2, '.', ',') . "
        </td>
        <td style='" . ($esLoteExtra ? $tdLote : $tdC) . " font-weight:bold;'>
            " . number_format($stock, 0, '.', ',') . "
        </td>
        <td style='" . ($esLoteExtra ? $tdLoteR : $tdR) . " font-weight:bold;'>
            $ " . number_format($totalLote, 2, '.', ',') . "
        </td>
        {$celdaConteo}
    </tr>";

                        $esElPrimero = false;
                    }

                    // ── Subtotal del material (solo si tiene más de 1 lote) ────────
                    if ($totalLotes > 1) {
                        $stockTotal = array_sum($lotes);
                        $valorTotal = array_sum(array_map(
                            fn($p, $s) => $p * $s,
                            array_keys($lotes),
                            array_values($lotes)
                        ));

                        $celdaConteoSub = $incluirConteo
                            ? "<td style='{$tdC} background:#eef2fb;'>&nbsp;</td>
                           <td style='{$tdC} background:#eef2fb;'>&nbsp;</td>"
                            : "";

                        $tabla .= "
    <tr>
        <td colspan='4' style='{$tdStyle} text-align:right; font-style:italic;
                                background:#eef2fb; font-size:10px;'>
            Subtotal: " . e($mat['nombre']) . "
        </td>
        <td style='{$tdC} font-weight:bold; background:#eef2fb;'>
            " . number_format($stockTotal, 0, '.', ',') . "
        </td>
        <td style='{$tdR} font-weight:bold; background:#eef2fb;'>
            $ " . number_format($valorTotal, 2, '.', ',') . "
        </td>
        {$celdaConteoSub}
    </tr>";
                    }
                }
            }
        }

        $tabla .= "
</tbody>
</table>";

        // ── Tabla resumen valorizado ──────────────────────────────────────────────
        if (!empty($resumenPorCodigo)) {
            $tabla .= "
<br>
<table width='45%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-left:auto;'>
<thead>
    <tr>
        <th colspan='2' style='{$thStyle} font-size:12px; background:#104e8c; color:#fff;'>
            RESUMEN VALORIZADO POR OBJETO ESPECÍFICO
        </th>
    </tr>
    <tr>
        <th style='{$thStyle} width:70%;'>Objeto Específico</th>
        <th style='{$thStyle} width:30%;'>Total Valorizado</th>
    </tr>
</thead>
<tbody>";

            foreach ($resumenPorCodigo as $codigo => $subtotal) {
                $tabla .= "
    <tr>
        <td style='{$tdStyle}'>" . e($codigo) . "</td>
        <td style='{$tdR} font-weight:bold;'>$ " . number_format($subtotal, 2, '.', ',') . "</td>
    </tr>";
            }

            $tabla .= "
    <tr>
        <td style='{$tdStyle} text-align:right; font-weight:bold;
                    background:#d9e1f2; font-size:12px;'>
            GRAN TOTAL:
        </td>
        <td style='{$tdR} font-weight:bold; background:#d9e1f2; font-size:12px;'>
            $ " . number_format($granTotal, 2, '.', ',') . "
        </td>
    </tr>
</tbody>
</table>";
        }

        // ── Kits pendientes / abiertos (solo si el toggle está activo) ─────────────
        if ($incluirPendientes && $arrayPendientes->isNotEmpty()) {

            $tabla .= "
<br>
<div style='text-align:left; margin-top:10px;'>
    <h1 style='font-size:13px; margin:0; color:#000;'>KITS PENDIENTES / ABIERTOS</h1>
</div>
<table width='100%' style='border-collapse:collapse; margin-top:8px;'>
<thead>
    <tr>
        <th style='{$thStyle} width:28%;'>Producto</th>
        <th style='{$thStyle} width:12%;'>Cant. Salida</th>
        <th style='{$thStyle} width:25%;'>Descripción Salida</th>
        <th style='{$thStyle} width:35%;'>Detalle de entregas</th>
    </tr>
</thead>
<tbody>";

            foreach ($arrayPendientes as $pend) {

                if ($pend->entregas->isEmpty()) {
                    $detalleEntregas = "<span style='font-style:italic; color:#888;'>Sin entregas registradas</span>";
                } else {
                    $lineas = [];
                    foreach ($pend->entregas as $ent) {
                        $obs = $ent->observacion ?: '—';
                        $lineas[] = "{$ent->cantidad} {$pend->unidadMedida} — {$obs}";
                    }
                    $detalleEntregas = implode('<br>', $lineas);
                }

                $descripcionSalida = !empty($pend->descripcion) ? $pend->descripcion : '—';

                $tabla .= "
    <tr>
        <td style='{$tdStyle} text-align:left; vertical-align:top;'>{$pend->nombreMaterial}</td>
        <td style='{$tdC} vertical-align:top;'>{$pend->cantidad_salida} {$pend->unidadMedida}</td>
        <td style='{$tdStyle} text-align:left; vertical-align:top;'>{$descripcionSalida}</td>
        <td style='{$tdStyle} text-align:left;'>{$detalleEntregas}</td>
    </tr>";
            }

            $tabla .= "
</tbody>
</table>";
        }

        // ── Render ────────────────────────────────────────────────────────────────
        $stylesheet = file_get_contents('css/cssbodega.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->setFooter("Página: " . '{PAGENO}' . "/" . '{nb}');
        $mpdf->WriteHTML($tabla, 2);
        $mpdf->Output();
    }






    public function reportePDFInicialPorPeriodos($desde, $hasta)
    {
        $start = Carbon::parse($desde)->startOfDay();
        $end   = Carbon::parse($hasta)->endOfDay();

        $desdeFormat = Carbon::parse($desde)->format('d/m/Y');
        $hastaFormat = Carbon::parse($hasta)->format('d/m/Y');

        $rows = DB::select("
        WITH movimientos AS (

            SELECT
                ed.id_material,
                COALESCE(NULLIF(oe.codigo, ''), 'SIN-CODIGO') AS codigo,
                m.nombre AS descripcion,
                ed.precio,
                e.fecha AS fecha_movimiento,
                ed.cantidad_inicial AS entrada,
                0 AS salida,
                (ed.cantidad_inicial * ed.precio) AS monto_entrada,
                0 AS monto_salida
            FROM entradas_detalle ed
            INNER JOIN entradas e ON e.id = ed.id_entradas
            INNER JOIN materiales m ON m.id = ed.id_material
            LEFT JOIN objeto_especifico oe ON oe.id = m.id_objespecifico

            UNION ALL

            SELECT
                ed.id_material,
                COALESCE(NULLIF(oe.codigo, ''), 'SIN-CODIGO') AS codigo,
                m.nombre AS descripcion,
                ed.precio,
                COALESCE(
                    STR_TO_DATE(sd.fecha, '%Y-%m-%d %H:%i:%s'),
                    STR_TO_DATE(sd.fecha, '%Y-%m-%d'),
                    STR_TO_DATE(sd.fecha, '%d/%m/%Y')
                ) AS fecha_movimiento,
                0 AS entrada,
                sd.cantidad_salida AS salida,
                0 AS monto_entrada,
                (sd.cantidad_salida * ed.precio) AS monto_salida
            FROM salidas_detalle sd
            INNER JOIN entradas_detalle ed ON ed.id = sd.id_entrada_detalle
            INNER JOIN materiales m ON m.id = ed.id_material
            LEFT JOIN objeto_especifico oe ON oe.id = m.id_objespecifico
        )

        SELECT
            id_material,
            codigo,
            descripcion,
            MAX(precio) AS precio,

            SUM(CASE WHEN fecha_movimiento < ? THEN entrada - salida ELSE 0 END) AS saldo_inicial_cant,

            SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN entrada ELSE 0 END) AS entradas_mes_cant,

            SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN salida ELSE 0 END) AS salidas_mes_cant,

            (
                SUM(CASE WHEN fecha_movimiento < ? THEN entrada - salida ELSE 0 END)
                + SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN entrada ELSE 0 END)
                - SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN salida ELSE 0 END)
            ) AS saldo_final_cant,

            SUM(CASE WHEN fecha_movimiento < ? THEN monto_entrada - monto_salida ELSE 0 END) AS saldo_inicial_money,

            SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN monto_entrada ELSE 0 END) AS entradas_mes_money,

            SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN monto_salida ELSE 0 END) AS salidas_mes_money,

            (
                SUM(CASE WHEN fecha_movimiento < ? THEN monto_entrada - monto_salida ELSE 0 END)
                + SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN monto_entrada ELSE 0 END)
                - SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN monto_salida ELSE 0 END)
            ) AS saldo_final_money

        FROM movimientos
        GROUP BY id_material, codigo, descripcion
        ORDER BY codigo, descripcion
    ", [
            // saldo_inicial_cant
            $start->toDateString(),

            // entradas_mes_cant
            $start->toDateString(), $end->toDateString(),

            // salidas_mes_cant
            $start->toDateString(), $end->toDateString(),

            // saldo_final_cant
            $start->toDateString(),
            $start->toDateString(), $end->toDateString(),
            $start->toDateString(), $end->toDateString(),

            // saldo_inicial_money
            $start->toDateString(),

            // entradas_mes_money
            $start->toDateString(), $end->toDateString(),

            // salidas_mes_money
            $start->toDateString(), $end->toDateString(),

            // saldo_final_money
            $start->toDateString(),
            $start->toDateString(), $end->toDateString(),
            $start->toDateString(), $end->toDateString(),
        ]);

        $rows = array_values(array_filter($rows, function ($r) {
            $inicial  = (float) ($r->saldo_inicial_cant ?? 0);
            $entradas = (float) ($r->entradas_mes_cant ?? 0);
            $salidas  = (float) ($r->salidas_mes_cant ?? 0);
            $final    = (float) ($r->saldo_final_cant ?? 0);

            return !($inicial == 0 && $entradas == 0 && $salidas == 0 && $final == 0);
        }));

        $totales = [
            'inicial_cant'   => 0,
            'entradas_cant'  => 0,
            'salidas_cant'   => 0,
            'final_cant'     => 0,
            'inicial_money'  => 0.0,
            'entradas_money' => 0.0,
            'salidas_money'  => 0.0,
            'final_money'    => 0.0,
        ];

        $sumPorCodigo = [];

        foreach ($rows as $r) {
            $totales['inicial_cant']   += (int)   ($r->saldo_inicial_cant  ?? 0);
            $totales['entradas_cant']  += (int)   ($r->entradas_mes_cant   ?? 0);
            $totales['salidas_cant']   += (int)   ($r->salidas_mes_cant    ?? 0);
            $totales['final_cant']     += (int)   ($r->saldo_final_cant    ?? 0);
            $totales['inicial_money']  += (float) ($r->saldo_inicial_money ?? 0);
            $totales['entradas_money'] += (float) ($r->entradas_mes_money  ?? 0);
            $totales['salidas_money']  += (float) ($r->salidas_mes_money   ?? 0);
            $totales['final_money']    += (float) ($r->saldo_final_money   ?? 0);

            $codigo = $r->codigo ?? 'SIN-CODIGO';

            if (!isset($sumPorCodigo[$codigo])) {
                $sumPorCodigo[$codigo] = [
                    'codigo'         => $codigo,
                    'inicial_cant'   => 0,
                    'entradas_cant'  => 0,
                    'salidas_cant'   => 0,
                    'final_cant'     => 0,
                    'inicial_money'  => 0.0,
                    'entradas_money' => 0.0,
                    'salidas_money'  => 0.0,
                    'final_money'    => 0.0,
                ];
            }

            $sumPorCodigo[$codigo]['inicial_cant']   += (int)   ($r->saldo_inicial_cant  ?? 0);
            $sumPorCodigo[$codigo]['entradas_cant']  += (int)   ($r->entradas_mes_cant   ?? 0);
            $sumPorCodigo[$codigo]['salidas_cant']   += (int)   ($r->salidas_mes_cant    ?? 0);
            $sumPorCodigo[$codigo]['final_cant']     += (int)   ($r->saldo_final_cant    ?? 0);
            $sumPorCodigo[$codigo]['inicial_money']  += (float) ($r->saldo_inicial_money ?? 0);
            $sumPorCodigo[$codigo]['entradas_money'] += (float) ($r->entradas_mes_money  ?? 0);
            $sumPorCodigo[$codigo]['salidas_money']  += (float) ($r->salidas_mes_money   ?? 0);
            $sumPorCodigo[$codigo]['final_money']    += (float) ($r->saldo_final_money   ?? 0);
        }

        // ── mPDF ──────────────────────────────────────────────────────────────
        $mpdf = new \Mpdf\Mpdf([
            'tempDir'     => sys_get_temp_dir(),
            'format'      => 'LETTER',
            'orientation' => 'L',
        ]);

        $mpdf->SetTitle('Reporte Mensual de Inventario');
        $mpdf->showImageErrors = false;

        $logoalcaldia = 'images/gobiernologo.jpg';

        $encabezado = "
<table width='100%' style='border-collapse:collapse; font-family: Arial, sans-serif;'>
    <tr>
        <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
            <table width='100%'>
                <tr>
                    <td style='width:30%; text-align:left;'>
                        <img src='{$logoalcaldia}' style='height:38px'>
                    </td>
                    <td style='width:70%; text-align:left; color:#104e8c; font-size:13px; font-weight:bold; line-height:1.3;'>
                        REPORTE DE INVENTARIO
                    </td>
                </tr>
            </table>
        </td>
        <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000; padding:6px 8px; text-align:center; font-size:15px; font-weight:bold;'>
            CONTROL DE ENTRADAS / SALIDAS
        </td>
        <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
            <table width='100%' style='font-size:10px;'>
                <tr>
                    <td width='40%' style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Código:</strong></td>
                    <td width='60%' style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'></td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Versión:</strong></td>
                    <td style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'>000</td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'><strong>Fecha de vigencia:</strong></td>
                    <td style='padding:4px 6px; text-align:center;'></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>
";

        $encabezado .= "<span style='font-weight:bold;'>Del {$desdeFormat} al {$hastaFormat}</span><br>";

        if (file_exists(public_path('css/cssbodega.css'))) {
            $stylesheet = file_get_contents(public_path('css/cssbodega.css'));
            $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
        }

        // ── Tabla principal ───────────────────────────────────────────────────
        $html = $encabezado;

        $html .= "
<table width='100%' border='1' cellspacing='0' cellpadding='4' style='border-collapse:collapse; font-size:11px; margin-top:8px'>
    <thead style='background:#f2f4f8'>
        <tr>
            <th>#</th>
            <th>Código</th>
            <th>Descripción / Nombre</th>
            <th style='text-align:right; width:8%'>PRECIO</th>
            <th style='text-align:right; width:6%'>INICIAL</th>
            <th style='text-align:right; width:7%'>$ INICIAL</th>
            <th style='text-align:right; width:8%'>ENTRADAS</th>
            <th style='text-align:right; width:9%'>$ ENTRADAS</th>
            <th style='text-align:right; width:8%'>SALIDAS</th>
            <th style='text-align:right; width:8%'>$ SALIDAS</th>
            <th style='text-align:right; width:6%'>SALDO</th>
            <th style='text-align:right; width:7%'>$ SALDO</th>
        </tr>
    </thead>
    <tbody>
";

        $i = 1;
        foreach ($rows as $r) {
            $html .= "
    <tr>
        <td>{$i}</td>
        <td>" . e($r->codigo ?? '') . "</td>
        <td>" . e($r->descripcion ?? '') . "</td>
        <td style='text-align:right'>$" . number_format($r->precio ?? 0, 4) . "</td>
        <td style='text-align:right'>" . number_format($r->saldo_inicial_cant ?? 0) . "</td>
        <td style='text-align:right'>$" . number_format($r->saldo_inicial_money ?? 0, 2) . "</td>
        <td style='text-align:right'>" . number_format($r->entradas_mes_cant ?? 0) . "</td>
        <td style='text-align:right'>$" . number_format($r->entradas_mes_money ?? 0, 2) . "</td>
        <td style='text-align:right'>" . number_format($r->salidas_mes_cant ?? 0) . "</td>
        <td style='text-align:right'>$" . number_format($r->salidas_mes_money ?? 0, 2) . "</td>
        <td style='text-align:right'>" . number_format($r->saldo_final_cant ?? 0) . "</td>
        <td style='text-align:right'>$" . number_format($r->saldo_final_money ?? 0, 2) . "</td>
    </tr>
";
            $i++;
        }

        if (!$rows) {
            $html .= "<tr><td colspan='12' style='text-align:center; color:#888;'>Sin registros en el rango seleccionado.</td></tr>";
        }

        $html .= "
    </tbody>
    <tfoot>
        <tr style='font-weight:bold; background:#f9fafb'>
            <td colspan='4' style='text-align:right'>Totales:</td>
            <td style='text-align:right'>" . number_format($totales['inicial_cant']) . "</td>
            <td style='text-align:right'>$" . number_format($totales['inicial_money'], 2) . "</td>
            <td style='text-align:right'>" . number_format($totales['entradas_cant']) . "</td>
            <td style='text-align:right'>$" . number_format($totales['entradas_money'], 2) . "</td>
            <td style='text-align:right'>" . number_format($totales['salidas_cant']) . "</td>
            <td style='text-align:right'>$" . number_format($totales['salidas_money'], 2) . "</td>
            <td style='text-align:right'>" . number_format($totales['final_cant']) . "</td>
            <td style='text-align:right'>$" . number_format($totales['final_money'], 2) . "</td>
        </tr>
    </tfoot>
</table>
";

        // ── Resumen del período ───────────────────────────────────────────────
        $html .= "
<br>
<table width='60%' border='1' cellspacing='0' cellpadding='6' style='border-collapse:collapse; font-size:12px'>
    <tr style='background:#eef3ff; font-weight:bold; text-align:center'>
        <td colspan='3'>Resumen del período {$desdeFormat} - {$hastaFormat}</td>
    </tr>
    <tr style='font-weight:bold; background:#f9fafb'>
        <td></td>
        <td style='text-align:right'>Cantidad</td>
        <td style='text-align:right'>Dinero ($)</td>
    </tr>
    <tr>
        <td>Ingresó (Entradas del mes)</td>
        <td style='text-align:right'>" . number_format($totales['entradas_cant']) . "</td>
        <td style='text-align:right'>$" . number_format($totales['entradas_money'], 2) . "</td>
    </tr>
    <tr>
        <td>Salió (Salidas del mes)</td>
        <td style='text-align:right'>" . number_format($totales['salidas_cant']) . "</td>
        <td style='text-align:right'>$" . number_format($totales['salidas_money'], 2) . "</td>
    </tr>
    <tr>
        <td>Disponible al cierre (Saldo final)</td>
        <td style='text-align:right'>" . number_format($totales['final_cant']) . "</td>
        <td style='text-align:right'>$" . number_format($totales['final_money'], 2) . "</td>
    </tr>
</table>
";

        // ── Tabla resumen por código ──────────────────────────────────────────
        if (!empty($sumPorCodigo)) {
            $totalSaldoFinalCodigos = 0;

            $html .= "
<br><br>
<table width='100%' border='1' cellspacing='0' cellpadding='4' style='border-collapse:collapse; font-size:11px'>
    <thead style='background:#f2f4f8'>
        <tr>
            <th style='width:4%'>#</th>
            <th style='width:10%'>Código</th>
            <th style='text-align:right; width:6%'>INICIAL</th>
            <th style='text-align:right; width:10%'>$ INICIAL</th>
            <th style='text-align:right; width:6%'>ENTRADAS</th>
            <th style='text-align:right; width:10%'>$ ENTRADAS</th>
            <th style='text-align:right; width:6%'>SALIDAS</th>
            <th style='text-align:right; width:10%'>$ SALIDAS</th>
            <th style='text-align:right; width:6%'>SALDO</th>
            <th style='text-align:right; width:10%'>$ SALDO</th>
        </tr>
    </thead>
    <tbody>
";

            $j = 1;
            foreach ($sumPorCodigo as $s) {
                $totalSaldoFinalCodigos += (float) $s['final_money'];

                $html .= "
        <tr>
            <td>{$j}</td>
            <td>" . e($s['codigo']) . "</td>
            <td style='text-align:right'>" . number_format($s['inicial_cant']) . "</td>
            <td style='text-align:right'>$" . number_format($s['inicial_money'], 2) . "</td>
            <td style='text-align:right'>" . number_format($s['entradas_cant']) . "</td>
            <td style='text-align:right'>$" . number_format($s['entradas_money'], 2) . "</td>
            <td style='text-align:right'>" . number_format($s['salidas_cant']) . "</td>
            <td style='text-align:right'>$" . number_format($s['salidas_money'], 2) . "</td>
            <td style='text-align:right'>" . number_format($s['final_cant']) . "</td>
            <td style='text-align:right'>$" . number_format($s['final_money'], 2) . "</td>
        </tr>
";
                $j++;
            }

            $html .= "
        <tr style='font-weight:bold; background:#f9fafb'>
            <td colspan='9' style='text-align:right'>TOTAL</td>
            <td style='text-align:right'>$" . number_format($totalSaldoFinalCodigos, 2) . "</td>
        </tr>
    </tbody>
</table>
";
        }

        // ── Firma ─────────────────────────────────────────────────────────────
        $informacionGeneral = InformacionGeneral::where('id', 1)->first();
        $margenFirma = $informacionGeneral->px_firmas ?? '40px';

        $html .= "
<div style='text-align:center; font-size:13px; margin-top:{$margenFirma};'>
    F._____________________________<br>
    <span style='font-weight:bold; font-size:14px;'>Unidad de Tecnologías de la Información</span>
</div>
";

        $mpdf->setFooter('Página {PAGENO} de {nb}');
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output();
    }






    //********** END REPORTE POR FECHAS *********************









    public function reportePDFEntregadoAunidades($idDep, $desde = '0', $hasta = '0')
    {
        // ── 1. Validar departamento ───────────────────────────────────────────
        $departamento = Departamentos::findOrFail($idDep);

        // ── 2. Query base: salidas_detalle + entregas para ese departamento ───
        //   Consideramos entregas en salidas_detalle_entregas ligadas al depto,
        //   Y también salidas_detalle directamente asignadas al depto.

        // Traemos salidas_detalle del departamento (asignación directa)
        $querySD = SalidasDetalle::with([
            'entradaDetalle.material.unidadMedida',
            'entradaDetalle.material.objetoEspecifico',
            'entradaDetalle.entrada',
            'entregas' => function ($q) use ($idDep, $desde, $hasta) {
                $q->where(function($sub) use ($idDep) {
                    $sub->where('id_departamento', $idDep)
                        ->orWhereNull('id_departamento');
                });
                if ($desde !== '0' && $hasta !== '0') {
                    $q->whereBetween('fecha_entrega', [$desde, $hasta]);
                } elseif ($desde !== '0') {
                    $q->where('fecha_entrega', '>=', $desde);
                } elseif ($hasta !== '0') {
                    $q->where('fecha_entrega', '<=', $hasta);
                }
            }
        ])
            ->where('id_departamento', $idDep);

        // Filtro de fecha sobre la salida si no hay fechas en entregas
        if ($desde !== '0' && $hasta !== '0') {
            $querySD->whereBetween('fecha', [$desde, $hasta]);
        } elseif ($desde !== '0') {
            $querySD->where('fecha', '>=', $desde);
        } elseif ($hasta !== '0') {
            $querySD->where('fecha', '<=', $hasta);
        }

        $salidasDetalle = $querySD->orderBy('fecha', 'asc')->get();

        // ── 3. También incluir entregas directas en salidas_detalle_entregas ─
        //    (salidas que no están directamente en el depto pero sí las entregas)
        $queryEnt = SalidasDetalleEntregas::with([
            'salidaDetalle.entradaDetalle.material.unidadMedida',
            'salidaDetalle.entradaDetalle.material.objetoEspecifico',
            'salidaDetalle.entradaDetalle.entrada',
        ])
            ->where('id_departamento', $idDep);

        if ($desde !== '0' && $hasta !== '0') {
            $queryEnt->whereBetween('fecha_entrega', [$desde, $hasta]);
        } elseif ($desde !== '0') {
            $queryEnt->where('fecha_entrega', '>=', $desde);
        } elseif ($hasta !== '0') {
            $queryEnt->where('fecha_entrega', '<=', $hasta);
        }

        $entregasDirectas = $queryEnt->orderBy('fecha_entrega', 'asc')->get();

        // ── 4. Consolidar en una colección unificada ──────────────────────────
        $filas = collect();

        // 4a. Desde salidas_detalle asignadas directamente al depto
        foreach ($salidasDetalle as $sd) {
            $mat = $sd->entradaDetalle->material ?? null;
            if (!$mat) continue;

            $cantidadEntregada = $sd->entregas->sum('cantidad');
            // Si no hay entregas registradas, la salida completa se considera entregada
            if ($cantidadEntregada == 0) {
                $cantidadEntregada = $sd->cantidad_salida;
            }

            $detalleEntregas = $sd->entregas->map(function ($e) use ($mat) {
                return [
                    'fecha'      => date('d-m-Y', strtotime($e->fecha_entrega)),
                    'cantidad'   => $e->cantidad,
                    'um'         => $mat->unidadMedida->nombre ?? '',
                    'observacion'=> $e->observacion ?: '—',
                ];
            })->toArray();

            $filas->push((object)[
                'fecha_raw'       => $sd->fecha,                                    // ← NUEVO
                'fecha'           => date('d-m-Y', strtotime($sd->fecha)),
                'nombreMaterial'  => $mat->nombre ?? '',
                'unidadMedida'    => $mat->unidadMedida->nombre ?? '',
                'cantidad'        => $cantidadEntregada,
                'descripcion'     => $sd->descripcion ?: '—',
                'numero_solicitud'=> $sd->numero_solicitud ?: '—',
                'lote'            => $sd->entradaDetalle->entrada->lote ?? '—',
                'detalleEntregas' => $detalleEntregas,
                'fuente'          => 'salida',
            ]);
        }

        // 4b. Desde entregas directas en salidas_detalle_entregas
        //     Evitamos duplicar los que ya están en salidas directas del depto
        $idsYaCargados = $salidasDetalle->pluck('id')->toArray();

        foreach ($entregasDirectas as $ent) {
            $sd  = $ent->salidaDetalle;
            $mat = $sd->entradaDetalle->material ?? null;
            if (!$mat) continue;

            // Si la salida ya fue cargada arriba, skip
            if (in_array($sd->id, $idsYaCargados)) continue;

            $filas->push((object)[
                'fecha_raw'       => $ent->fecha_entrega,                           // ← NUEVO
                'fecha'           => date('d-m-Y', strtotime($ent->fecha_entrega)),
                'nombreMaterial'  => $mat->nombre ?? '',
                'unidadMedida'    => $mat->unidadMedida->nombre ?? '',
                'cantidad'        => $ent->cantidad,
                'descripcion'     => $sd->descripcion ?: '—',
                'numero_solicitud'=> $sd->numero_solicitud ?: '—',
                'lote'            => $sd->entradaDetalle->entrada->lote ?? '—',
                'detalleEntregas' => [[
                    'fecha'      => date('d-m-Y', strtotime($ent->fecha_entrega)),
                    'cantidad'   => $ent->cantidad,
                    'um'         => $mat->unidadMedida->nombre ?? '',
                    'observacion'=> $ent->observacion ?: '—',
                ]],
                'fuente'          => 'entrega',
            ]);
        }

        $filas = $filas->sortBy('fecha_raw')->values();

        $totalUnidades = $filas->sum('cantidad');

        // ── 5. Fechas y encabezado ────────────────────────────────────────────
        $fechaHoy    = date('d-m-Y', strtotime(Carbon::now('America/El_Salvador')));
        $rangoTexto  = ($desde !== '0' && $hasta !== '0')
            ? 'Del ' . date('d-m-Y', strtotime($desde)) . ' al ' . date('d-m-Y', strtotime($hasta))
            : 'Todo el historial';

        // ── 6. Generar PDF ────────────────────────────────────────────────────
        $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER']);
        $mpdf->SetTitle('Entregado a ' . $departamento->nombre);
        $mpdf->showImageErrors = false;

        $logoalcaldia = 'images/gobiernologo.jpg';
        $logosantaana = 'images/logo.png';

        // ── Encabezado ────────────────────────────────────────────────────────
        $html = "
    <table style='width:100%; border-collapse:collapse;'>
        <tr>
            <td style='width:15%; text-align:left;'>
                <img src='$logosantaana' alt='Santa Ana Norte' style='max-width:100px; height:auto;'>
            </td>
            <td style='width:70%; text-align:center;'>
                <h1 style='font-size:16px; margin:0; color:#003366; text-transform:uppercase;'>
                    ALCALDÍA MUNICIPAL DE SANTA ANA NORTE
                </h1>
            </td>
            <td style='width:15%; text-align:right;'>

            </td>
        </tr>
    </table>
    <hr style='border:none; border-top:2px solid #003366; margin:0;'>

    <div style='text-align:center; margin-top:16px;'>
        <h1 style='font-size:15px; margin:0; color:#000; text-transform:uppercase;'>
            MATERIALES ENTREGADOS A: {$departamento->nombre}
        </h1>
        <p style='font-size:12px; margin:4px 0 0 0; color:#444;'>
            {$rangoTexto} &nbsp;|&nbsp; Impreso: {$fechaHoy}
        </p>
    </div>
    ";

        // ── Tabla de entregas ─────────────────────────────────────────────────
        if ($filas->isEmpty()) {
            $html .= "
        <p style='margin-top:30px; text-align:center; font-size:13px; color:#666;'>
            No se encontraron entregas para esta unidad en el período seleccionado.
        </p>";
        } else {
            $html .= "
        <table style='width:100%; border-collapse:collapse; margin-top:20px;'>
            <thead>
                <tr>
                    <th style='border:1px solid #000; font-size:13px; padding:4px; text-align:center; width:10%;'>Fecha</th>
                    <th style='border:1px solid #000; font-size:13px; padding:4px; text-align:center; width:28%;'>Producto</th>
                    <th style='border:1px solid #000; font-size:13px; padding:4px; text-align:center; width:7%;'>U.M</th>
                    <th style='border:1px solid #000; font-size:13px; padding:4px; text-align:center; width:8%;'>Cantidad</th>
                    <th style='border:1px solid #000; font-size:13px; padding:4px; text-align:center; width:12%;'>No. Solicitud</th>
                    <th style='border:1px solid #000; font-size:13px; padding:4px; text-align:center; width:25%;'>Descripción / Observación</th>
                </tr>
            </thead>
            <tbody>
        ";

            foreach ($filas as $fila) {
                // Construir celda de descripción con detalle de entregas si hay varios
                $descCell = htmlspecialchars($fila->descripcion);
                if (count($fila->detalleEntregas) > 1) {
                    $descCell .= "<br><small style='color:#555;'>";
                    foreach ($fila->detalleEntregas as $de) {
                        $descCell .= "• {$de['fecha']}: {$de['cantidad']} {$de['um']} — {$de['observacion']}<br>";
                    }
                    $descCell .= "</small>";
                } elseif (count($fila->detalleEntregas) === 1) {
                    $de = $fila->detalleEntregas[0];
                    if ($de['observacion'] !== '—') {
                        $descCell .= "<br><small style='color:#555;'>Obs: {$de['observacion']}</small>";
                    }
                }

                $html .= "
            <tr>
                <td style='border:1px solid #000; font-size:12px; padding:3px; text-align:center; vertical-align:top;'>
                    {$fila->fecha}
                </td>
                <td style='border:1px solid #000; font-size:12px; padding:3px; text-align:left; vertical-align:top;'>
                    {$fila->nombreMaterial}
                </td>
                <td style='border:1px solid #000; font-size:12px; padding:3px; text-align:center; vertical-align:top;'>
                    {$fila->unidadMedida}
                </td>
                <td style='border:1px solid #000; font-size:12px; padding:3px; text-align:center; vertical-align:top;'>
                    {$fila->cantidad}
                </td>
                <td style='border:1px solid #000; font-size:12px; padding:3px; text-align:center; vertical-align:top;'>
                    {$fila->numero_solicitud}
                </td>

                <td style='border:1px solid #000; font-size:12px; padding:3px; text-align:left; vertical-align:top;'>
                    {$descCell}
                </td>
            </tr>";
            }



            $html .= "</tbody></table>";
        }

        // ── Escribir y Output ─────────────────────────────────────────────────
        $stylesheet = file_get_contents('css/cssbodega.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->setFooter('Página: {PAGENO}/{nb}');
        $mpdf->WriteHTML($html, 2);
        $mpdf->Output();
    }




    public function reportePDFEntregadoPorMaterial($idMat, $desde = '0', $hasta = '0')
    {
        // ── 1. Material ───────────────────────────────────────────────────────
        $material = Materiales::with('unidadMedida')->findOrFail($idMat);

        // ── 2. Todos los entradas_detalle de ese material ─────────────────────
        $entradasDetalle = EntradasDetalle::where('id_material', $idMat)->pluck('id');

        // ── 3. Salidas filtradas por fecha ────────────────────────────────────
        $query = SalidasDetalle::with([
            'departamento',
            'tipoSalida',
            'entradaDetalle.entrada',
        ])
            ->whereIn('id_entrada_detalle', $entradasDetalle);

        if ($desde !== '0' && $hasta !== '0') {
            $query->whereBetween('fecha', [$desde, $hasta]);
        } elseif ($desde !== '0') {
            $query->where('fecha', '>=', $desde);
        } elseif ($hasta !== '0') {
            $query->where('fecha', '<=', $hasta);
        }

        $salidas = $query->orderBy('fecha', 'asc')->get();

        // ── 4. Consolidar filas ───────────────────────────────────────────────
        $filas = collect();

        foreach ($salidas as $sd) {
            $filas->push((object)[
                'fecha_raw'        => $sd->fecha,
                'fecha'            => date('d-m-Y', strtotime($sd->fecha)),
                'departamento'     => $sd->departamento->nombre ?? 'Sin unidad',
                'tipo'             => $sd->tipoSalida->nombre ?? '—',
                'cantidad'         => $sd->cantidad_salida,
                'numero_solicitud' => $sd->numero_solicitud ?: '—',
                'descripcion'      => $sd->descripcion ?: '—',
            ]);
        }

        $filas = $filas->sortBy('fecha_raw')->values();

        $totalEntregado = $filas->sum('cantidad');

        // ── 5. Textos del encabezado ──────────────────────────────────────────
        $fechaHoy   = date('d-m-Y', strtotime(Carbon::now('America/El_Salvador')));
        $rangoTexto = ($desde !== '0' && $hasta !== '0')
            ? 'Del ' . date('d-m-Y', strtotime($desde)) . ' al ' . date('d-m-Y', strtotime($hasta))
            : 'Todo el historial';

        // ── 6. PDF ────────────────────────────────────────────────────────────
        $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER']);
        $mpdf->SetTitle('Entregas de ' . $material->nombre);
        $mpdf->showImageErrors = false;

        $logosantaana = 'images/logo.png';
        $logoalcaldia = 'images/gobiernologo.jpg';

        $html = "
    <table style='width:100%; border-collapse:collapse;'>
        <tr>
            <td style='width:15%; text-align:left;'>
                <img src='$logosantaana' style='max-width:100px; height:auto;'>
            </td>
            <td style='width:70%; text-align:center;'>
                <h1 style='font-size:16px; margin:0; color:#003366; text-transform:uppercase;'>
                    ALCALDÍA MUNICIPAL DE SANTA ANA NORTE
                </h1>
            </td>
            <td style='width:15%; text-align:right;'>
                <img src='$logoalcaldia' style='max-width:60px; height:auto;'>
            </td>
        </tr>
    </table>
    <hr style='border:none; border-top:2px solid #003366; margin:0;'>

    <div style='text-align:center; margin-top:14px;'>
        <h1 style='font-size:15px; margin:0; color:#000; text-transform:uppercase;'>
            HISTORIAL DE ENTREGAS — {$material->nombre}
        </h1>
        <p style='font-size:12px; margin:4px 0 2px 0; color:#444;'>
            {$rangoTexto} &nbsp;|&nbsp; Impreso: {$fechaHoy}
            &nbsp;|&nbsp; U.M: <strong>{$material->unidadMedida->nombre}</strong>
        </p>
    </div>
    ";

        if ($filas->isEmpty()) {
            $html .= "
        <p style='margin-top:30px; text-align:center; font-size:13px; color:#666;'>
            No se encontraron salidas para este material en el período seleccionado.
        </p>";
        } else {
            $html .= "
        <table style='width:100%; border-collapse:collapse; margin-top:18px;'>
            <thead>
                <tr>
                    <th style='border:1px solid #000; font-size:12px; padding:4px; text-align:center; width:10%;'>Fecha</th>
                    <th style='border:1px solid #000; font-size:12px; padding:4px; text-align:center; width:26%;'>Unidad / Depto.</th>
                    <th style='border:1px solid #000; font-size:12px; padding:4px; text-align:center; width:18%;'>Tipo de salida</th>
                    <th style='border:1px solid #000; font-size:12px; padding:4px; text-align:center; width:10%;'>Cantidad</th>
                    <th style='border:1px solid #000; font-size:12px; padding:4px; text-align:center; width:13%;'>No. Solicitud</th>
                    <th style='border:1px solid #000; font-size:12px; padding:4px; text-align:center; width:23%;'>Descripción</th>
                </tr>
            </thead>
            <tbody>
        ";

            foreach ($filas as $fila) {

                $html .= "
            <tr>
                <td style='border:1px solid #000; font-size:11px; padding:3px; text-align:center; vertical-align:top;'>
                    {$fila->fecha}
                </td>
                <td style='border:1px solid #000; font-size:11px; padding:3px; text-align:left; vertical-align:top;'>
                    {$fila->departamento}
                </td>
                <td style='border:1px solid #000; font-size:11px; padding:3px; text-align:center; vertical-align:top;'>
                    {$fila->tipo}
                </td>
                <td style='border:1px solid #000; font-size:11px; padding:3px; text-align:center; vertical-align:top;'>
                    {$fila->cantidad}
                </td>
                <td style='border:1px solid #000; font-size:11px; padding:3px; text-align:center; vertical-align:top;'>
                    {$fila->numero_solicitud}
                </td>
                <td style='border:1px solid #000; font-size:11px; padding:3px; text-align:left; vertical-align:top;'>
                    " . htmlspecialchars($fila->descripcion) . "
                </td>
            </tr>";
            }

            $html .= "
            <tr>
                <td colspan='3' style='border:1px solid #000; font-size:12px; padding:4px;
                    text-align:right; font-weight:bold;'>
                    Total entregado:
                </td>
                <td style='border:1px solid #000; font-size:12px; padding:4px;
                    text-align:center; font-weight:bold;'>
                    {$totalEntregado}
                </td>
                <td colspan='2' style='border:1px solid #000;'></td>
            </tr>
        </tbody></table>";
        }

        $stylesheet = file_get_contents('css/cssbodega.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->setFooter('Página: {PAGENO}/{nb}');
        $mpdf->WriteHTML($html, 2);
        $mpdf->Output();
    }



    public function reportePDFEntregasPendientesPorMaterial($idMat, $desde = '0', $hasta = '0')
    {
        // ── 1. Material ───────────────────────────────────────────────────────
        $material = Materiales::with('unidadMedida')->findOrFail($idMat);

        // ── 2. Todos los entradas_detalle de ese material ─────────────────────
        $entradasDetalle = EntradasDetalle::where('id_material', $idMat)->pluck('id');

        // ── 3. IDs de salidas_detalle de ese material ─────────────────────────
        $salidasDetalleIds = SalidasDetalle::whereIn('id_entrada_detalle', $entradasDetalle)
            ->pluck('id');

        // ── 4. Entregas registradas en salidas_detalle_entregas ───────────────
        $query = SalidasDetalleEntregas::with([
            'departamento',
            'salidaDetalle.departamento',
            'salidaDetalle.tipoSalida',
        ])
            ->whereIn('id_salida_detalle', $salidasDetalleIds);

        if ($desde !== '0' && $hasta !== '0') {
            $query->whereBetween('fecha_entrega', [$desde, $hasta]);
        } elseif ($desde !== '0') {
            $query->where('fecha_entrega', '>=', $desde);
        } elseif ($hasta !== '0') {
            $query->where('fecha_entrega', '<=', $hasta);
        }

        $entregas = $query->orderBy('fecha_entrega', 'asc')->get();

        // ── 5. Consolidar filas ───────────────────────────────────────────────
        $filas = collect();

        foreach ($entregas as $e) {
            $filas->push((object)[
                'fecha_raw'        => $e->fecha_entrega,
                'fecha'            => date('d-m-Y', strtotime($e->fecha_entrega)),
                'departamento'     => $e->departamento->nombre
                    ?? $e->salidaDetalle->departamento->nombre
                        ?? 'Sin unidad',
                'tipo'             => $e->salidaDetalle->tipoSalida->nombre ?? '—',
                'cantidad'         => $e->cantidad,
                'numero_solicitud' => $e->numero_solicitud ?: '—',
                'observacion'      => $e->observacion ?: '—',
            ]);
        }

        $totalEntregado = $filas->sum('cantidad');

        // ── 6. Textos del encabezado ──────────────────────────────────────────
        $fechaHoy   = date('d-m-Y', strtotime(Carbon::now('America/El_Salvador')));
        $rangoTexto = ($desde !== '0' && $hasta !== '0')
            ? 'Del ' . date('d-m-Y', strtotime($desde)) . ' al ' . date('d-m-Y', strtotime($hasta))
            : 'Todo el historial';

        // ── 7. PDF ────────────────────────────────────────────────────────────
        $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER']);
        $mpdf->SetTitle('Historial de Entregas — ' . $material->nombre);
        $mpdf->showImageErrors = false;

        $logosantaana = 'images/logo.png';
        $logoalcaldia = 'images/gobiernologo.jpg';

        $html = "
    <table style='width:100%; border-collapse:collapse;'>
        <tr>
            <td style='width:15%; text-align:left;'>
                <img src='$logosantaana' style='max-width:100px; height:auto;'>
            </td>
            <td style='width:70%; text-align:center;'>
                <h1 style='font-size:16px; margin:0; color:#003366; text-transform:uppercase;'>
                    ALCALDÍA MUNICIPAL DE SANTA ANA NORTE
                </h1>
            </td>
            <td style='width:15%; text-align:right;'>

            </td>
        </tr>
    </table>
    <hr style='border:none; border-top:2px solid #003366; margin:0;'>

    <div style='text-align:center; margin-top:14px;'>
        <h1 style='font-size:15px; margin:0; color:#1a5c4a; text-transform:uppercase;'>
            HISTORIAL DE ENTREGAS REGISTRADAS — {$material->nombre}
        </h1>
        <p style='font-size:12px; margin:4px 0 2px 0; color:#444;'>
            {$rangoTexto} &nbsp;|&nbsp; Impreso: {$fechaHoy}
            &nbsp;|&nbsp; U.M: <strong>{$material->unidadMedida->nombre}</strong>
        </p>
    </div>
    ";

        if ($filas->isEmpty()) {
            $html .= "
        <p style='margin-top:30px; text-align:center; font-size:13px; color:#666;'>
            No hay entregas registradas para este material en el período seleccionado.
        </p>";
        } else {
            $html .= "
        <table style='width:100%; border-collapse:collapse; margin-top:18px;'>
            <thead>
                <tr>
                    <th style='border:1px solid #000; font-size:11px; padding:4px; text-align:center; background:#d4edda; width:10%;'>Fecha Entrega</th>
                    <th style='border:1px solid #000; font-size:11px; padding:4px; text-align:center; background:#d4edda; width:26%;'>Unidad</th>
                    <th style='border:1px solid #000; font-size:11px; padding:4px; text-align:center; background:#d4edda; width:16%;'>Tipo de salida</th>
                    <th style='border:1px solid #000; font-size:11px; padding:4px; text-align:center; background:#d4edda; width:9%;'>Cantidad</th>
                    <th style='border:1px solid #000; font-size:11px; padding:4px; text-align:center; background:#d4edda; width:13%;'>No. Solicitud</th>
                    <th style='border:1px solid #000; font-size:11px; padding:4px; text-align:center; background:#d4edda; width:26%;'>Observación</th>
                </tr>
            </thead>
            <tbody>
        ";

            foreach ($filas as $fila) {
                $html .= "
            <tr>
                <td style='border:1px solid #000; font-size:11px; padding:3px; text-align:center; vertical-align:top;'>
                    {$fila->fecha}
                </td>
                <td style='border:1px solid #000; font-size:11px; padding:3px; text-align:left; vertical-align:top;'>
                    {$fila->departamento}
                </td>
                <td style='border:1px solid #000; font-size:11px; padding:3px; text-align:center; vertical-align:top;'>
                    {$fila->tipo}
                </td>
                <td style='border:1px solid #000; font-size:11px; padding:3px; text-align:center; vertical-align:top;
                    font-weight:bold;'>
                    {$fila->cantidad}
                </td>
                <td style='border:1px solid #000; font-size:11px; padding:3px; text-align:center; vertical-align:top;'>
                    {$fila->numero_solicitud}
                </td>
                <td style='border:1px solid #000; font-size:11px; padding:3px; text-align:left; vertical-align:top;'>
                    " . htmlspecialchars($fila->observacion) . "
                </td>
            </tr>";
            }

            $html .= "
            <tr>
                <td colspan='3' style='border:1px solid #000; font-size:12px; padding:4px;
                    text-align:right; font-weight:bold;'>
                    Total entregado:
                </td>
                <td style='border:1px solid #000; font-size:12px; padding:4px;
                    text-align:center; font-weight:bold;'>
                    {$totalEntregado}
                </td>
                <td colspan='2' style='border:1px solid #000;'></td>
            </tr>
        </tbody></table>";
        }

        $stylesheet = file_get_contents('css/cssbodega.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->setFooter('Página: {PAGENO}/{nb}');
        $mpdf->WriteHTML($html, 2);
        $mpdf->Output();
    }




}
