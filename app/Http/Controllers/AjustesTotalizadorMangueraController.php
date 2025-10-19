<?php

namespace App\Http\Controllers;

use App\Models\AjusteTotalizadorManguera;
use Illuminate\Http\Request;

class AjusteTotalizadorController extends Controller
{
    // POST v1/ajustes-totalizador
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_manguera' => ['required','integer','exists:mangueras,id'],
            'fecha_hora_ajuste' => ['required','date'],
            'lectura_anterior' => ['required','numeric','min:0'],
            'lectura_nueva' => ['required','numeric','min:0'],
            'aplica_desde' => ['required','date'],
            'motivo' => ['nullable','string'],
        ]);

        $row = AjusteTotalizadorManguera::create($validated);

        // Aquí podrías marcar cortes como "ajustados" o recalcular en un job.
        return response()->json(['message'=>'Ajuste guardado','id'=>$row->id,'data'=>$row], 201);
    }
}
