<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventoTanqueVirtual extends Model
{
    protected $table = 'evento_tanque_virtual';

    protected $fillable = [
        'tanque_virtual_id',
        'tipo_evento',
        'fecha_hora',
        'volumen_valor',
        'volumen_um',
        'es_cierre_diario',
        'es_consolidado_mensual',
        'observaciones',
        'geo_lat',
        'geo_lng',
    ];

    public function tanque(): BelongsTo {
        return $this->belongsTo(TanqueVirtual::class, 'tanque_virtual_id');
    }

    public function cfdis(): HasMany {
        return $this->hasMany(EventoCfdi::class, 'evento_id');
    }

    public function complementos(): HasOne {
        return $this->hasOne(Complemento::class, 'evento_id');
    }
}
