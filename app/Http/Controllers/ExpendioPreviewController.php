<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TotalizadorMangueraDia;
use App\Models\Manguera;

class ExpendioPreviewController extends Controller
{
    // GET v1/expendio/preview-json/{idPlanta}/{fecha}
    public function previewJSON($idPlanta, $fecha)
    {
        // Por cada manguera de la planta (que tenga corte ese día), arma el nodo de expendio
        $rows = TotalizadorMangueraDia::whereDate('fecha', $fecha)
            ->whereHas('manguera', fn($q)=> $q->whereHas('dispensario', fn($d)=>$d->where('id_planta', $idPlanta)))
            ->with(['manguera:id,id_dispensario,identificador_manguera,id_subproducto'])
            ->get();

        $expendio = $rows->map(function($r){
            return [
                'IdentificadorManguera' => $r->manguera->identificador_manguera,
                'Subproducto' => $r->id_subproducto ?? $r->manguera->id_subproducto,
                'TotalizadorInicialDia' => (float)$r->totalizador_inicial_dia,
                'TotalizadorFinalDia'   => (float)$r->totalizador_final_dia,
                'VolumenEntregadoDia'   => (float)$r->volumen_entregado_dia,
                'Fecha'                 => $r->fecha->format('Y-m-d'),
            ];
        })->values();

        // Estructura simple de previsualización; ajusta a tu formato final XML/JSON de Anexo 31
        $payload = [
            'Planta' => (int)$idPlanta,
            'Fecha' => $fecha,
            'Expendio' => [
                'Mangueras' => $expendio
            ]
        ];

        return response()->json($payload);
    }
}
