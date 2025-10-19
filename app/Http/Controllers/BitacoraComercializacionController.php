<?php

namespace App\Http\Controllers;

use App\Models\BitacoraComercializacion;
use Illuminate\Http\Request;

class BitacoraComercializacionController extends Controller
{
  // GET /v1/bitacora-comercializacion?page=1
  public function index(Request $request)
  {
    $q = BitacoraComercializacion::orderBy('id','desc')->paginate(25);
    return response()->json($q);
  }

  // GET /v1/bitacora-comercializacion/{id}
  public function show($id)
  {
    $item = BitacoraComercializacion::findOrFail($id);
    return response()->json($item);
  }

  // POST /v1/bitacora-comercializacion
  public function store(Request $request)
  {
    $data = $request->validate([
      'fecha_hora_evento' => ['required','date'],
      'tipo_evento'       => ['required','integer'],
      'descripcion_evento'=> ['required','string','max:255'],
    ]);
    $item = BitacoraComercializacion::create($data);
    return response()->json($item, 201);
  }

  // POST /v1/bitacora-comercializacion/{id}
  public function update($id, Request $request)
  {
    $item = BitacoraComercializacion::findOrFail($id);
    $data = $request->validate([
      'fecha_hora_evento' => ['required','date'],
      'tipo_evento'       => ['required','integer'],
      'descripcion_evento'=> ['required','string','max:255'],
    ]);
    $item->update($data);
    return response()->json($item);
  }

  // DELETE /v1/bitacora-comercializacion/{id}
  public function destroy($id)
  {
    $item = BitacoraComercializacion::findOrFail($id);
    $item->delete();
    return response()->json(['message'=>'Eliminado']);
  }
}
