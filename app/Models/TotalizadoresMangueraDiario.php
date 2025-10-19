<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TotalizadorMangueraDia extends Model
{
    protected $table = 'totalizadores_manguera_diario';

    protected $fillable = [
        'id_manguera',
        'fecha',
        'id_subproducto',
        'totalizador_inicial_dia',
        'totalizador_final_dia',
        'volumen_entregado_dia',
        'observaciones',
    ];

    protected $casts = [
        'id_manguera' => 'integer',
        'id_subproducto' => 'integer',
        'fecha' => 'date:Y-m-d',
        'totalizador_inicial_dia' => 'decimal:3',
        'totalizador_final_dia' => 'decimal:3',
        'volumen_entregado_dia' => 'decimal:3',
    ];

    public function manguera() { return $this->belongsTo(\App\Models\Manguera::class, 'id_manguera'); }
}
