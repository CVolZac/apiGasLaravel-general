<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TanqueVirtual extends Model
{
    protected $table = 'tanque_virtual';

    protected $fillable = [
        'instalacion_id',
        'clave_identificacion_tanque',
        'producto_clave',
        'subproducto_clave',
        'cap_total',
        'cap_operativa',
        'cap_util',
        'cap_fondaje',
        'cap_gas_talon',
        'vol_min_operacion',
        'estado',
        'es_virtual',
        'vigencia_calibracion',
        'localizacion_descripcion',
    ];

    public function instalacion(): BelongsTo {
        return $this->belongsTo(ComercializadorInstalacion::class, 'instalacion_id');
    }

    public function eventos(): HasMany {
        return $this->hasMany(EventoTanqueVirtual::class, 'tanque_virtual_id');
    }
}
