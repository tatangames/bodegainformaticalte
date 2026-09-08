@extends('adminlte::page')

@section('title', 'Error 500')

@section('content_header')
    <h1>Error 500</h1>
@stop
{{-- Activa plugins que necesitas --}}
@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.Toastr', true)
@section('plugins.Sweetalert2', true)

@section('content_top_nav_right')

    <li class="nav-item dropdown">
        <a href="#" class="nav-link" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-cogs"></i>
            <span class="d-none d-md-inline">{{ Auth::guard('admin')->user()->nombre ?? 'Usuario' }}</span>
        </a>

        <div class="dropdown-menu dropdown-menu-right">
            <a href="{{ route('admin.perfil') }}" class="dropdown-item">
                <i class="fas fa-user mr-2"></i> Editar Perfil
            </a>

            <div class="dropdown-divider"></div>

            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="dropdown-item">
                    <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                </button>
            </form>
        </div>
    </li>

@endsection

@section('content')

    <div id="divcontenedor">
        <section class="content-header">
            <div class="container-fluid">
                <section class="content">
                    <div class="error-page" style="margin-top: 40px;">
                        <h2 class="headline text-danger" style="font-size: 90px;">
                            500
                        </h2>

                        <div class="error-content" style="margin-left: 0; text-align: center;">
                            <h3 class="mb-3">
                                <i class="fas fa-exclamation-triangle text-danger"></i> Ha ocurrido un error interno
                            </h3>

                            <p class="text-muted" style="font-size: 16px;">
                                Algo salió mal en el servidor. Intenta nuevamente o vuelve
                                al inicio de sesión. Si el problema persiste, contacta al administrador del sistema.
                            </p>

                            <div class="mt-4">
                                <a href="{{ route('login.admin') }}" class="btn btn-danger btn-lg">
                                    <i class="fas fa-sign-in-alt mr-2"></i> Volver al inicio de sesión
                                </a>

                                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-lg ml-2">
                                    <i class="fas fa-arrow-left mr-2"></i> Regresar
                                </a>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </div>

@stop

@section('js')


@endsection
