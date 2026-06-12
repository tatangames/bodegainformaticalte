@extends('adminlte::page')

@section('title', 'Registro de Entradas')

@section('content_header')
    <h1>Registro de Entradas</h1>
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
        *:focus { outline: none; }
    </style>

    <div id="divcontenedor">

        {{-- ══ Card Información ══ --}}
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-10">
                        <div class="card card-gray-dark">
                            <div class="card-header">
                                <h3 class="card-title">Información de Ingreso</h3>
                            </div>
                            <div class="card-body">

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Fecha: <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="fecha">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Lote / Factura (Opcional):</label>
                                            <input type="text" class="form-control" autocomplete="off"
                                                   maxlength="100" id="factura"
                                                   placeholder="Ej: FAC-001">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label>Tipo de Compra: <span class="text-danger">*</span></label>
                                            <select class="form-control" id="select-tipocompra" style="width:100%">
                                                <option value="">Seleccione...</option>
                                                @foreach($arrayTipoCompra as $tc)
                                                    <option value="{{ $tc->id }}">{{ $tc->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label>Proveedor: <span class="text-danger">*</span></label>
                                            <select class="form-control" id="select-proveedor" style="width:100%">
                                                <option value="">Seleccione...</option>
                                                @foreach($arrayProveedor as $tc)
                                                    <option value="{{ $tc->id }}">{{ $tc->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>Descripción (Opcional):</label>
                                            <input type="text" class="form-control" autocomplete="off"
                                                   maxlength="800" id="descripcion">
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end justify-content-end">
                                        <div class="form-group">
                                            <button type="button" onclick="abrirModal()" class="btn btn-primary btn-sm">
                                                <i class="fas fa-plus"></i> Agregar Material
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══ Modal Agregar Material ══ --}}
        <div class="modal fade" id="modalRepuesto">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Agregar Material</h4>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario-repuesto">
                            <div class="card-body">

                                <div class="form-group">
                                    <label>Material <span class="text-danger">*</span></label>
                                    <table class="table" id="matriz-busqueda">
                                        <tbody>
                                        <tr>
                                            <td>
                                                <input id="repuesto" data-info="0" data-nombre=""
                                                       autocomplete="off" class="form-control"
                                                       style="width:100%"
                                                       onkeyup="buscarMaterial(this)"
                                                       maxlength="400" type="text"
                                                       placeholder="Buscar material...">
                                                <div class="droplista" style="position:absolute;z-index:9;width:75%!important;"></div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Cantidad <span class="text-danger">*</span></label>
                                            <input type="number" id="cantidad" min="1" max="1000000"
                                                   class="form-control" autocomplete="off" placeholder="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Precio (4 decimales) <span class="text-danger">*</span></label>
                                            <input type="text" id="precio-producto" inputmode="decimal"
                                                   class="form-control" autocomplete="off" placeholder="0.0000"
                                                   onkeypress="return validarDecimal(event, this)"
                                                   oninput="limitarDecimales(this, 4)">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Detalle (Opcional):</label>
                                            <input type="text" id="codigo" maxlength="100"
                                                   class="form-control" autocomplete="off">
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="agregarFila()">Agregar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ Tabla Detalle ══ --}}
        <section class="content-header">
            <div class="row mb-2">
                <div class="col-sm-6"><h2>Detalle de Ingreso</h2></div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card card-gray-dark">
                    <div class="card-header">
                        <h3 class="card-title">Materiales agregados</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0" id="matriz">
                                <thead>
                                <tr>
                                    <th style="width:5%">#</th>
                                    <th style="width:38%">Material</th>
                                    <th style="width:12%">Cantidad</th>
                                    <th style="width:15%">Código/Detalle</th>
                                    <th style="width:15%">Precio</th>
                                    <th style="width:15%">Opciones</th>
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
                <i class="fas fa-save mr-1"></i> Guardar Entrada
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
        var seguroBuscador = true;
        var txtContenedorGlobal = null;

        $(function () {
            // Fecha hoy
            var hoy = new Date();
            document.getElementById('fecha').value = hoy.toJSON().slice(0, 10);

            // Select2
            $('#select-tipocompra').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('body'),
                language: { noResults: function () { return 'No encontrado'; } }
            });

            $('#select-proveedor').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('body'),
                language: { noResults: function () { return 'No encontrado'; } }
            });

            // Cerrar droplista al click fuera
            $(document).click(function () {
                $('.droplista').hide();
            });
        });

        // Solo permite números y un único punto decimal
        function validarDecimal(e, input) {
            var char = String.fromCharCode(e.which);
            var valor = input.value;

            if (e.which === 0) return true; // teclas especiales (backspace, etc)

            if (char === '.') {
                // Solo un punto permitido
                if (valor.indexOf('.') !== -1) return false;
                return true;
            }

            if (!/[0-9]/.test(char)) return false;

            return true;
        }

        // Limita la cantidad de decimales mientras escribe
        function limitarDecimales(input, max) {
            var valor = input.value;

            // Eliminar cualquier caracter que no sea número o punto
            valor = valor.replace(/[^0-9.]/g, '');

            // Evitar más de un punto
            var partes = valor.split('.');
            if (partes.length > 2) {
                valor = partes[0] + '.' + partes.slice(1).join('');
                partes = valor.split('.');
            }

            // Limitar decimales
            if (partes[1] && partes[1].length > max) {
                partes[1] = partes[1].substring(0, max);
                valor = partes[0] + '.' + partes[1];
            }

            input.value = valor;
        }

        // ── Modal ─────────────────────────────────────────────────────
        function abrirModal() {
            document.getElementById('formulario-repuesto').reset();
            $('#repuesto').attr('data-info', '0').attr('data-nombre', '');
            $('#modalRepuesto').modal({ backdrop: 'static', keyboard: false });
        }

        // ── Agregar fila a tabla ──────────────────────────────────────
        function agregarFila() {
            var repuesto    = document.getElementById('repuesto');
            var idMaterial  = repuesto.dataset.info;
            var nombreMat   = repuesto.dataset.nombre || repuesto.value.trim();
            var cantidad    = document.getElementById('cantidad').value;
            var codigo      = document.getElementById('codigo').value;
            var precio      = document.getElementById('precio-producto').value;

            var reglaEntero  = /^[0-9]\d*$/;
            var reglaDecimal = /^([0-9]+\.?[0-9]{0,4})$/;

            if (!idMaterial || idMaterial == 0 || idMaterial === '') {
                toastr.error('Seleccione un material de la lista'); return;
            }
            if (cantidad === '' || !cantidad.match(reglaEntero) || parseInt(cantidad) <= 0) {
                toastr.error('Cantidad debe ser un entero mayor a 0'); return;
            }
            if (parseInt(cantidad) > 1000000) {
                toastr.error('Cantidad máximo 1 millón'); return;
            }
            if (precio === '' || !precio.match(reglaDecimal) || parseFloat(precio) < 0) {
                toastr.error('Precio debe ser un número decimal no negativo'); return;
            }
            if (parseFloat(precio) > 9000000) {
                toastr.error('Precio máximo 9 millones'); return;
            }

            // Verificar duplicado
            var duplicado = false;
            $('#matriz tbody tr').each(function () {
                if ($(this).find('input[name="descripcionArray[]"]').attr('data-info') == idMaterial) {
                    duplicado = true;
                }
            });
            if (duplicado) {
                toastr.warning('Este material ya fue agregado'); return;
            }

            var nFilas = $('#matriz tbody tr').length + 1;

            var fila = `
                <tr>
                    <td><span class="num-fila">${nFilas}</span></td>
                    <td>
                        <input name="descripcionArray[]" type="hidden"
                               data-info="${idMaterial}" data-nombre="${nombreMat}">
                        ${nombreMat}
                    </td>
                    <td>
                        <input name="cantidadArray[]" type="hidden" value="${cantidad}">
                        ${cantidad}
                    </td>
                    <td>
                        <input name="codigoArray[]" type="hidden" value="${codigo}">
                        ${codigo}
                    </td>
                    <td>
                        <input name="arrayPrecio[]" type="hidden" value="${precio}">
                        $${parseFloat(precio).toFixed(4)}
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm btn-block"
                                onclick="borrarFila(this)">
                            <i class="fas fa-trash"></i> Borrar
                        </button>
                    </td>
                </tr>`;

            $('#matriz tbody').append(fila);
            toastr.success('Material agregado');
            $('#modalRepuesto').modal('hide');

            document.getElementById('formulario-repuesto').reset();
            $('#repuesto').attr('data-info', '0').attr('data-nombre', '');
        }

        // ── Borrar fila ───────────────────────────────────────────────
        function borrarFila(btn) {
            $(btn).closest('tr').remove();
            renumerarFilas();
        }

        function renumerarFilas() {
            $('#matriz tbody tr').each(function (i) {
                $(this).find('.num-fila').text(i + 1);
            });
        }

        // ── Buscador material ─────────────────────────────────────────
        function buscarMaterial(e) {
            if (!seguroBuscador) return;
            seguroBuscador = false;
            txtContenedorGlobal = e;

            var texto = e.value;
            if (texto === '') {
                $(e).attr('data-info', 0);
                $('.droplista').hide();
                seguroBuscador = true;
                return;
            }

            axios.post(urlAdmin + '/admin/buscar/material', { query: texto })
                .then(function (response) {
                    seguroBuscador = true;
                    var row = $(e).closest('tr');
                    row.find('.droplista').fadeIn().html(response.data);
                })
                .catch(function () { seguroBuscador = true; });
        }

        function modificarValor(edrop) {
            var texto = $(edrop).text().trim();
            var id    = edrop.id;
            $(txtContenedorGlobal).val(texto)
                .attr('data-info', id)
                .attr('data-nombre', texto);
            $('.droplista').hide();
        }

        // ── Guardar ───────────────────────────────────────────────────
        function preguntaGuardar() {
            colorBlancoTabla();
            Swal.fire({
                title: '¿Guardar Entrada?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sí'
            }).then(function (result) {
                if (result.isConfirmed) guardarEntrada();
            });
        }

        function guardarEntrada() {
            var fecha       = document.getElementById('fecha').value;
            var factura     = document.getElementById('factura').value;
            var descripcion = document.getElementById('descripcion').value;
            var tipocompra  = document.getElementById('select-tipocompra').value;
            var proveedor  = document.getElementById('select-proveedor').value;

            if (!fecha)      { toastr.error('Fecha es requerida'); return; }
            if (!tipocompra) { toastr.error('Tipo de Compra es requerido'); return; }
            if (!proveedor) { toastr.error('Proveedor es requerido'); return; }

            var nFilas = $('#matriz tbody tr').length;
            if (nFilas === 0) { toastr.error('Agregue al menos un material'); return; }

            var reglaEntero  = /^[0-9]\d*$/;
            var reglaDecimal = /^([0-9]+\.?[0-9]{0,4})$/;

            var contenedorArray = [];
            var valido = true;

            $('#matriz tbody tr').each(function (i) {
                if (!valido) return;

                var idMaterial  = $(this).find('input[name="descripcionArray[]"]').attr('data-info');
                var nombre      = $(this).find('input[name="descripcionArray[]"]').attr('data-nombre');
                var infoCantidad = $(this).find('input[name="cantidadArray[]"]').val();
                var infoCodigo  = $(this).find('input[name="codigoArray[]"]').val();
                var infoPrecio  = $(this).find('input[name="arrayPrecio[]"]').val();

                if (!idMaterial || idMaterial == 0) {
                    colorRojoTabla(i);
                    toastr.error('Fila #' + (i + 1) + ': material inválido');
                    valido = false; return;
                }
                if (!infoCantidad.match(reglaEntero) || parseInt(infoCantidad) <= 0) {
                    colorRojoTabla(i);
                    toastr.error('Fila #' + (i + 1) + ': cantidad inválida');
                    valido = false; return;
                }
                if (!infoPrecio.match(reglaDecimal) || parseFloat(infoPrecio) < 0) {
                    colorRojoTabla(i);
                    toastr.error('Fila #' + (i + 1) + ': precio inválido');
                    valido = false; return;
                }

                contenedorArray.push({
                    idMaterial:   idMaterial,
                    infoNombre:   nombre,
                    infoCantidad: infoCantidad,
                    infoCodigo:   infoCodigo,
                    infoPrecio:   infoPrecio
                });
            });

            if (!valido) return;

            var formData = new FormData();
            formData.append('fecha',           fecha);
            formData.append('factura',         factura);
            formData.append('descripcion',     descripcion);
            formData.append('tipocompra',      tipocompra);
            formData.append('proveedor',      proveedor);
            formData.append('contenedorArray', JSON.stringify(contenedorArray));

            openLoading();
            axios.post(urlAdmin + '/admin/entradas/guardar', formData)
                .then(function (response) {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Registrado correctamente');
                        limpiar();
                    } else {
                        toastr.error('Error al guardar');
                    }
                })
                .catch(function () { closeLoading(); toastr.error('Error al guardar'); });
        }

        function colorRojoTabla(index) {
            $('#matriz tbody tr:eq(' + index + ')').css('background', '#F1948A');
        }

        function colorBlancoTabla() {
            $('#matriz tbody tr').css('background', 'white');
        }

        function limpiar() {
            document.getElementById('descripcion').value = '';
            document.getElementById('factura').value = '';
            $('#select-tipocompra').val('').trigger('change');
            $('#matriz tbody').empty();
        }
    </script>
@endsection
