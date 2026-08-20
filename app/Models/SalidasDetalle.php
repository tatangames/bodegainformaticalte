<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalidasDetalle extends Model
{
    use HasFactory;
    protected $table = 'salidas_detalle';
    public $timestamps = false;
    protected $fillable = [
        'id_entrada_detalle',
        'id_departamento',
        'cantidad_salida',
        'estado',
        'fecha',
        'numero_solicitud',
        'descripcion',
    ];
    public function entradaDetalle()
    {
        return $this->belongsTo(EntradasDetalle::class, 'id_entrada_detalle', 'id');
    }

    public function material()
    {
        return $this->belongsTo(Materiales::class, 'id_material', 'id');
        // Ajusta: Material::class  → nombre real de tu modelo
        //         'id_material'    → FK en salidasdetalle
        //         'id'             → PK en la tabla materiales
    }

    public function isPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function entregas()
    {
        return $this->hasMany(SalidasDetalleEntregas::class, 'id_salida_detalle', 'id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamentos::class, 'id_departamento', 'id');
    }

    // En SalidasDetalle — agrega este método
    public function tipoSalida()
    {
        return $this->belongsTo(TipoSalida::class, 'id_tiposalida', 'id');
    }

}
