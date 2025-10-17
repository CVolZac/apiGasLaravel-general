<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subproducto extends Model
{
    use SoftDeletes;

    protected $table = 'subproductos';

    protected $fillable = [
        'id_planta',
        'producto_id',
        'clave_subproducto',
        'nombre_subproducto',
        'unidad_medida',
        'octanaje',
        'composicion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
