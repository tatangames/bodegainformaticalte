<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ControlController extends Controller
{
    public function indexRedireccionamiento(){
        $user = Auth::user();

        // ADMINISTRADOR
        if($user->hasRole('admin')){
            return redirect()->route('admin.roles.index');
        }
        // Inventario
        else  if($user->hasRole('inventario')){
            return redirect()->route('admin.materiales.index');
        }
        // Reportes
        else  if($user->hasRole('reportes')){
            return redirect()->route('admin.reporte.generales.index');
        }

        return redirect()->route('no.permisos.index');
    }

    public function indexSinPermiso(){
        return view('errors.403');
    }

}
