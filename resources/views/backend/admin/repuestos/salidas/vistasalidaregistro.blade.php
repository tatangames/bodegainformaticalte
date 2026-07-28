@extends('adminlte::page')

@section('title', 'Registro de Salidas')

@section('content_header')
    <h1>Registro de Salidas</h1>
@stop

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

    <style>
        #matriz { table-layout: auto; word-break: break-word; width: 100%; }
        #matriz-busqueda { table-layout: fixed; }
        .cursor-pointer { cursor: pointer; }
        .cursor-pointer:hover { color: #401fd2; font-weight: bold; }
        *:focus { outline: none; }
        .badge-global {
            background: #17a2b8;
            color: #fff;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 3px;
            vertical-align: middle;
        }
    </style>

    <div id="divcontenedor">

        {{-- ══ Card Información ══ --}}
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-gray-dark">
                            <div class="card-header">
                                <h3 class="card-title">Información de Salida</h3>
                            </div>
                            <div class="card-body">

                                {{-- Fila 1: Tipo de salida + botón --}}
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label>Tipo de Salida: <span class="text-danger">*</span></label>
                                            <select class="form-control" id="select-tiposalida" style="width:100%">
                                                <option value="">Seleccione...</option>
                                                @foreach($arrayTipoSalida as $ts)
                                                    <option value="{{ $ts->id }}">{{ $ts->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-5"></div>
                                    <div class="col-md-2 d-flex align-items-end justify-content-end">
                                        <div class="form-group">
                                            <button type="button" id="botonaddmaterial"
                                                    onclick="abrirModal()"
                                                    class="btn btn-primary btn-sm" disabled>
                                                <i class="fas fa-search mr-1"></i> Buscar Material
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <hr class="mt-1 mb-3">

                                {{-- ══ Campos globales por ítem ══ --}}
                                <div class="alert alert-info py-2 mb-3" style="border-left: 4px solid #17a2b8;">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <strong>Campos globales por ítem:</strong>
                                    Si completas los campos a continuación, se copiarán automáticamente en
                                    <strong>todas las filas</strong> que agregues al detalle.
                                    Podrás editarlos individualmente por fila después.
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>
                                                Fecha por ítem
                                                <span class="badge-global">global</span>
                                                <small class="text-muted">(Opcional)</small>
                                            </label>
                                            <input type="date" class="form-control" id="fecha_global_item">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>
                                                N° Solicitud por ítem
                                                <span class="badge-global">global</span>
                                                <small class="text-muted">(Opcional)</small>
                                            </label>
                                            <input type="text" class="form-control" autocomplete="off"
                                                   maxlength="100" id="numero_solicitud_global"
                                                   placeholder="Ej: SOL-001">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>
                                                Descripción por ítem
                                                <span class="badge-global">global</span>
                                                <small class="text-muted">(Opcional)</small>
                                            </label>
                                            <input type="text" class="form-control" autocomplete="off"
                                                   maxlength="800" id="descripcion_global"
                                                   placeholder="Se copiará en todas las filas...">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>
                                                Departamento por ítem
                                                <span class="badge-global">global</span>
                                                <small class="text-muted">(Opcional)</small>
                                            </label>
                                            <select class="form-control" id="departamento_global" style="width:100%">
                                                <option value="">Sin departamento</option>
                                                @foreach($arrayDepartamentos as $dep)
                                                    <option value="{{ $dep->id }}">{{ $dep->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══ Modal Buscar Material ══ --}}
        <div class="modal fade" id="modalRepuesto">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header" style="background:#2156af">
                        <h4 class="modal-title text-white">
                            <i class="fas fa-search mr-2"></i>Buscar Material
                        </h4>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario-repuesto">
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Buscar material:</label>
                                    <table class="table" id="matriz-busqueda">
                                        <tbody>
                                        <tr>
                                            <td>
                                                <input id="inputBuscador" autocomplete="off"
                                                       class="form-control" style="width:100%"
                                                       onkeyup="buscarMaterial(this)"
                                                       maxlength="300" type="text"
                                                       placeholder="Escribir nombre del material...">
                                                <div class="droplista" id="midropmenu"
                                                     style="position:absolute;z-index:9;width:95%!important;"></div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ Modal Cantidades ══ --}}
        <div class="modal fade" id="modalCantidad">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header" style="background:#1a3a6b">
                        <h4 class="modal-title text-white">
                            <i class="fas fa-boxes mr-2"></i>Salida de Material
                        </h4>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario-material">
                            <div class="card-body">

                                <input type="hidden" id="id-material-seleccionado">

                                <div class="form-row mb-3">
                                    <div class="col-md-9">
                                        <label>Material</label>
                                        <input type="text" disabled class="form-control" id="info-material">
                                    </div>
                                    <div class="col-md-3">
                                        <label>U/M</label>
                                        <input type="text" disabled class="form-control" id="info-medida">
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" id="matrizM">
                                        <thead class="thead-dark">
                                        <tr>
                                            <th>Fecha Ingreso</th>
                                            <th>Detalle (opcional)</th>
                                            <th>Precio</th>
                                            <th class="text-center">Cant. Actual</th>
                                            <th class="text-center">Cant. Salida</th>
                                        </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-success" onclick="agregarAlDetalle()">
                            <i class="fas fa-plus mr-1"></i> Agregar al Detalle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ Tabla Detalle ══ --}}
        <section class="content-header">
            <div class="row mb-2">
                <div class="col-sm-6"><h2>Detalle de Salida</h2></div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card card-gray-dark">
                    <div class="card-header">
                        <h3 class="card-title">Materiales a retirar</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0" id="matriz">
                                <thead>
                                <tr>
                                    <th style="width:3%;  min-width:40px">#</th>
                                    <th style="width:14%; min-width:120px">Material</th>
                                    <th style="width:5%;  min-width:60px" class="text-center">Cant.</th>
                                    <th style="width:10%; min-width:120px">Fecha</th>
                                    <th style="width:10%; min-width:110px">N° Solicitud</th>
                                    <th style="width:16%; min-width:140px">Descripción</th>
                                    <th style="width:16%; min-width:140px">Departamento</th>
                                    <th style="width:11%; min-width:110px" class="text-center">Estado</th>
                                    <th style="width:7%;  min-width:70px">Opciones</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══ Botón Guardar ══ --}}
        <div class="modal-footer justify-content-between" style="margin-top:25px;">
            <button type="button" class="btn btn-success" onclick="preguntaGuardar()">
                <i class="fas fa-save mr-1"></i> Guardar Salida
            </button>
        </div>

    </div>

