@extends('adminlte::page')

@section('title', 'Historial / Salidas')

@section('content_header')
    <h1>Historial de Salidas</h1>
@stop

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.Sweetalert2', true)

@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet"/>
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">

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

    {{-- ══ Modal Ver Detalle Salida ══ --}}
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background:#1a3a6b">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-list mr-2"></i>Detalle de la Salida
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    {{-- Info cabecera --}}
                    <div class="card card-outline card-secondary mb-3">
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Fecha</small>
                                    <strong id="det-fecha">—</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Tipo de Salida</small>
                                    <span id="det-tipo">—</span>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">N° Solicitud</small>
                                    <span id="det-solicitud">—</span>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Departamento</small>
                                    <span id="det-departamento">—</span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Material</small>
                                    <strong id="det-material">—</strong>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Descripción</small>
                                    <span id="det-descripcion">—</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Entregas adicionales --}}
                    <h6 class="mb-2">
                        <i class="fas fa-truck mr-1 text-primary"></i>
                        Entregas adicionales registradas
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>N° Solicitud</th>
                                <th>Departamento</th>
                                <th class="text-center">Cantidad</th>
                                <th>Observación</th>
                            </tr>
                            </thead>
                            <tbody id="det-entregas-tbody">
                            <tr>
                                <td colspan="6" class="text-center text-muted">—</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Modal Editar Salida ══ --}}
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-edit mr-2"></i>Editar Salida
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editar-id">

                    <div class="form-group">
                        <label>Fecha <span class="text-danger">*</span></label>
                        <input type="date" id="editar-fecha" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Tipo de Salida <span class="text-danger">*</span></label>
                        <select id="editar-tiposalida" class="form-control" style="width:100%">
                            <option value="">Seleccione...</option>
                            @foreach($arrayTipoSalida as $ts)
                                <option value="{{ $ts->id }}">{{ $ts->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Departamento <small class="text-muted">(Opcional)</small></label>
                        <select id="editar-departamento" class="form-control" style="width:100%">
                            <option value="">Sin departamento</option>
                            @foreach($arrayDepartamentos as $dep)
                                <option value="{{ $dep->id }}">{{ $dep->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado <span class="text-danger">*</span></label>
                        <select id="editar-estado" class="form-control">
                            <option value="finalizado">Finalizado</option>
                            <option value="pendiente">Pendiente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>N° Solicitud <small class="text-muted">(Opcional)</small></label>
                        <input type="text" id="editar-solicitud" class="form-control" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label>Descripción <small class="text-muted">(Opcional)</small></label>
                        <textarea id="editar-descripcion" class="form-control" rows="3" maxlength="800"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" onclick="guardarEdicion()">
                        <i class="fas fa-save mr-1"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="divcontenedor">

        {{-- ══ FILTROS ══ --}}
        <section class="content" style="margin-bottom:0">
            <div class="container-fluid">
                <div class="card card-gray-dark">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filtros</h3>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label class="font-weight-bold">Tipo de Salida</label>
                                <select class="form-control" id="filtro-tiposalida">
                                    <option value="">— Todos —</option>
                                    @foreach($arrayTipoSalida as $ts)
                                        <option value="{{ $ts->id }}">{{ $ts->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="font-weight-bold">Departamento</label>
                                <select class="form-control" id="filtro-departamento">
                                    <option value="">— Todos —</option>
                                    @foreach($arrayDepartamentos as $dep)
                                        <option value="{{ $dep->id }}">{{ $dep->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="font-weight-bold">Fecha desde</label>
                                <input type="date" class="form-control" id="filtro-fecha-desde">
                            </div>
                            <div class="col-md-2">
                                <label class="font-weight-bold">Fecha hasta</label>
                                <input type="date" class="form-control" id="filtro-fecha-hasta">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div style="width:100%">
                                    <button class="btn btn-primary btn-block mb-1" onclick="buscarConFiltros()">
                                        <i class="fas fa-search mr-1"></i> Buscar
                                    </button>
                                    <button class="btn btn-secondary btn-block" onclick="limpiarFiltros()">
                                        <i class="fas fa-times mr-1"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label class="font-weight-bold">Objeto Específico</label>
                                <select class="form-control" id="filtro-objeto">
                                    <option value="">— Todos —</option>
                                    @foreach($arrayObjetoEspecifico as $oe)
                                        <option value="{{ $oe->id }}">
                                            {{ $oe->codigo }} — {{ $oe->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="font-weight-bold">Buscar por material</label>
                                <input type="text" class="form-control" id="filtro-material"
                                       placeholder="Nombre del material...">
                            </div>
                            <div class="col-md-4">
                                <label class="font-weight-bold">N° Solicitud</label>
                                <input type="text" class="form-control" id="filtro-solicitud"
                                       placeholder="Ej: SOL-001...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══ TABLA ══ --}}
        <section class="content">
            <div class="container-fluid">
                <div class="card card-gray-dark">
                    <div class="card-header">
                        <h3 class="card-title">Resultados</h3>
                        <div class="card-tools">
                            <span class="badge badge-info" id="badge-total" style="display:none"></span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="div-instruccion" class="text-center text-muted py-5">
                            <i class="fas fa-search fa-3x mb-3 d-block"></i>
                            <p class="mb-0">Utiliza los filtros de arriba y presiona <strong>Buscar</strong> para ver el historial.</p>
                        </div>
                        <div id="div-tabla" style="display:none">
                            <div id="tablaDatatable"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>

@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}" type="text/javascript"></script>

    <script>

        const RUTA_TABLA       = "{{ url('/admin/historial/salidas/tabla') }}";
        const RUTA_EDITAR_INFO = "{{ url('/admin/historial/salidas/informacion') }}";
        const RUTA_EDITAR_SAVE = "{{ url('/admin/historial/salidas/editar') }}";
        const RUTA_ELIMINAR    = "{{ url('/admin/historial/salidas/eliminar') }}";
        const RUTA_DETALLE     = "{{ url('/admin/historial/salidas/detalle') }}";

        $(function () {
            $('#filtro-tiposalida').select2({
                theme: 'bootstrap-5',
                placeholder: '— Todos —',
                allowClear: true,
                language: { noResults: function () { return 'No encontrado'; } }
            });
            $('#filtro-departamento').select2({
                theme: 'bootstrap-5',
                placeholder: '— Todos —',
                allowClear: true,
                language: { noResults: function () { return 'No encontrado'; } }
            });
            $('#editar-tiposalida').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalEditar'),
                language: { noResults: function () { return 'No encontrado'; } }
            });
            $('#editar-departamento').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalEditar'),
                language: { noResults: function () { return 'No encontrado'; } }
            });


            $('#filtro-objeto').select2({
                theme: 'bootstrap-5',
                placeholder: '— Todos —',
                allowClear: true,
                language: { noResults: function () { return 'No encontrado'; } }
            });

        });

        // ── Inicializar DataTable ─────────────────────────────────────
        function initDataTable() {
            if ($.fn.DataTable.isDataTable('#tabla-historial')) {
                $('#tabla-historial').DataTable().destroy();
            }
            $('#tabla-historial').DataTable({
                paging:       true,
                lengthChange: true,
                searching:    true,
                ordering:     true,
                info:         true,
                autoWidth:    false,
                responsive:   true,
                pagingType:   'full_numbers',
                lengthMenu:   [[50, 100, -1], [50, 100, 'Todo']],
                // Excluir la columna de acciones del ordenamiento y búsqueda
                columnDefs: [
                    { orderable: false, targets: [11] },
                    { searchable: false, targets: [11] }
                ],
                language: {
                    sProcessing:   'Procesando...',
                    sLengthMenu:   'Mostrar _MENU_ registros',
                    sZeroRecords:  'No se encontraron resultados',
                    sEmptyTable:   'Ningún dato disponible',
                    sInfo:         'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    sInfoEmpty:    'Mostrando 0 a 0 de 0 registros',
                    sInfoFiltered: '(filtrado de _MAX_ registros)',
                    sSearch:       'Buscar:',
                    oPaginate: {
                        sFirst: 'Primero', sLast: 'Último',
                        sNext: 'Siguiente', sPrevious: 'Anterior'
                    }
                },
                dom:
                    "<'row align-items-center'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 text-md-right'f>>" +
                    "tr" +
                    "<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });
            $('#tabla-historial_length select').addClass('form-control form-control-sm');
            $('#tabla-historial_filter input').addClass('form-control form-control-sm').css('display', 'inline-block');
        }

        // ── Buscar con filtros ────────────────────────────────────────
        function buscarConFiltros() {
            const params = new URLSearchParams();
            const tipo      = $('#filtro-tiposalida').val();
            const depto     = $('#filtro-departamento').val();
            const desde     = $('#filtro-fecha-desde').val();
            const hasta     = $('#filtro-fecha-hasta').val();
            const material  = $('#filtro-material').val().trim();
            const solicitud = $('#filtro-solicitud').val().trim();
            const objeto = $('#filtro-objeto').val();

            if (tipo)      params.append('tiposalida',   tipo);
            if (depto)     params.append('departamento', depto);
            if (desde)     params.append('fecha_desde',  desde);
            if (hasta)     params.append('fecha_hasta',  hasta);
            if (material)  params.append('material',     material);
            if (solicitud) params.append('solicitud',    solicitud);
            if (objeto) params.append('objeto_especifico', objeto);

            const url = RUTA_TABLA + (params.toString() ? '?' + params.toString() : '');

            $('#div-instruccion').hide();
            $('#div-tabla').show();
            $('#tablaDatatable').html(
                '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>'
            );

            $('#tablaDatatable').load(url, function () {
                initDataTable();
                const total = $('#tabla-historial tbody tr').length;
                $('#badge-total').text(total + ' registros').show();
            });
        }

        function limpiarFiltros() {
            $('#filtro-tiposalida').val('').trigger('change');
            $('#filtro-departamento').val('').trigger('change');
            $('#filtro-objeto').val('').trigger('change');   // ← NUEVO
            $('#filtro-fecha-desde').val('');
            $('#filtro-fecha-hasta').val('');
            $('#filtro-material').val('');
            $('#filtro-solicitud').val('');
            $('#div-instruccion').show();
            $('#div-tabla').hide();
            $('#badge-total').hide();
        }

        // ── Ver detalle ───────────────────────────────────────────────
        function verDetalle(id) {
            $('#det-fecha').text('—');
            $('#det-tipo').text('—');
            $('#det-solicitud').text('—');
            $('#det-departamento').text('—');
            $('#det-material').text('—');
            $('#det-descripcion').text('—');
            $('#det-entregas-tbody').html(
                '<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>'
            );
            $('#modalDetalle').modal('show');

            var formData = new FormData();
            formData.append('id', id);

            axios.post(RUTA_DETALLE, formData)
                .then(function (response) {
                    if (response.data.success !== 1) {
                        toastr.error('Error al cargar el detalle'); return;
                    }

                    var s = response.data.salida;
                    $('#det-fecha').text(s.fecha              ? formatearFecha(s.fecha) : '—');
                    $('#det-tipo').text(s.tipo_salida         || '—');
                    $('#det-solicitud').text(s.numero_solicitud || '—');
                    $('#det-departamento').text(s.departamento  || '—');
                    $('#det-material').text(s.material          || '—');
                    $('#det-descripcion').text(s.descripcion    || '—');

                    var entregas = response.data.entregas;
                    $('#det-entregas-tbody').empty();

                    if (!entregas || entregas.length === 0) {
                        $('#det-entregas-tbody').html(
                            '<tr><td colspan="6" class="text-center text-muted">Sin entregas adicionales</td></tr>'
                        );
                        return;
                    }

                    $.each(entregas, function (i, e) {
                        $('#det-entregas-tbody').append(
                            '<tr>' +
                            '<td>' + (i + 1) + '</td>' +
                            '<td>' + formatearFecha(e.fecha_entrega) + '</td>' +
                            '<td>' + (e.numero_solicitud || '<span class="text-muted">—</span>') + '</td>' +
                            '<td>' + (e.departamento     || '<span class="text-muted">Sin departamento</span>') + '</td>' +
                            '<td class="text-center">' + e.cantidad + '</td>' +
                            '<td>' + (e.observacion      || '<span class="text-muted">—</span>') + '</td>' +
                            '</tr>'
                        );
                    });
                })
                .catch(function () { toastr.error('Error al cargar el detalle'); });
        }

        // ── Editar ────────────────────────────────────────────────────
        function modalEditar(id) {
            openLoading();
            var formData = new FormData();
            formData.append('id', id);

            axios.post(RUTA_EDITAR_INFO, formData)
                .then(function (response) {
                    closeLoading();
                    if (response.data.success !== 1) {
                        toastr.error('No se pudo cargar la información'); return;
                    }
                    var s = response.data.salida;
                    $('#editar-id').val(s.id);
                    $('#editar-fecha').val(s.fecha ? s.fecha.substring(0, 10) : '');
                    $('#editar-solicitud').val(s.numero_solicitud ?? '');
                    $('#editar-descripcion').val(s.descripcion ?? '');
                    $('#editar-tiposalida').val(s.id_tiposalida ?? '').trigger('change');
                    $('#editar-departamento').val(s.id_departamento ?? '').trigger('change');
                    $('#editar-estado').val(s.estado ?? 'finalizado');
                    $('#modalEditar').modal('show');
                })
                .catch(function () { closeLoading(); toastr.error('Error al obtener información'); });
        }

        function guardarEdicion() {
            var id          = $('#editar-id').val();
            var fecha       = $('#editar-fecha').val().trim();
            var tipo        = $('#editar-tiposalida').val();
            var depto       = $('#editar-departamento').val();
            var solicitud   = $('#editar-solicitud').val().trim();
            var descripcion = $('#editar-descripcion').val().trim();
            var estado      = $('#editar-estado').val();

            if (!fecha) { toastr.error('La fecha es requerida'); return; }
            if (!tipo)  { toastr.error('El tipo de salida es requerido'); return; }

            openLoading();
            var formData = new FormData();
            formData.append('id',               id);
            formData.append('fecha',            fecha);
            formData.append('id_tiposalida',    tipo);
            formData.append('id_departamento',  depto);
            formData.append('numero_solicitud', solicitud);
            formData.append('descripcion',      descripcion);
            formData.append('estado',           estado);

            axios.post(RUTA_EDITAR_SAVE, formData)
                .then(function (response) {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Salida actualizada correctamente');
                        $('#modalEditar').modal('hide');
                        buscarConFiltros();
                    } else {
                        toastr.error('Error al actualizar');
                    }
                })
                .catch(function () { closeLoading(); toastr.error('Error al actualizar'); });
        }

        // ── Eliminar ──────────────────────────────────────────────────
        function eliminar(id) {
            Swal.fire({
                title: '¿Eliminar esta salida?',
                text: 'Esta acción no se puede deshacer.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.value) return;
                openLoading();
                var formData = new FormData();
                formData.append('id', id);
                axios.post(RUTA_ELIMINAR, formData)
                    .then(function (response) {
                        closeLoading();
                        if (response.data.success === 1) {
                            toastr.success('Salida eliminada correctamente');
                            buscarConFiltros();
                        } else {
                            toastr.error('Error al eliminar');
                        }
                    })
                    .catch(function () { closeLoading(); toastr.error('Error al eliminar'); });
            });
        }

        function formatearFecha(fecha) {
            if (!fecha) return '—';
            var p = fecha.substring(0, 10).split('-');
            if (p.length !== 3) return fecha;
            return p[2] + '-' + p[1] + '-' + p[0];
        }

    </script>
@endsection
