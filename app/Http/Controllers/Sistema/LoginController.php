<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('guest', except: ['logout',]),
        ];
    }

    public function vistaLoginForm(){

        if (auth()->check()) {
            return redirect()->route('admin.panel');
        }

        return view('frontend.login.vistalogin');
    }

    public function login(Request $request){

        $rules = array(
            'usuario' => 'required',
            'password' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if ( $validator->fails()){
            return ['success' => 0];
        }

        // si ya habia iniciado sesion, redireccionar
        if (Auth::check()) {
            return ['success'=> 1, 'ruta'=> route('admin.panel')];
        }

        $credenciales = [
            'usuario'    => $request->input('usuario'),
            'password' => $request->input('password'),
        ];

        if (Auth::guard('admin')->attempt($credenciales, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return response()->json([
                'success' => 1,
                'ruta' => route('admin.panel'),
            ]);
        }

        return ['success' => 2];
    }

    public function logout(Request $request){
        Auth::guard('admin')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login.admin');
    }



    public function vistaRedireccionOtrosSistemas()
    {
        return view('backend.admin.reportes.global.vistaredireccionsistema');
    }
}
