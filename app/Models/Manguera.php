<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manguera extends Model
{
    protected $table = 'mangueras';

    protected $fillable = [
        'id_dispensario',
        'identificador_manguera',
        'estado',
    ];

    protected $casts = [
        'id_dispensario' => 'integer',
    ];

    public function dispensario()
    {
        return $this->belongsTo(Dispensario::class, 'id_dispensario');
    }
}
