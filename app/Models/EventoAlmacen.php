<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoAlmacen extends Model
{
    use HasFactory;

    protected $table = 'eventos_almacen';

    protected $fillable = [
        'id_almacen',
        'id_tanque',
        'id_producto',
        'lote',

        'tipo_evento',              // 'entrada' | 'salida'

        'volumen_inicial',
        'volumen_movido',          // compat
        'volumen_movido_vo',       // Observado
        'volumen_movido_vc',       // 20°C
        'volumen_final',
        'unidad',

        'fecha_inicio_evento',
        'fecha_fin_evento',

        'modo_medicion',           // 'estatica' | 'dinamica'
        'nivel',
        'temperatura',
        'presion_absoluta',
        'densidad',
        'caudal',
        'tiempo',
        'totalizador',

        'origen',                  // 'nacional' | 'extranjero'
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio_evento' => 'datetime',
        'fecha_fin_evento'    => 'datetime',

        'volumen_inicial'     => 'decimal:6',
        'volumen_movido'      => 'decimal:6',
        'volumen_movido_vo'   => 'decimal:6',
        'volumen_movido_vc'   => 'decimal:6',
        'volumen_final'       => 'decimal:6',

        'nivel'               => 'decimal:6',
        'temperatura'         => 'decimal:4',
        'presion_absoluta'    => 'decimal:6',
        'densidad'            => 'decimal:6',
        'caudal'              => 'decimal:6',
        'tiempo'              => 'decimal:6',
        'totalizador'         => 'decimal:6',
    ];

    /* ===== Relaciones ===== */

    public function almacen()
    {
        // Ajusta 'almacen' → 'almacenes' si tu tabla real cambia
        return $this->belongsTo(Almacen::class, 'id_almacen', 'id');
    }

    /**
     * Si tus tanques están en la misma tabla 'almacen', esta relación funciona.
     * Si tienes una tabla 'tanques', cambia a Tanque::class y FK adecuada.
     */
    public function tanque()
    {
        return $this->belongsTo(Almacen::class, 'id_tanque', 'id');
    }

    // CFDI (vía pivote evento_cfdi). Ajusta el modelo Cfdi si usas otro nombre.
    public function cfdis()
    {
        return $this->belongsToMany(Cfdi::class, 'evento_cfdi', 'evento_id', 'cfdi_id')
                    ->withPivot([
                        'tipo_cfdi', 'fecha_hora', 'precio_compra', 'contraprestacion',
                        'volumen_documentado_valor', 'volumen_documentado_unidad',
                        'monto_total', 'rfc_emisor', 'nombre_emisor', 'rfc_receptor',
                    ])->withTimestamps();
    }

    // Complementos (crea los modelos si aún no los tienes)
    public function complementoTransporte()   { return $this->hasOne(ComplementoTransporte::class, 'evento_id'); }
    public function complementoTrasvase()     { return $this->hasOne(ComplementoTrasvase::class, 'evento_id'); }
    public function complementoNacional()     { return $this->hasOne(ComplementoNacional::class, 'evento_id'); }
    public function complementoExtranjero()   { return $this->hasOne(ComplementoExtranjero::class, 'evento_id'); }

    /* ===== Scopes útiles ===== */

    public function scopeDeAlmacen($q, $idAlmacen)
    {
        return $q->where('id_almacen', $idAlmacen);
    }

    public function scopeEntreFechas($q, $desde = null, $hasta = null)
    {
        if ($desde) $q->where('fecha_inicio_evento', '>=', $desde);
        if ($hasta) $q->where('fecha_inicio_evento', '<=', $hasta);
        return $q;
    }

    public function scopeTipo($q, $tipo = null) // 'entrada' | 'salida'
    {
        if ($tipo) $q->where('tipo_evento', $tipo);
        return $q;
    }

    public function eventoCfdis()
{
    return $this->hasMany(EventoCfdi::class, 'evento_id');
}

}
