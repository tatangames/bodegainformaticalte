@php
    $totalGeneral = $arraySalidas->sum('subtotal');
@endphp

<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover table-sm" id="tabla-historial">
        <thead class="thead-dark">
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Material</th>
            <th>Tipo Salida</th>
            <th>Departamento</th>
            <th>N° Solicitud</th>
            <th class="text-center">Cantidad</th>
            <th class="text-right">Precio Unit.</th>  {{-- NUEVO --}}
            <th class="text-right">Subtotal</th>      {{-- NUEVO --}}
            <th class="text-center">Estado</th>
            <th class="text-center">Entregas</th>
            <th class="text-center">Acciones</th>
        </tr>
        </thead>
        <tbody>
        @forelse($arraySalidas as $key => $salida)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($salida->fecha)->format('d-m-Y') }}</td>
                <td>{{ $salida->material }}</td>
                <td>{{ $salida->tipo_salida ?? '—' }}</td>
                <td>{{ $salida->departamento ?? '—' }}</td>
                <td>{{ $salida->numero_solicitud ?? '—' }}</td>
                <td class="text-center">{{ $salida->cantidad_salida }}</td>

                {{-- NUEVO: Precio unitario --}}
                <td class="text-right">
                    ${{ number_format($salida->precio ?? 0, 2) }}
                </td>

                {{-- NUEVO: Subtotal --}}
                <td class="text-right">
                    ${{ number_format($salida->subtotal ?? 0, 2) }}
                </td>

                <td class="text-center">
                    @if($salida->estado === 'finalizado')
                        <span class="badge badge-success">Finalizado</span>
                    @else
                        <span class="badge badge-warning">Pendiente</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($salida->total_entregas > 0)
                        <span class="badge badge-info">{{ $salida->total_entregas }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="text-center text-nowrap">
                    <button class="btn btn-xs btn-info" onclick="verDetalle({{ $salida->id }})" title="Ver detalle">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-xs btn-warning" onclick="modalEditar({{ $salida->id }})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-xs btn-danger" onclick="eliminar({{ $salida->id }})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="12" class="text-center text-muted py-3">
                    No se encontraron registros con los filtros aplicados.
                </td>
            </tr>
        @endforelse
        </tbody>

        {{-- NUEVO: Fila de total general --}}
        @if($arraySalidas->count() > 0)
            <tfoot>
            <tr class="table-dark font-weight-bold">
                <td colspan="8" class="text-right">TOTAL GENERAL:</td>
                <td class="text-right text-warning">
                    ${{ number_format($totalGeneral, 2) }}
                </td>
                <td colspan="3"></td>
            </tr>
            </tfoot>
        @endif
    </table>
</div>
