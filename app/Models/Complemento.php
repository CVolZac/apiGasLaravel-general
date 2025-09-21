<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complemento extends Model
{
    protected $table = 'complementos';

    protected $fillable = [
        'evento_id',
        'permiso_transporte',
        'clave_vehiculo',
        'tarifa_transporte',
        'cargo_capacidad_trans',
        'cargo_uso_trans',
        'cargo_volumetrico_trans',
        'trasvase_nombre',
        'trasvase_rfc',
        'trasvase_permiso',
        'trasvase_descripcion',
        'trasvase_cfdi',
        'dictamen_rfc',
        'dictamen_lote',
        'dictamen_folio',
        'dictamen_fecha',
        'dictamen_resultado',
        'certificado_rfc',
        'certificado_folio',
        'certificado_fecha',
        'certificado_resultado',
        'aclaracion_texto',
    ];

    public function evento(): BelongsTo {
        return $this->belongsTo(EventoTanqueVirtual::class, 'evento_id');
    }
}
