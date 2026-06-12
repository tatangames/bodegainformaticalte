<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entradas extends Model
{
    use HasFactory;
    protected $table = 'entradas';
    public $timestamps = false;

    protected $fillable = [
        'id_tipocompra',
        'd_proveedor',
        'fecha',
        'descripcion',
        'lote',
    ];

    public function detalle()
    {
        return $this->hasMany(EntradasDetalle::class, 'id_entradas');
    }

    public function tipoCompra()
    {
        return $this->belongsTo(TipoCompra::class, 'id_tipocompra');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }
}
