@extends('adminlte::page')

@section('title', 'Historial / Entradas')

@section('content_header')
    <h1>Historial / Entradas</h1>
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
    <div id="divcontenedor">

        {{-- ══ FILTROS ══ --}}
        <section class="content" style="margin-bottom:0">
            <div class="container-fluid">
                <div class="card card-blue">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filtros</h3>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-end">

                            <div class="col-md-2">
                                <label class="font-weight-bold">Fecha desde</label>
                                <input type="date" class="form-control" id="filtro-fecha-desde">
                            </div>
                            <div class="col-md-2">
                                <label class="font-weight-bold">Fecha hasta</label>
                                <input type="date" class="form-control" id="filtro-fecha-hasta">
                            </div>
                            <div class="col-md-3">
                                <label class="font-weight-bold">Tipo de Compra</label>
                                <select class="form-control" id="filtro-tipocompra" style="width:100%">
                                    <option value="">Todos</option>
                                    @foreach($arrayTipoCompra as $tc)
                                        <option value="{{ $tc->id }}">{{ $tc->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="font-weight-bold">Proveedor</label>
                                <select class="form-control" id="filtro-proveedor" style="width:100%">
                                    <option value="">Todos</option>
                                    @foreach($arrayProveedores as $pv)
                                        <option value="{{ $pv->id }}">{{ $pv->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div style="width:100%">
                                    <button class="btn btn-primary btn-block mb-1" onclick="recargar()">
                                        <i class="fas fa-search mr-1"></i> Filtrar
                                    </button>
                                    <button class="btn btn-secondary btn-block" onclick="limpiarFiltros()">
                                        <i class="fas fa-times mr-1"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold">Buscar por material</label>
                                <input type="text" class="form-control" id="filtro-material"
                                       placeholder="Nombre del material...">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold">Factura/Lote</label>
                                <input type="text" class="form-control" id="filtro-factura"
                                       placeholder="">
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>

        {{-- ══ TABLA ══ --}}
        <section class="content">
            <div class="container-fluid">
                <div class="card card-blue">
                    <div class="card-header">
                        <h3 class="card-title">Listado de Entradas</h3>
                    </div>
                    <div class="card-body">
                        <div id="tablaDatatable"></div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- ══ Modal Editar Entrada ══ --}}
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-edit mr-2"></i>Editar Entrada
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formulario-editar">
                        <input type="hidden" id="id-editar">
                        <div class="form-group">
                            <label>Fecha <span class="text-danger">*</span></label>
                            <input type="date" id="fecha-editar" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Tipo de Compra <span class="text-danger">*</span></label>
                            <select id="select-tipocompra-editar" class="form-control" style="width:100%">
                                <option value="">Seleccione...</option>
                                @foreach($arrayTipoCompra as $tc)
                                    <option value="{{ $tc->id }}">{{ $tc->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Proveedor <small class="text-muted">(Opcional)</small></label>
                            <select id="select-proveedor-editar" class="form-control" style="width:100%">
                                <option value="">Sin proveedor</option>
                                @foreach($arrayProveedores as $pv)
                                    <option value="{{ $pv->id }}">{{ $pv->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Factura <small class="text-muted">(Opcional)</small></label>
                            <input type="text" id="factura-editar" class="form-control" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label>Descripción <small class="text-muted">(Opcional)</small></label>
                            <textarea id="descripcion-editar" class="form-control" rows="3" maxlength="800"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" onclick="editar()">
                        <i class="fas fa-save mr-1"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Modal Detalle Entrada ══ --}}
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-list mr-2"></i>
                        Detalle — <span id="detalle-titulo"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="detalle-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                    <div id="detalle-contenido" style="display:none;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm">
                                <thead class="thead-dark">
                                <tr>
                                    <th style="width:4%">#</th>
                                    <th>Material</th>
                                    <th style="width:16%">Detalle (opcional)</th>
                                    <th style="width:10%" class="text-center">Cantidad</th>
                                    <th style="width:13%" class="text-right">Precio unit.</th>
                                    <th style="width:13%" class="text-right">Subtotal</th>
                                    <th style="width:12%" class="text-center">Acciones</th>
                                </tr>
                                </thead>
                                <tbody id="detalle-tbody"></tbody>
                                <tfoot>
                                <tr class="table-dark">
                                    <th colspan="5" class="text-right">Total</th>
                                    <th class="text-right" id="detalle-total">$0.00</th>
                                    <th></th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div id="detalle-vacio" class="text-center text-muted py-4" style="display:none;">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>Esta entrada no tiene materiales registrados.</p>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <a id="btn-agregar-extras" href="#" class="btn btn-success btn-sm">
                        <i class="fas fa-plus mr-1"></i> Agregar extras
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Modal Editar Detalle ══ --}}
    <div class="modal fade" id="modalEditarDetalle" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-edit mr-2"></i>Editar Material
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formulario-editar-detalle">
                        <input type="hidden" id="detalle-id-editar">
                        <div class="form-group">
                            <label>Material</label>
                            <input type="text" id="detalle-material-editar" class="form-control" disabled>
                        </div>
                        <div class="form-group">
                            <label>
                                Cantidad <span class="text-danger">*</span>
                                <small id="detalle-cantidad-aviso" class="text-danger ml-1" style="display:none;">
                                    (no editable — tiene salidas)
                                </small>
                            </label>
                            <input type="number" id="detalle-cantidad-editar" class="form-control"
                                   min="1" max="1000000" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label>Detalle (opcional) <small class="text-muted">(Opcional)</small></label>
                            <input type="text" id="detalle-codigo-editar" autocomplete="off" class="form-control" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label>Precio unitario <span class="text-danger">*</span></label>
                            <input type="number" id="detalle-precio-editar" class="form-control"
                                   step="0.0001" min="0" placeholder="0.0000">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" onclick="editarDetalle()">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}" type="text/javascript"></script>

    <script>
        var _entradaIdActual     = null;
        var _entradaTituloActual = '';

        $(function () {
            const ruta = "{{ url('/admin/historial/entradas/tabla') }}";

            // ── DataTable ─────────────────────────────────────────
            function initDataTable() {
                if ($.fn.DataTable.isDataTable('#tabla')) {
                    $('#tabla').DataTable().destroy();
                }
                $('#tabla').DataTable({
                    paging: true,
                    lengthChange: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    autoWidth: false,
                    responsive: true,
                    pagingType: "full_numbers",
                    lengthMenu: [[50, 100, -1], [50, 100, "Todo"]],
                    language: {
                        sProcessing:   "Procesando...",
                        sLengthMenu:   "Mostrar _MENU_ registros",
                        sZeroRecords:  "No se encontraron resultados",
                        sEmptyTable:   "Ningún dato disponible en esta tabla",
                        sInfo:         "Mostrando _START_ a _END_ de _TOTAL_ registros",
                        sInfoEmpty:    "Mostrando 0 a 0 de 0 registros",
                        sInfoFiltered: "(filtrado de _MAX_ registros)",
                        sSearch:       "Buscar:",
                        oPaginate: {
                            sFirst: "Primero", sLast: "Último",
                            sNext: "Siguiente", sPrevious: "Anterior"
                        }
                    },
                    dom:
                        "<'row align-items-center'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 text-md-right'f>>" +
                        "tr" +
                        "<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
                });
                $('#tabla_length select').addClass('form-control form-control-sm');
                $('#tabla_filter input').addClass('form-control form-control-sm').css('display', 'inline-block');
            }

            // ── Cargar tabla ──────────────────────────────────────
            function cargarTabla() {

                const fechaDesde = $('#filtro-fecha-desde').val();
                const fechaHasta = $('#filtro-fecha-hasta').val();
                const tipocompra = $('#filtro-tipocompra').val();
                const proveedor  = $('#filtro-proveedor').val();
                const material   = $('#filtro-material').val().trim();
                const factura    = $('#filtro-factura').val().trim();

                const params = new URLSearchParams();

                if (fechaDesde) params.append('fecha_desde', fechaDesde);
                if (fechaHasta) params.append('fecha_hasta', fechaHasta);
                if (tipocompra) params.append('tipocompra', tipocompra);
                if (proveedor)  params.append('proveedor', proveedor);
                if (material)   params.append('material', material);
                if (factura)    params.append('factura', factura);

                const url = params.toString()
                    ? ruta + '?' + params.toString()
                    : ruta;

                $('#tablaDatatable').load(url, function () {
                    initDataTable();
                });
            }

            cargarTabla();

            window.recargar = function () {
                cargarTabla();
            };

            window.limpiarFiltros = function () {
                $('#filtro-fecha-desde').val('');
                $('#filtro-fecha-hasta').val('');
                $('#filtro-tipocompra').val('').trigger('change');
                $('#filtro-proveedor').val('').trigger('change');
                $('#filtro-material').val('');   // NUEVO
                $('#filtro-factura').val('');    // NUEVO
                cargarTabla();
            };

            cargarTabla();

            // ── Select2 ───────────────────────────────────────────
            $('#select-tipocompra-editar').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalEditar'),
                language: { noResults: function () { return 'No encontrado'; } }
            });

            $('#filtro-tipocompra').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('body'),
                language: { noResults: function () { return 'No encontrado'; } }
            });

            // Agrega Select2 para proveedor en el $(function(){...}) junto a los otros select2:
            $('#select-proveedor-editar').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalEditar'),
                language: { noResults: function () { return 'No encontrado'; } }
            });

            $('#filtro-proveedor').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('body'),
                language: { noResults: function () { return 'No encontrado'; } }
            });

            // ── Delegación botones detalle ────────────────────────
            $(document).on('click', '.btn-editar-detalle', function () {
                const btn = $(this);
                modalEditarDetalle(
                    btn.data('id'),
                    btn.data('material'),
                    btn.data('codigo'),
                    btn.data('precio'),
                    btn.data('cantidad'),
                    btn.data('tiene-salidas') == 1
                );
            });

            $(document).on('click', '.btn-eliminar-detalle', function () {
                eliminarDetalle($(this).data('id'), $(this).data('material'));
            });
        });

        // ── Editar cabecera ───────────────────────────────────────
        function modalEditar(id) {
            openLoading();
            document.getElementById('formulario-editar').reset();

            axios.post(urlAdmin + '/admin/historial/entradas/informacion', { id: id })
                .then(function (response) {
                    closeLoading();
                    if (response.data.success === 1) {
                        const e = response.data.entrada;
                        $('#id-editar').val(e.id);
                        $('#fecha-editar').val(e.fecha);
                        $('#factura-editar').val(e.lote ?? '');
                        $('#descripcion-editar').val(e.descripcion ?? '');
                        $('#select-tipocompra-editar').val(e.id_tipocompra).trigger('change');
                        $('#select-proveedor-editar').val(e.id_proveedor ?? '').trigger('change');
                        $('#modalEditar').modal('show');
                    } else {
                        toastr.error('No se pudo cargar la información');
                    }
                })
                .catch(function () { closeLoading(); toastr.error('Error al obtener información'); });
        }

        function editar() {
            const id          = $('#id-editar').val();
            const fecha       = $('#fecha-editar').val().trim();
            const tipocompra  = $('#select-tipocompra-editar').val();
            const proveedor   = $('#select-proveedor-editar').val();
            const factura     = $('#factura-editar').val().trim();
            const descripcion = $('#descripcion-editar').val().trim();

            if (!fecha)      { toastr.error('La fecha es requerida');       return; }
            if (!tipocompra) { toastr.error('Tipo de Compra es requerido'); return; }

            openLoading();
            const formData = new FormData();
            formData.append('id',            id);
            formData.append('fecha',         fecha);
            formData.append('id_tipocompra', tipocompra);
            formData.append('id_proveedor',  proveedor);
            formData.append('factura',       factura);
            formData.append('descripcion',   descripcion);

            axios.post(urlAdmin + '/admin/historial/entradas/editar', formData)
                .then(function (response) {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Entrada actualizada correctamente');
                        $('#modalEditar').modal('hide');
                        recargar();
                    } else {
                        toastr.error('Error al actualizar');
                    }
                })
                .catch(function () { closeLoading(); toastr.error('Error al actualizar'); });
        }

        // ── Eliminar entrada ──────────────────────────────────────
        function eliminar(id) {
            Swal.fire({
                title: '¿Eliminar entrada?',
                text: 'Se eliminarán también todos los materiales asociados.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (result.value) {
                    openLoading();
                    axios.post(urlAdmin + '/admin/historial/entradas/eliminar', { id: id })
                        .then(function (response) {
                            closeLoading();
                            switch (response.data.success) {
                                case 1:
                                    toastr.success('Entrada eliminada correctamente');
                                    recargar();
                                    break;
                                case 2:
                                    Swal.fire({
                                        title: 'No se puede eliminar',
                                        text: response.data.msg,
                                        type: 'warning',
                                        confirmButtonColor: '#d33',
                                        confirmButtonText: 'Entendido'
                                    });
                                    break;
                                case 0:
                                    toastr.error('La entrada no existe');
                                    recargar();
                                    break;
                                default:
                                    toastr.error('Error al eliminar');
                            }
                        })
                        .catch(function () { closeLoading(); toastr.error('Error al eliminar'); });
                }
            });
        }

        // ── Ver detalle ───────────────────────────────────────────
        function verDetalle(id, titulo) {
            _entradaIdActual     = id;
            _entradaTituloActual = titulo;

            $('#detalle-titulo').text(titulo);
            $('#btn-agregar-extras').attr('href', urlAdmin + '/admin/historial/entradas/extras/' + id);
            $('#detalle-tbody').html('');
            $('#detalle-total').text('$0.00');
            $('#detalle-contenido').hide();
            $('#detalle-vacio').hide();
            $('#detalle-loading').show();
            $('#modalDetalle').modal('show');

            axios.post(urlAdmin + '/admin/historial/entradas/detalle', { id: id })
                .then(function (response) {
                    $('#detalle-loading').hide();
                    if (response.data.success === 1 && response.data.detalle.length > 0) {
                        let html         = '';
                        let totalGeneral = 0;

                        response.data.detalle.forEach(function (fila, index) {
                            const subtotal = parseFloat(fila.precio_raw) * parseFloat(fila.cantidad_inicial);
                            totalGeneral  += subtotal;

                            html += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${fila.material}</td>
                                    <td>${fila.codigo}</td>
                                    <td class="text-center">${fila.cantidad_inicial}</td>
                                    <td class="text-right">$${fila.precio}</td>
                                    <td class="text-right">$${subtotal.toLocaleString('en-US', {minimumFractionDigits:4, maximumFractionDigits:4})}</td>
                                    <td class="text-center text-nowrap">
                                        <button type="button"
                                                class="btn btn-warning btn-xs btn-editar-detalle mr-1"
                                                data-id="${fila.id}"
                                                data-material="${fila.material}"
                                                data-codigo="${fila.codigo}"
                                                data-precio="${fila.precio_raw}"
                                                data-cantidad="${fila.cantidad_inicial}"
                                                data-tiene-salidas="${fila.tiene_salidas}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-danger btn-xs btn-eliminar-detalle"
                                                data-id="${fila.id}"
                                                data-material="${fila.material}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>`;
                        });

                        $('#detalle-total').text('$' + totalGeneral.toLocaleString('en-US', {minimumFractionDigits:4, maximumFractionDigits:4}));
                        $('#detalle-tbody').html(html);
                        $('#detalle-contenido').show();
                    } else {
                        $('#detalle-vacio').show();
                    }
                })
                .catch(function () {
                    $('#detalle-loading').hide();
                    $('#detalle-vacio').show();
                    toastr.error('Error al cargar el detalle');
                });
        }

        function recargarDetalle() {
            if (_entradaIdActual) {
                verDetalle(_entradaIdActual, _entradaTituloActual);
            }
        }

        // ── Editar detalle ────────────────────────────────────────
        function modalEditarDetalle(id, material, codigo, precio, cantidad, tieneSalidas) {
            document.getElementById('formulario-editar-detalle').reset();
            $('#detalle-id-editar').val(id);
            $('#detalle-material-editar').val(material);
            $('#detalle-codigo-editar').val(codigo);
            $('#detalle-precio-editar').val(precio);
            $('#detalle-cantidad-editar').val(cantidad);

            if (tieneSalidas) {
                $('#detalle-cantidad-editar').prop('disabled', true);
                $('#detalle-cantidad-aviso').show();
            } else {
                $('#detalle-cantidad-editar').prop('disabled', false);
                $('#detalle-cantidad-aviso').hide();
            }

            $('#modalEditarDetalle').modal('show');
        }

        function editarDetalle() {
            const id       = $('#detalle-id-editar').val();
            const codigo   = $('#detalle-codigo-editar').val().trim();
            const precio   = $('#detalle-precio-editar').val().trim();
            const cantidad = $('#detalle-cantidad-editar').val();
            const disabled = $('#detalle-cantidad-editar').prop('disabled');

            if (precio === '' || isNaN(precio) || parseFloat(precio) < 0) {
                toastr.error('Precio inválido'); return;
            }
            if (!disabled && (cantidad === '' || parseInt(cantidad) <= 0)) {
                toastr.error('Cantidad debe ser mayor a 0'); return;
            }

            openLoading();
            const formData = new FormData();
            formData.append('id',     id);
            formData.append('codigo', codigo);
            formData.append('precio', precio);
            if (!disabled) {
                formData.append('cantidad', cantidad);
            }

            axios.post(urlAdmin + '/admin/historial/entradas/detalle/editar', formData)
                .then(function (response) {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Actualizado correctamente');
                        $('#modalEditarDetalle').modal('hide');
                        recargarDetalle();
                    } else if (response.data.success === 2) {
                        Swal.fire({
                            title: 'No se puede modificar',
                            text: response.data.msg,
                            type: 'warning',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        toastr.error('Error al actualizar');
                    }
                })
                .catch(function () { closeLoading(); toastr.error('Error al actualizar'); });
        }

        // ── Eliminar detalle ──────────────────────────────────────
        function eliminarDetalle(id, material) {
            Swal.fire({
                title: '¿Eliminar material?',
                html: `Se eliminará: <b>${material}</b><br><small class="text-muted">Si es el último material, la entrada también será eliminada.</small>`,
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (result.value) {
                    openLoading();
                    axios.post(urlAdmin + '/admin/historial/entradas/detalle/eliminar', { id: id })
                        .then(function (response) {
                            closeLoading();
                            switch (response.data.success) {
                                case 1:
                                    if (response.data.entrada_borrada) {
                                        toastr.success('Material eliminado. La entrada fue eliminada por quedar vacía.');
                                        $('#modalDetalle').modal('hide');
                                        recargar();
                                    } else {
                                        toastr.success('Material eliminado correctamente');
                                        recargarDetalle();
                                        recargar();
                                    }
                                    break;
                                case 4:
                                    Swal.fire({
                                        title: 'No se puede eliminar',
                                        text: response.data.msg,
                                        type: 'warning',
                                        confirmButtonColor: '#d33',
                                        confirmButtonText: 'Entendido'
                                    });
                                    break;
                                case 0:
                                    toastr.error('El material no existe o ya fue eliminado');
                                    break;
                                default:
                                    toastr.error('Error al eliminar');
                            }
                        })
                        .catch(function () { closeLoading(); toastr.error('Error al eliminar'); });
                }
            });
        }
    </script>
@endsection
