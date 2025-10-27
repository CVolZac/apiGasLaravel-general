<?php

namespace App\Http\Controllers;

use App\Models\EventosComercializacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class EventosComercializacionController extends Controller
{
    public function index(Request $r)
    {
        try {
            $q = EventosComercializacion::orderBy('id','desc')->paginate(25);
            return response()->json($q);
        } catch (\Throwable $e) {
            Log::error('INDEX EVENTOS ERROR: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['message'=>'Error al listar eventos','error'=>$e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        return response()->json(EventosComercializacion::findOrFail($id));
    }

    public function store(Request $r)
    {
        $v = $r->validate([
            'flota_virtual_id' => ['required','integer','exists:flota_virtual,id'],
            'contraparte_id'   => ['nullable','integer'],
            'contrato_id'      => ['nullable','integer'],

            'tipo_evento'      => ['required', Rule::in(['RECEPCION','ENTREGA'])],
            'tipo_registro'    => ['required', Rule::in(['D','M'])],
            'producto_clave'   => ['required','string','max:20'],
            'ambito'           => ['nullable', Rule::in(['NACIONAL','EXTRANJERO','TRASLADO_INTERNO'])],

            'fecha_hora_inicio'=> ['nullable','date'],
            'fecha_hora_fin'   => ['nullable','date'],
            'fecha_hora_medicion' => ['nullable','date'],

            'temperatura'      => ['nullable','numeric'],
            'presion_absoluta' => ['nullable','numeric'],

            'volumen_inicial_valor' => ['nullable','numeric','min:0'],
            'volumen_inicial_um'    => ['nullable','string','max:10'],
            'volumen_movido_valor'  => ['nullable','numeric','min:0'],
            'volumen_movido_um'     => ['nullable','string','max:10'],
            'volumen_final_tanque'  => ['nullable','numeric','min:0'],

            'existencias'      => ['nullable','array'],

            'volumen_documentado_total' => ['nullable','numeric','min:0'],
            'volumen_documentado_um'    => ['nullable','string','max:10'],

            'rfc_contraparte'   => ['nullable','string','max:13'],
            'nombre_contraparte'=> ['nullable','string','max:255'],
            'permiso_contraparte'=>['nullable','string','max:50'],

            'complemento'      => ['nullable','array'],
            'estatus_validacion'=>['nullable', Rule::in(['PENDIENTE','VALIDO','OBSERVADO','RECHAZADO'])],
            'motivo_observacion'=>['nullable','string'],
            'version_esquema'  => ['nullable','string','max:40'],
            'observaciones'    => ['nullable','string'],
        ]);

        // Defaults guía
        $v['temperatura']      = $v['temperatura']      ?? 20.000;
        $v['presion_absoluta'] = $v['presion_absoluta'] ?? 101.325;
        $v['estatus_validacion']= $v['estatus_validacion'] ?? 'PENDIENTE';
        $v['version_esquema']   = $v['version_esquema']   ?? 'RMF2025-Anexo30';

        // Reglas rápidas (evita basura)
        if (($v['tipo_evento'] ?? null) === 'RECEPCION' && !in_array($v['ambito'] ?? null, ['NACIONAL','EXTRANJERO'], true)) {
            return response()->json(['message'=>'Recepción exige ambito NACIONAL o EXTRANJERO'], 422);
        }

        $ev = EventosComercializacion::create($v);
        return response()->json($ev, 201);
    }

    public function update($id, Request $r)
    {
        $ev = EventosComercializacion::findOrFail($id);

        $v = $r->validate([
            'flota_virtual_id' => ['sometimes','integer','exists:flota_virtual,id'],
            'contraparte_id'   => ['nullable','integer'],
            'contrato_id'      => ['nullable','integer'],

            'tipo_evento'      => ['sometimes', Rule::in(['RECEPCION','ENTREGA'])],
            'tipo_registro'    => ['sometimes', Rule::in(['D','M'])],
            'producto_clave'   => ['sometimes','string','max:20'],
            'ambito'           => ['nullable', Rule::in(['NACIONAL','EXTRANJERO','TRASLADO_INTERNO'])],

            'fecha_hora_inicio'=> ['nullable','date'],
            'fecha_hora_fin'   => ['nullable','date'],
            'fecha_hora_medicion' => ['nullable','date'],

            'temperatura'      => ['nullable','numeric'],
            'presion_absoluta' => ['nullable','numeric'],

            'volumen_inicial_valor' => ['nullable','numeric','min:0'],
            'volumen_inicial_um'    => ['nullable','string','max:10'],
            'volumen_movido_valor'  => ['nullable','numeric','min:0'],
            'volumen_movido_um'     => ['nullable','string','max:10'],
            'volumen_final_tanque'  => ['nullable','numeric','min:0'],

            'existencias'      => ['nullable','array'],

            'volumen_documentado_total' => ['nullable','numeric','min:0'],
            'volumen_documentado_um'    => ['nullable','string','max:10'],

            'rfc_contraparte'   => ['nullable','string','max:13'],
            'nombre_contraparte'=> ['nullable','string','max:255'],
            'permiso_contraparte'=>['nullable','string','max:50'],

            'complemento'      => ['nullable','array'],
            'estatus_validacion'=>['nullable', Rule::in(['PENDIENTE','VALIDO','OBSERVADO','RECHAZADO'])],
            'motivo_observacion'=>['nullable','string'],
            'version_esquema'  => ['nullable','string','max:40'],
            'observaciones'    => ['nullable','string'],
        ]);

        $ev->update($v);
        return response()->json($ev->refresh());
    }

    public function destroy($id)
    {
        $ev = EventosComercializacion::findOrFail($id);
        $ev->delete();
        return response()->json(['message'=>'Eliminado']);
    }
}
