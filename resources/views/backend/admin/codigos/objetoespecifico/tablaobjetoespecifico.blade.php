<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <table id="tabla" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th style="width:8%">ID</th>
                                <th style="width:8%">Código</th>
                                <th style="width:25%">Nombre</th>
                                <th style="width:20%">Cuenta</th>
                                <th style="width:20%">Rubro</th>
                                <th style="width:10%">Opciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($lista as $dato)
                                <tr>
                                    <td>{{ $dato->id }}</td>
                                    <td>{{ $dato->codigo }}</td>
                                    <td>{{ $dato->nombre }}</td>
                                    <td>{{ $dato->cuenta->codigo }} — {{ $dato->cuenta->nombre }}</td>
                                    <td>{{ $dato->cuenta->rubro->codigo }} — {{ $dato->cuenta->rubro->nombre }}</td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-xs"
                                                onclick="informacion({{ $dato->id }})">
                                            <i class="fas fa-edit"></i>&nbsp;Editar
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
