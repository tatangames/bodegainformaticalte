@extends('adminlte::page')

@section('title', 'Generales')

@section('plugins.Sweetalert2', true)
@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet"/>
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/estiloToggle.css') }}" type="text/css" rel="stylesheet" />
    <li class="nav-item dropdown">
        <a href="#" class="nav-link" data-toggle="dropdown">
            <i class="fas fa-cogs"></i>
            <span class="d-none d-md-inline">{{ Auth::guard('admin')->user()->nombre }}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-right">
            <a href="{{ route('admin.perfil') }}" class="dropdown-item">
                <i class="fas fa-user mr-2"></i> Editar Perfil
            </a>
        </div>
    </li>
    <li class="nav-item">
        <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="nav-link btn btn-link border-0 bg-transparent">
                <i class="fas fa-sign-out-alt"></i>
                <span class="d-none d-md-inline">Cerrar Sesión</span>
            </button>
        </form>
    </li>
@endsection

@section('content')
    <style>
        *:focus { outline: none; }
        .reporte-card {
            border: none; border-radius: 12px;
            box-shadow: 0 2px 18px rgba(0,0,0,.10);
            margin-bottom: 24px; overflow: hidden;
        }
        .reporte-header { padding: 14px 20px; display: flex; align-items: center; gap: 12px; }
        .reporte-header.entradas { background: linear-gradient(135deg, #1a6b2a, #28a745); }
        .reporte-header.salidas  { background: linear-gradient(135deg, #6b1a1a, #dc3545); }
        .reporte-header i  { font-size: 22px; color: #fff; }
        .reporte-header h5 {
            color: #fff; font-size: 14px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .05em; margin: 0;
        }
        .reporte-body { padding: 22px 24px; background: #fff; }
        .field-label {
            font-size: 11px; font-weight: 700; color: #6b7a99;
            text-transform: uppercase; letter-spacing: .06em;
            margin-bottom: 6px; display: block;
        }
        .divider { border: none; border-top: 2px dashed #e8eef8; margin: 12px 0 18px 0; }
        .btn-pdf {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 20px; border-radius: 8px; font-weight: 600;
            font-size: 13px; border: none; cursor: pointer;
            transition: all .2s; margin-top: 14px;
        }
        .btn-pdf.verde { background: linear-gradient(135deg, #1a6b2a, #28a745); color: #fff; box-shadow: 0 4px 14px rgba(40,167,69,.35); }
        .btn-pdf.rojo  { background: linear-gradient(135deg, #6b1a1a, #dc3545); color: #fff; box-shadow: 0 4px 14px rgba(220,53,69,.35); }
        .btn-pdf:hover { transform: translateY(-1px); filter: brightness(1.08); color: #fff; }
        .fecha-row { display: flex; gap: 14px; margin-bottom: 14px; }
        .fecha-box { flex: 1; }
        .fecha-box label {
            font-size: 11px; font-weight: 700; color: #6b7a99;
            text-transform: uppercase; margin-bottom: 4px; display: block;
        }
        .tipo-badge {
            display: inline-block; font-size: 10px; font-weight: 700;
            padding: 2px 8px; border-radius: 4px; margin-left: 6px;
            vertical-align: middle;
        }
        .tipo-badge.juntos   { background:#d4edda; color:#155724; }
        .tipo-badge.separado { background:#cce5ff; color:#004085; }
    </style>

    <section class="content">
        <div class="container-fluid">
            <div class="row">

                {{-- ══ INVENTARIO ACTUAL ══ --}}
                <div class="col-md-4">
                    <div class="reporte-card">
                        <div class="reporte-header" style="background: linear-gradient(135deg, #1a4a6b, #1a73e8);">
                            <i class="fas fa-boxes"></i>
                            <h5>Inventario Actual de Materiales</h5>
                        </div>
                        <div class="reporte-body">
                            <p style="font-size:13px; color:#666; margin-bottom:14px;">
                                Existencias actuales (entradas menos salidas). Solo muestra materiales con cantidad mayor a cero.
                            </p>
                            <hr class="divider">

                            <div class="row">
                                <div class="col-md-6 d-flex">
                                    <button type="button" onclick="generarPdfInventario()" class="btn-pdf"
                                            style="background: linear-gradient(135deg, #1a4a6b, #1a73e8); color:#fff;
                               box-shadow: 0 4px 14px rgba(26,115,232,.35); margin-top:0;">
                                        <i class="fas fa-file-pdf"></i> Generar PDF
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- ══ ENTRADAS / SALIDAS POR PERÍODO ══ --}}
                <div class="col-md-4">
                    <div class="reporte-card">
                        <div class="reporte-header" style="background: linear-gradient(135deg, #6b4a1a, #e88e1a);">
                            <i class="fas fa-exchange-alt"></i>
                            <h5>Control de Entradas/Salidas por Período</h5>
                        </div>
                        <div class="reporte-body">
                            <p style="font-size:13px; color:#666; margin-bottom:14px;">
                                Muestra saldo inicial, entradas, salidas y saldo final de cada material dentro del rango de fechas seleccionado.
                            </p>
                            <hr class="divider">

                            <div class="fecha-row">
                                <div class="fecha-box">
                                    <label>Fecha desde <span class="text-danger">*</span></label>
                                    <input type="date" id="periodo-fecha-desde" class="form-control form-control-sm">
                                </div>
                                <div class="fecha-box">
                                    <label>Fecha hasta <span class="text-danger">*</span></label>
                                    <input type="date" id="periodo-fecha-hasta" class="form-control form-control-sm">
                                </div>
                            </div>

                            <button type="button" onclick="generarPdfPeriodo()" class="btn-pdf"
                                    style="background: linear-gradient(135deg, #6b4a1a, #e88e1a); color:#fff;
                               box-shadow: 0 4px 14px rgba(232,142,26,.35); margin-top:0;">
                                <i class="fas fa-file-pdf"></i> Generar PDF
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
    <script src="{{ asset('js/jquery.simpleaccordion.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}" type="text/javascript"></script>
    <script>

        function generarPdfInventario() {
            window.open("{{ url('admin/reporte/pdf/inventario') }}/", '_blank');
        }

        // ── Reporte de Entradas/Salidas por Período ────────────────────
        function generarPdfPeriodo() {
            var desde = document.getElementById('periodo-fecha-desde').value;
            var hasta = document.getElementById('periodo-fecha-hasta').value;

            if (!desde || !hasta) {
                toastr.error('Debes seleccionar fecha desde y fecha hasta');
                return;
            }

            if (desde > hasta) {
                toastr.error('La fecha "desde" no puede ser mayor que "hasta"');
                return;
            }

            var url = "{{ url('admin/bodega/reportespdf/inicial/final') }}/" + desde + '/' + hasta;
            window.open(url, '_blank');
        }

    </script>
@endsection
