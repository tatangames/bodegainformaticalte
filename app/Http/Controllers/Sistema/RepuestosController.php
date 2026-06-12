<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use App\Models\Entradas;
use App\Models\EntradasDetalle;
use App\Models\Materiales;
use App\Models\ObjetoEspecifico;
use App\Models\Proveedor;
use App\Models\SalidasDetalle;
use App\Models\TipoCompra;
use App\Models\UnidadMedida;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RepuestosController extends Controller
{

    public function index()
    {
        $lUnidad           = UnidadMedida::orderBy('nombre', 'ASC')->get();
        $lObjetoEspecifico = ObjetoEspecifico::with('cuenta')
            ->orderBy('nombre', 'ASC')->get();

        return view('backend.admin.inventario.vistainventario',
            compact('lUnidad', 'lObjetoEspecifico'));
    }

    public function tablaMateriales()
    {
        $filtro = request('filtro', 'todos');

        $query = Materiales::with('objetoEspecifico')->orderBy('nombre', 'ASC');

        if ($filtro === 'sin_objeto') {
            $query->whereNull('id_objespecifico');
        }

        $lista = $query->get();

        // Una sola query para todas las unidades
        $unidades = UnidadMedida::pluck('nombre', 'id');

        foreach ($lista as $item) {
            $item->medida = $unidades[$item->id_medida] ?? '';

            $entradas = DB::table('entradas_detalle')
                ->where('id_material', $item->id)
                ->sum('cantidad_inicial');

            $salidas = DB::table('salidas_detalle as sd')
                ->join('entradas_detalle as ed', 'ed.id', '=', 'sd.id_entrada_detalle')
                ->where('ed.id_material', $item->id)
                ->sum('sd.cantidad_salida');

            $item->entradas = $entradas;
            $item->salidas  = $salidas;
            $item->total    = $entradas - $salidas;

            $item->objeto_especifico = $item->objetoEspecifico;
        }

        return view('backend.admin.inventario.tablainventario', compact('lista'));
    }

    public function nuevoMaterial(Request $request)
    {
        $validar = Validator::make($request->all(), [
            'nombre'           => 'required',
            'id_objespecifico' => 'required|exists:objeto_especifico,id',
        ]);
        if ($validar->fails()) { return ['success' => 0]; }

        $dato = new Materiales();
        $dato->nombre           = $request->nombre;
        $dato->id_medida        = $request->unidad ?: null;
        $dato->id_objespecifico = $request->id_objespecifico;
        $dato->codigo           = $request->codigo;

        return $dato->save() ? ['success' => 1] : ['success' => 2];
    }

    public function informacionMaterial(Request $request)
    {
        $validar = Validator::make($request->all(), ['id' => 'required']);
        if ($validar->fails()) { return ['success' => 0]; }

        if ($lista = Materiales::where('id', $request->id)->first()) {
            $arrayUnidad         = UnidadMedida::orderBy('nombre', 'ASC')->get();
            $arrayObjetoEspecifico = ObjetoEspecifico::with('cuenta.rubro')
                ->orderBy('nombre', 'ASC')->get();

            return [
                'success'           => 1,
                'material'          => $lista,
                'unidad'            => $arrayUnidad,
                'objeto_especifico' => $arrayObjetoEspecifico,
            ];
        }

        return ['success' => 2];
    }

    public function editarMaterial(Request $request)
    {
        $validar = Validator::make($request->all(), [
            'id'               => 'required',
            'nombre'           => 'required',
            'id_objespecifico' => 'required|exists:objeto_especifico,id',
        ]);
        if ($validar->fails()) { return ['success' => 0]; }

        Materiales::where('id', $request->id)->update([
            'id_medida'        => $request->unidad ?: null,
            'id_objespecifico' => $request->id_objespecifico,
            'nombre'           => $request->nombre,
            'codigo'           => $request->codigo,
        ]);

        return ['success' => 1];
    }



    //*******************************************************************

    public function indexRegistroEntrada()
    {
        $arrayTipoCompra  = TipoCompra::orderBy('nombre')->get();
        $arrayProveedor  = Proveedor::orderBy('nombre')->get();

        return view('backend.admin.repuestos.registros.vistaentradaregistro',
            compact( 'arrayTipoCompra', 'arrayProveedor'));
    }


    public function buscadorMaterial(Request $request){

        if($request->get('query')){
            $query = $request->get('query');
            $data = Materiales::where('nombre', 'LIKE', "%{$query}%")
                ->get();

            foreach ($data as $dd){
                if($info = UnidadMedida::where('id', $dd->id_medida)->first()){
                    $dd->medida = "- " . $info->nombre;
                }else{
                    $dd->medida = "";
                }
            }

            $output = '<ul class="dropdown-menu" style="display:block; position:relative;">';
            $tiene = true;
            foreach($data as $row){

                // si solo hay 1 fila, No mostrara el hr, salto de linea
                if(count($data) == 1){
                    if(!empty($row)){
                        $tiene = false;
                        $output .= '
                 <li onclick="modificarValor(this)" id="'.$row->id.'"><a href="#" style="margin-left: 3px">'.$row->nombre . '  ' .$row->medida .'</a></li>
                ';
                    }
                }

                else{
                    if(!empty($row)){
                        $tiene = false;
                        $output .= '
                 <li onclick="modificarValor(this)" id="'.$row->id.'"><a href="#" style="margin-left: 3px">'.$row->nombre . ' ' .$row->medida .'</a></li>
                   <hr>
                ';
                    }
                }
            }
            $output .= '</ul>';
            if($tiene){
                $output = '';
            }
            echo $output;
        }
    }

    // GUARDAR ENTRADAS
    public function guardarEntrada(Request $request)
    {
        Log::info($request->all());

        $validator = Validator::make($request->all(), [
            'fecha'      => 'required|date',
            'tipocompra' => 'required',
            'proveedor'  => 'required',
        ]);

        if ($validator->fails()) {
            return ['success' => 0];
        }

        DB::beginTransaction();

        try {
            $datosContenedor = json_decode($request->contenedorArray, true);

            if (empty($datosContenedor)) {
                return ['success' => 0];
            }

            $entrada = new Entradas();
            $entrada->id_tipocompra = $request->tipocompra;
            $entrada->id_proveedor = $request->proveedor;
            $entrada->fecha         = $request->fecha;
            $entrada->descripcion   = $request->descripcion;
            $entrada->lote          = $request->factura; // lote es el campo varchar(100)
            $entrada->save();

            foreach ($datosContenedor as $fila) {
                $detalle = new EntradasDetalle();
                $detalle->id_entradas      = $entrada->id;
                $detalle->id_material      = $fila['idMaterial'];
                $detalle->cantidad_inicial = $fila['infoCantidad'];
                $detalle->precio           = $fila['infoPrecio'];
                $detalle->codigo           = $fila['infoCodigo'];
                $detalle->nombre           = $fila['infoNombre'];
                $detalle->save();
            }

            DB::commit();
            return ['success' => 1];

        } catch (\Throwable $e) {
            DB::rollback();
            Log::error('guardarEntrada: ' . $e);
            return ['success' => 99];
        }
    }


    public function inventarioConteoDeMateriales(Request $request)
    {
        $idMaterial = $request->id;

        // IDs de entradas_detalle de este material
        $idsEntradasDetalle = EntradasDetalle::where('id_material', $idMaterial)
            ->pluck('id');

        // Total de unidades que entraron
        $totalEntradas = EntradasDetalle::where('id_material', $idMaterial)
            ->sum('cantidad_inicial');

        // Total de unidades que salieron
        $totalSalidas = SalidasDetalle::whereIn('id_entrada_detalle', $idsEntradasDetalle)
            ->sum('cantidad_salida');

        $totalDisponible = $totalEntradas - $totalSalidas;

        return response()->json([
            'success' => 1,
            'totales' => [
                'entradas'   => $totalEntradas,
                'salidas'    => $totalSalidas,
                'disponible' => $totalDisponible,
            ],
        ]);
    }





    //*******************************************

    public function vistaDetalleMaterial($id){

        $infomaterial = Materiales::where('id', $id)->first();
        $medida = '';
        if($infoMedida = UnidadMedida::where('id', $infomaterial->id_medida)->first()){
            $medida = $infoMedida->nombre;
        }

        return view('backend.admin.inventario.detalle.vistadetalle', compact('id', 'infomaterial', 'medida'));
    }


    public function tablaDetalleMaterial($id){

        // SOLO HABRA 1 MATERIAL POR CADA PROYECTO
        $arrayEntradas =  Entradas::where('id_material', $id)->get();

        $pilaArrayEntrada = array();


        foreach ($arrayEntradas as $data){

            // VERIFICAR QUE LA CANTIDAD SEA MAYOR A 0 PARA PODER
            // MOSTRARLO
            if($data->cantidad > 0){
                array_push($pilaArrayEntrada, $data->id);
            }
        }

        $lista = Entradas::whereIn('id', $pilaArrayEntrada)
            ->orderBy('id_tipoproyecto', 'ASC')
            ->get();

        foreach ($lista as $info){
            // OBTENER NOMBRE DE PROYECTO

            $infoProyecto = TipoProyecto::where('id', $info->id_tipoproyecto)->first();
            $info->nombrepro = $infoProyecto->nombre;
        }

        return view('backend.admin.inventario.detalle.tabladetallematerial', compact('lista'));
    }



}
