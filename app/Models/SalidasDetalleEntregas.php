<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalidasDetalleEntregas extends Model
{
    use HasFactory;
    protected $table = 'salidas_detalle_entregas';
    public $timestamps = false;
    protected $fillable = ['id_salida_detalle', 'id_departamento', 'numero_solicitud', 'cantidad', 'fecha_entrega', 'observacion'];

    public function salidaDetalle()
    {
        return $this->belongsTo(SalidasDetalle::class, 'id_salida_detalle', 'id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamentos::class, 'id_departamento', 'id');
    }

}