@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}" type="text/javascript"></script>

    <script>

        // Opciones de departamentos para el select de cada fila
        var arrayDepartamentosOpciones = [
            '<option value="">Sin departamento</option>',
            @foreach($arrayDepartamentos as $dep)
                '<option value="{{ $dep->id }}" data-id="{{ $dep->id }}">{{ $dep->nombre }}</option>',
            @endforeach
        ].join('');

        var seguroBuscador = true;

        // Configuración Select2 reutilizable
        var select2Config = {
            theme: 'bootstrap-5',
            dropdownParent: $('body'),
            language: { noResults: function () { return 'No encontrado'; } }
        };

        $(function () {
            $('#select-tiposalida').select2(select2Config);

            $('#select-tiposalida').on('change', function () {
                var val = $(this).val();
                $('#botonaddmaterial').prop('disabled', !val || val === '');
            });

            // ── Select2 en departamento global ────────────────────────
            $('#departamento_global').select2(select2Config);

            $(document).click(function () { $('.droplista').hide(); });
        });

        // ── Modal buscador ────────────────────────────────────────────
        function abrirModal() {
            document.getElementById('formulario-repuesto').reset();
            $('.droplista').html('').hide();
            $('#modalRepuesto').modal('show');
        }

        // ── Buscar material ───────────────────────────────────────────
        function buscarMaterial(e) {
            if (!seguroBuscador) return;
            seguroBuscador = false;

            var texto = e.value;
            if (texto === '') {
                $('.droplista').hide();
                seguroBuscador = true;
                return;
            }

            axios.post(urlAdmin + '/admin/buscar/material/disponible', { query: texto })
                .then(function (response) {
                    seguroBuscador = true;
                    $('#midropmenu').fadeIn().html(response.data);
                })
                .catch(function () { seguroBuscador = true; });
        }

        // ── Seleccionar material → modal cantidades ───────────────────
        function modificarValor(edrop) {
            openLoading();
            $('#matrizM tbody').empty();

            var formData = new FormData();
            formData.append('id', edrop.id);

            axios.post(urlAdmin + '/admin/buscar/material/disponibilidad', formData)
                .then(function (response) {
                    closeLoading();
                    if (response.data.success !== 1) {
                        toastr.error('Error al cargar material'); return;
                    }
                    if (response.data.disponible === 1) {
                        toastr.info('NO HAY INVENTARIO DISPONIBLE'); return;
                    }

                    $('#id-material-seleccionado').val(edrop.id);
                    $('#info-material').val(response.data.nombreMaterial);
                    $('#info-medida').val(response.data.nombreMedida);

                    $.each(response.data.arrayIngreso, function (key, val) {
                        var fila =
                            '<tr>' +
                            '<td><input disabled value="' + val.fechaIngreso + '" class="form-control form-control-sm" type="text"></td>' +
                            '<td><input disabled value="' + (val.codigo ?? '') + '" class="form-control form-control-sm" type="text"></td>' +
                            '<td><input disabled value="' + val.precioFormat + '" class="form-control form-control-sm" type="text"></td>' +
                            '<td class="text-center"><input disabled name="arrayCantidadActual[]" ' +
                            'data-cantidadActualFila="' + val.cantidadActual + '" ' +
                            'value="' + val.cantidadActual + '" ' +
                            'class="form-control form-control-sm text-center" type="number"></td>' +
                            '<td><input class="form-control form-control-sm" ' +
                            'data-idfilaentradadetalle="' + val.id + '" ' +
                            'name="arrayCantidadSalida[]" min="0" max="' + val.cantidadActual + '" ' +
                            'type="number" placeholder="0" ' +
                            'onkeydown="return validateInput(event);" ' +
                            'oninput="validateCantidadSalida(this, ' + val.cantidadActual + ');">' +
                            '</td>' +
                            '</tr>';
                        $('#matrizM tbody').append(fila);
                    });

                    $('#modalRepuesto').modal('hide');
                    $('#modalCantidad').modal('show');
                })
                .catch(function () { closeLoading(); toastr.error('Error'); });
        }

        // ── Agregar al detalle ────────────────────────────────────────
        function agregarAlDetalle() {
            var arrayIdEntradaDetalle = $("input[name='arrayCantidadSalida[]']")
                .map(function () { return $(this).attr('data-idfilaentradadetalle'); }).get();
            var arrayCantidadSalida = $("input[name='arrayCantidadSalida[]']")
                .map(function () { return $(this).val(); }).get();
            var arrayCantidadActual = $("input[name='arrayCantidadActual[]']")
                .map(function () { return $(this).attr('data-cantidadActualFila'); }).get();

            colorBlancoMatriz();
            var habraSalida = false;

            for (var a = 0; a < arrayCantidadSalida.length; a++) {
                var fc  = arrayCantidadSalida[a];
                var max = arrayCantidadActual[a];

                if (fc !== '' && parseInt(fc) > 0) {
                    habraSalida = true;
                    if (parseInt(fc) > parseInt(max)) {
                        colorRojoMatriz(a);
                        toastr.error('Fila #' + (a + 1) + ': Supera cantidad disponible (' + max + ')');
                        return;
                    }
                }
                if (fc !== '' && parseInt(fc) <= 0 && fc !== '') {
                    colorRojoMatriz(a);
                    toastr.error('Fila #' + (a + 1) + ': No se permite cero o negativo');
                    return;
                }
            }

            if (!habraSalida) { toastr.error('Registre mínimo 1 cantidad de salida'); return; }

            // ── Leer valores globales ─────────────────────────────────
            var fechaGlobal       = document.getElementById('fecha_global_item').value;
            var solicitudGlobal   = document.getElementById('numero_solicitud_global').value;
            var descripcionGlobal = document.getElementById('descripcion_global').value;
            var deptoGlobalVal    = $('#departamento_global').val();
            var deptoGlobalText   = $('#departamento_global option:selected').text();

            var nombreTexto = document.getElementById('info-material').value;

            for (var z = 0; z < arrayCantidadSalida.length; z++) {
                var fc2 = arrayCantidadSalida[z];
                if (fc2 !== '' && parseInt(fc2) > 0) {
                    var nFilas = $('#matriz tbody tr').length + 1;

                    // Construir options del select de departamento pre-seleccionando el global
                    var deptoOptions = '<option value="">Sin departamento</option>';
                    @foreach($arrayDepartamentos as $dep)
                        deptoOptions += '<option value="{{ $dep->id }}"' +
                        (deptoGlobalVal === '{{ $dep->id }}' ? ' selected' : '') +
                        '>{{ $dep->nombre }}</option>';
                    @endforeach

                    var fila =
                        '<tr>' +
                        '<td><span class="num-fila">' + nFilas + '</span></td>' +

                        // Material
                        '<td>' +
                        '<input name="idmaterialArray[]" type="hidden" ' +
                        'data-idmaterialArray="' + arrayIdEntradaDetalle[z] + '" ' +
                        'data-nombreMaterial="' + nombreTexto + '">' +
                        nombreTexto +
                        '</td>' +

                        // Cantidad
                        '<td class="text-center">' +
                        '<input name="salidaArray[]" type="hidden" data-cantidadSalida="' + fc2 + '">' +
                        fc2 +
                        '</td>' +

                        // Fecha por fila
                        '<td>' +
                        '<input name="fechaItemArray[]" type="date" ' +
                        'class="form-control form-control-sm" ' +
                        'value="' + fechaGlobal + '">' +
                        '</td>' +

                        // N° Solicitud por fila
                        '<td>' +
                        '<input name="solicitudItemArray[]" type="text" ' +
                        'class="form-control form-control-sm" ' +
                        'maxlength="100" placeholder="SOL-..." ' +
                        'value="' + solicitudGlobal + '">' +
                        '</td>' +

                        // Descripción por fila
                        '<td>' +
                        '<input name="descripcionItemArray[]" type="text" ' +
                        'class="form-control form-control-sm" ' +
                        'maxlength="800" placeholder="Descripción..." ' +
                        'value="' + descripcionGlobal + '">' +
                        '</td>' +

                        // Departamento por fila (se inicializa Select2 después del append)
                        '<td>' +
                        '<select name="departamentoArray[]" class="form-control form-control-sm select2-depto" style="width:100%">' +
                        deptoOptions +
                        '</select>' +
                        '</td>' +

                        // Estado toggle
                        '<td class="text-center">' +
                        '<input name="estadoArray[]" type="hidden" data-estadoSalida="finalizado">' +
                        '<button type="button" class="btn btn-success btn-sm btn-estado" ' +
                        'onclick="toggleEstado(this)" data-estado="finalizado">' +
                        '<i class="fas fa-check-circle mr-1"></i> Finalizado' +
                        '</button>' +
                        '</td>' +

                        // Opciones
                        '<td>' +
                        '<button type="button" class="btn btn-danger btn-sm btn-block" onclick="borrarFila(this)">' +
                        '<i class="fas fa-trash"></i>' +
                        '</button>' +
                        '</td>' +
                        '</tr>';

                    $('#matriz tbody').append(fila);

                    // ── Inicializar Select2 en el select recién agregado ──
                    $('#matriz tbody tr:last-child select.select2-depto').select2(select2Config);
                }
            }

            $('#modalCantidad').modal('hide');
            document.getElementById('inputBuscador').value = '';
            $('.droplista').html('').hide();
            toastr.success('Material agregado al detalle');
        }


        // ── Toggle estado por fila ────────────────────────────────────
        function toggleEstado(btn) {
            var estadoActual = $(btn).attr('data-estado');
            var hiddenEstado = $(btn).siblings('input[name="estadoArray[]"]');

            if (estadoActual === 'pendiente') {
                $(btn)
                    .attr('data-estado', 'finalizado')
                    .removeClass('btn-warning')
                    .addClass('btn-success')
                    .html('<i class="fas fa-check-circle mr-1"></i> Finalizado');
                hiddenEstado.attr('data-estadoSalida', 'finalizado');
            } else {
                $(btn)
                    .attr('data-estado', 'pendiente')
                    .removeClass('btn-success')
                    .addClass('btn-warning')
                    .html('<i class="fas fa-clock mr-1"></i> Pendiente');
                hiddenEstado.attr('data-estadoSalida', 'pendiente');
            }
        }

        // ── Guardar salida ────────────────────────────────────────────
        function preguntaGuardar() {
            colorBlancoTabla();
            Swal.fire({
                title: '¿Guardar Salida?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sí, guardar'
            }).then(function (result) {
                if (result.isConfirmed) guardarSalida();
            });
        }


        function guardarSalida() {
            var tiposalida = document.getElementById('select-tiposalida').value;

            if (!tiposalida) { toastr.error('Tipo de Salida es requerido'); return; }

            if ($('#matriz tbody tr').length === 0) {
                toastr.error('Agregue al menos un material'); return;
            }

            // ── Leer globales ─────────────────────────────────────────────────────
            var fechaGlobal       = document.getElementById('fecha_global_item').value;
            var solicitudGlobal   = document.getElementById('numero_solicitud_global').value;
            var descripcionGlobal = document.getElementById('descripcion_global').value.trim();
            var deptoGlobal       = $('#departamento_global').val();

            // ── Validar fecha obligatoria por fila ────────────────────────────────
            var sinFecha       = false;
            var filaFechaError = 0;

            $('#matriz tbody tr').each(function (index) {
                var fechaFila  = $(this).find('input[name="fechaItemArray[]"]').val();
                var fechaFinal = (fechaGlobal && fechaGlobal !== '') ? fechaGlobal : fechaFila;
                if (!fechaFinal || fechaFinal === '') {
                    sinFecha       = true;
                    filaFechaError = index + 1;
                    $(this).css('background', '#f8d7da');
                    return false;
                }
            });

            if (sinFecha) {
                toastr.error('Fila #' + filaFechaError + ': La fecha es obligatoria (complétala en la fila o en el campo global)');
                return;
            }

            // ── Validar descripción obligatoria si estado es PENDIENTE ────────────
            var sinDescripcion = false;
            var filaDescError  = 0;

            $('#matriz tbody tr').each(function (index) {
                var estadoFila = $(this).find('input[name="estadoArray[]"]').attr('data-estadoSalida');

                if (estadoFila === 'pendiente') {
                    var descFila  = $(this).find('input[name="descripcionItemArray[]"]').val().trim();
                    var descFinal = descripcionGlobal !== '' ? descripcionGlobal : descFila;

                    if (!descFinal || descFinal === '') {
                        sinDescripcion = true;
                        filaDescError  = index + 1;
                        $(this).css('background', '#f8d7da');
                        return false;
                    }
                }
            });

            if (sinDescripcion) {
                toastr.error('Fila #' + filaDescError + ': La descripción es obligatoria cuando el estado es Pendiente');
                return;
            }

            // ── Validar departamento obligatorio si es SALIDA CON SOLICITUD ───────
            if (parseInt(tiposalida) === 1) {

                if (!deptoGlobal || deptoGlobal === '') {
                    var sinDepartamento = false;
                    var filaDeptoError  = 0;

                    $('#matriz tbody tr').each(function (index) {
                        var depVal = $(this).find('select[name="departamentoArray[]"]').val();
                        if (!depVal || depVal === '') {
                            sinDepartamento = true;
                            filaDeptoError  = index + 1;
                            $(this).css('background', '#f8d7da');
                            return false;
                        }
                    });

                    if (sinDepartamento) {
                        toastr.error('Fila #' + filaDeptoError + ': El departamento es obligatorio (complétalo en la fila o en el campo global)');
                        return;
                    }
                }
            }

            // ── Recolectar datos de la tabla ──────────────────────────────────────
            var idEntradaDetalle      = $("input[name='idmaterialArray[]']")
                .map(function () { return $(this).attr('data-idmaterialArray'); }).get();
            var salidaCantidad        = $("input[name='salidaArray[]']")
                .map(function () { return $(this).attr('data-cantidadSalida'); }).get();
            var salidaEstado          = $("input[name='estadoArray[]']")
                .map(function () { return $(this).attr('data-estadoSalida'); }).get();
            var salidaDepartamento    = $("select[name='departamentoArray[]']")
                .map(function () { return $(this).val(); }).get();
            var salidaFechaItem       = $("input[name='fechaItemArray[]']")
                .map(function () { return $(this).val(); }).get();
            var salidaSolicitudItem   = $("input[name='solicitudItemArray[]']")
                .map(function () { return $(this).val(); }).get();
            var salidaDescripcionItem = $("input[name='descripcionItemArray[]']")
                .map(function () { return $(this).val(); }).get();

            var contenedorArray = [];
            for (var p = 0; p < salidaCantidad.length; p++) {
                var fechaFinal       = (fechaGlobal       && fechaGlobal       !== '') ? fechaGlobal                       : (salidaFechaItem[p]       || '');
                var solicitudFinal   = (solicitudGlobal   && solicitudGlobal   !== '') ? solicitudGlobal                   : (salidaSolicitudItem[p]   || '');
                var descripcionFinal = (descripcionGlobal && descripcionGlobal !== '') ? descripcionGlobal                 : (salidaDescripcionItem[p] || '');
                var deptoFinal       = (deptoGlobal       && deptoGlobal       !== '') ? deptoGlobal                       : (salidaDepartamento[p]    || '');

                contenedorArray.push({
                    infoIdEntradaDeta:   idEntradaDetalle[p],
                    infoCantidad:        salidaCantidad[p],
                    infoEstado:          salidaEstado[p],
                    infoTipoSalida:      tiposalida,
                    infoDepartamento:    deptoFinal,
                    infoFechaItem:       fechaFinal,
                    infoSolicitudItem:   solicitudFinal,
                    infoDescripcionItem: descripcionFinal,
                });
            }

            openLoading();
            var formData = new FormData();
            formData.append('contenedorArray', JSON.stringify(contenedorArray));

            axios.post(urlAdmin + '/admin/salida/guardar', formData)
                .then(function (response) {
                    closeLoading();
                    colorBlancoTabla();
                    if (response.data.success === 10) {
                        Swal.fire({
                            title: 'Salida Registrada',
                            icon: 'success',
                            allowOutsideClick: false,
                            confirmButtonColor: '#28a745',
                            confirmButtonText: 'Aceptar'
                        }).then(function () { location.reload(); });
                    } else if (response.data.success === 2) {
                        Swal.fire({
                            title: 'Cantidad no disponible',
                            html: '<b>' + response.data.nombre_material + '</b><br><br>' +
                                'Solicitado: <b>' + response.data.cantidad_pedida + '</b><br>' +
                                'Disponible: <b>' + response.data.disponible + '</b>',
                            icon: 'warning',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Entendido'
                        });
                    } else if (response.data.success === 3) {
                        Swal.fire({
                            title: 'Fecha de salida inválida',
                            html: '<b>' + response.data.nombre_material + '</b><br><br>' +
                                'Fecha de entrada: <b>' + response.data.fecha_entrada + '</b><br>' +
                                'Fecha de salida: <b>' + response.data.fecha_salida + '</b><br><br>' +
                                'La fecha de salida no puede ser anterior a la fecha de entrada.',
                            icon: 'warning',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        toastr.error('Error al guardar');
                    }
                })
                .catch(function () { closeLoading(); toastr.error('Error al guardar'); });
        }



        // ── Utilidades ────────────────────────────────────────────────
        function borrarFila(btn) {
            var $fila = $(btn).closest('tr');
            // Destruir Select2 antes de remover la fila (evita memory leaks)
            var $sel = $fila.find('select[name="departamentoArray[]"]');
            if ($sel.data('select2')) $sel.select2('destroy');
            $fila.remove();
            renumerarFilas();
        }

        function renumerarFilas() {
            $('#matriz tbody tr').each(function (i) {
                $(this).find('.num-fila').text(i + 1);
            });
        }

        function colorBlancoTabla() {
            $('#matriz tbody tr').css('background', 'white');
        }

        function colorRojoMatriz(index) {
            $('#matrizM tbody tr:eq(' + index + ')').css('background', '#f8d7da');
        }

        function colorBlancoMatriz() {
            $('#matrizM tbody tr').css('background', 'white');
        }

        function validateInput(event) {
            const key = event.key;
            if (['Backspace', 'ArrowLeft', 'ArrowRight', 'Delete', 'Tab'].includes(key)) return true;
            if (key === 'e' || key === 'E' || key === '-' || isNaN(Number(key))) return false;
            return true;
        }

        function validateCantidadSalida(input, maxCantidad) {
            input.value = input.value.replace(/[^0-9]/g, '');
            if (Number(input.value) > maxCantidad) input.value = maxCantidad;
        }

    </script>
@endsection
