<?php

namespace App\Http\Controllers;

use App\Models\BitacoraComercializacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BitacoraComercializacionController extends Controller
{
  // GET /v1/bitacora-comercializacion?entidad=&accion=&q=&desde=&hasta=
  public function index(Request $request)
  {
    $entidad = $request->get('entidad');
    $accion  = $request->get('accion');
    $q       = $request->get('q');
    $desde   = $request->get('desde');
    $hasta   = $request->get('hasta');

    $query = BitacoraComercializacion::query();

    if ($entidad) $query->where('entidad', $entidad);
    if ($accion)  $query->where('accion',  $accion);

    if ($q) {
      $qLower = mb_strtolower($q, 'UTF-8');
      $query->whereRaw('LOWER(descripcion) LIKE ?', ['%'.$qLower.'%']);
    }

    if ($desde) $query->whereDate('created_at', '>=', $desde);
    if ($hasta) $query->whereDate('created_at', '<=', $hasta);

    $data = $query->orderBy('id','desc')->paginate(25);
    return response()->json($data);
  }

  // POST /v1/bitacora-comercializacion
  public function store(Request $request)
  {
    $validated = $request->validate([
      'entidad'     => ['required','string','max:50', Rule::in(['contrato','contraparte','evento','reporte','complemento'])],
      'entidad_id'  => ['nullable','integer'],
      'accion'      => ['required','string','max:30', Rule::in(['create','update','delete','validate','generate','send','error'])],
      'descripcion' => ['nullable','string','max:255'],
      'payload'     => ['nullable','array'],
      'usuario_id'  => ['nullable','integer'],
    ]);

    $b = BitacoraComercializacion::create($validated);

    return response()->json([
      'message' => 'Registro creado en bitácora de comercialización',
      'id' => $b->id,
      'data' => $b,
    ], 201);
  }
}
