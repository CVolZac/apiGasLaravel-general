<?php

namespace App\Http\Controllers;

use App\Models\EventoComercializacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventoComercializacionController extends Controller
{
  // GET /v1/eventos-comercio?page=1&tipo=&producto=&contenedor=&fecha_ini=&fecha_fin=&q=
  public function index(Request $r)
  {
    $q = EventoComercializacion::query()
      ->when($r->filled('tipo'), fn($x)=>$x->where('tipo_evento',$r->tipo))
      ->when($r->filled('producto'), fn($x)=>$x->where('producto_clave',$r->producto))
      ->when($r->filled('contenedor'), fn($x)=>$x->where('flota_virtual_id',$r->contenedor))
      ->when($r->filled('fecha_ini'), fn($x)=>$x->whereDate('fecha_hora_inicio','>=',$r->fecha_ini))
      ->when($r->filled('fecha_fin'), fn($x)=>$x->whereDate('fecha_hora_fin','<=',$r->fecha_fin))
      ->orderBy('id','desc')
      ->paginate(25);

    return response()->json($q);
  }

  // GET /v1/eventos-comercio/{id}
  public function show($id)
  {
    return response()->json(EventoComercializacion::findOrFail($id));
  }

  // POST /v1/eventos-comercio
  public function store(Request $r)
  {
    $base = $r->validate([
      'flota_virtual_id'     => ['required','integer','exists:flota_virtual,id'],
      'contraparte_id'       => ['nullable','integer'],
      'contrato_id'          => ['nullable','integer'],
      'tipo_evento'          => ['required', Rule::in(['RECEPCION','ENTREGA'])],
      'tipo_registro'        => ['required', Rule::in(['D','M'])],
      'producto_clave'       => ['required','string','max:20'],

      'fecha_hora_inicio'    => ['nullable','date'],
      'fecha_hora_fin'       => ['nullable','date'],

      'temperatura'          => ['nullable','numeric'],
      'presion_absoluta'     => ['nullable','numeric'],

      'volumen_inicial_valor'=> ['nullable','numeric','min:0'],
      'volumen_inicial_um'   => ['nullable','string','max:10'],
      'volumen_movido_valor' => ['nullable','numeric','min:0'],
      'volumen_movido_um'    => ['nullable','string','max:10'],
      'volumen_final_tanque' => ['nullable','numeric','min:0'],

      'existencias'          => ['nullable','array'],   // JSON Existencias
      'complemento'          => ['nullable','array'],   // JSON Complemento
      'observaciones'        => ['nullable','string'],
    ]);

    // Defaults guía (si no vienen)
    $base['temperatura']      = $base['temperatura'] ?? 20.000;
    $base['presion_absoluta'] = $base['presion_absoluta'] ?? 101.325;

    $ev = EventoComercializacion::create($base);
    return response()->json($ev, 201);
  }

  // POST /v1/eventos-comercio/{id}
  public function update($id, Request $r)
  {
    $ev = EventoComercializacion::findOrFail($id);

    $base = $r->validate([
      'flota_virtual_id'     => ['required','integer','exists:flota_virtual,id'],
      'contraparte_id'       => ['nullable','integer'],
      'contrato_id'          => ['nullable','integer'],
      'tipo_evento'          => ['required', Rule::in(['RECEPCION','ENTREGA'])],
      'tipo_registro'        => ['required', Rule::in(['D','M'])],
      'producto_clave'       => ['required','string','max:20'],

      'fecha_hora_inicio'    => ['nullable','date'],
      'fecha_hora_fin'       => ['nullable','date'],

      'temperatura'          => ['nullable','numeric'],
      'presion_absoluta'     => ['nullable','numeric'],

      'volumen_inicial_valor'=> ['nullable','numeric','min:0'],
      'volumen_inicial_um'   => ['nullable','string','max:10'],
      'volumen_movido_valor' => ['nullable','numeric','min:0'],
      'volumen_movido_um'    => ['nullable','string','max:10'],
      'volumen_final_tanque' => ['nullable','numeric','min:0'],

      'existencias'          => ['nullable','array'],
      'complemento'          => ['nullable','array'],
      'observaciones'        => ['nullable','string'],
    ]);

    $base['temperatura']      = $base['temperatura'] ?? 20.000;
    $base['presion_absoluta'] = $base['presion_absoluta'] ?? 101.325;

    $ev->update($base);
    return response()->json($ev);
  }

  // DELETE /v1/eventos-comercio/{id}
  public function destroy($id)
  {
    $ev = EventoComercializacion::findOrFail($id);
    $ev->delete();
    return response()->json(['message' => 'Eliminado']);
  }
}
