<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'id_planta',
        'clave_producto',
        'nombre',
        'unidad_medida',
        'tipo_combustible',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function subproductos()
    {
        return $this->hasMany(Subproducto::class, 'producto_id');
    }
}
