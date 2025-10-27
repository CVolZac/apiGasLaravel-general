<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventosComercializacion extends Model
{
    protected $table = 'eventos_comercializacion';

    protected $fillable = [
        'flota_virtual_id',
        'contraparte_id',
        'contrato_id',
        'tipo_evento',
        'tipo_registro',
        'producto_clave',
        'ambito',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'fecha_hora_medicion',
        'temperatura',
        'presion_absoluta',
        'volumen_inicial_valor',
        'volumen_inicial_um',
        'volumen_movido_valor',
        'volumen_movido_um',
        'volumen_final_tanque',
        'existencias',
        'volumen_documentado_total',
        'volumen_documentado_um',
        'rfc_contraparte',
        'nombre_contraparte',
        'permiso_contraparte',
        'complemento',
        'estatus_validacion',
        'motivo_observacion',
        'version_esquema',
        'observaciones',
    ];

    protected $casts = [
        'fecha_hora_inicio' => 'datetime',
        'fecha_hora_fin' => 'datetime',
        'fecha_hora_medicion' => 'datetime',
        'temperatura' => 'decimal:3',
        'presion_absoluta' => 'decimal:3',
        'volumen_inicial_valor' => 'decimal:6',
        'volumen_movido_valor' => 'decimal:6',
        'volumen_final_tanque' => 'decimal:6',
        'volumen_documentado_total' => 'decimal:6',
        'existencias' => 'array',
        'complemento' => 'array',
    ];

    // Relaciones sugeridas (si ya tienes los modelos)
    public function cfdis()
    {
        return $this->hasMany(CfdisComercializador::class, 'evento_id');
    }
    public function pedimentos()
    {
        return $this->hasMany(PedimentosComercializador::class, 'evento_id');
    }
    public function servicios()
    {
        return $this->hasMany(ServiciosComercializador::class, 'evento_id');
    }
}
