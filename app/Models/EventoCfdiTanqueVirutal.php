<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoCfdiTanqueVirutal extends Model
{
    protected $table = 'evento_cfdi_tanque_virutal';

    protected $fillable = [
        'evento_id',
        'uuid',
        'tipo_cfdi',
        'precio',
        'fecha_hora_transaccion',
        'vol_doc_valor',
        'vol_doc_um',
    ];

    public function evento(): BelongsTo {
        return $this->belongsTo(EventoTanqueVirtual::class, 'evento_id');
    }
}
