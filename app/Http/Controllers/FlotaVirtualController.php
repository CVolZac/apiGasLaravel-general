<?php

namespace App\Http\Controllers;

use App\Models\FlotaVirtual;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FlotaVirtualController extends Controller
{
  // GET /v1/flota-virtual?page=1&tipo_contenedor=&producto=&estatus=
  public function index(Request $request)
  {
    $q = FlotaVirtual::query()
      ->when($request->tipo_contenedor, fn($x,$v) => $x->where('tipo_contenedor',$v))
      ->when($request->producto,       fn($x,$v) => $x->where('producto_clave',$v))
      ->when($request->estatus,        fn($x,$v) => $x->where('estatus',$v))
      ->orderBy('id','desc')
      ->paginate(25);

    return response()->json($q);
  }

  // GET /v1/flota-virtual/{id}
  public function show($id)
  {
    $item = FlotaVirtual::findOrFail($id);
    return response()->json($item);
  }

  // POST /v1/flota-virtual
  public function store(Request $request)
  {
    $data = $request->validate([
      'clave_contenedor' => ['required','string','max:80','unique:flota_virtual,clave_contenedor'],
      'tipo_contenedor'  => ['required', Rule::in(['AUTOTANQUE_VIRTUAL','DUCTO_VIRTUAL','TANQUE_VIRTUAL'])],
      'producto_clave'   => ['required','string','max:20'],
      'capacidad_valor'  => ['nullable','numeric','min:0'],
      'capacidad_um'     => ['nullable','string','max:20'],
      'estatus'          => ['required', Rule::in(['activo','inactivo'])],
      'descripcion'      => ['nullable','string','max:255'],
    ]);

    $item = FlotaVirtual::create($data);
    return response()->json($item, 201);
  }

  // POST /v1/flota-virtual/{id}
  public function update($id, Request $request)
  {
    $item = FlotaVirtual::findOrFail($id);

    $data = $request->validate([
      'clave_contenedor' => ['required','string','max:80', Rule::unique('flota_virtual','clave_contenedor')->ignore($item->id)],
      'tipo_contenedor'  => ['required', Rule::in(['AUTOTANQUE_VIRTUAL','DUCTO_VIRTUAL','TANQUE_VIRTUAL'])],
      'producto_clave'   => ['required','string','max:20'],
      'capacidad_valor'  => ['nullable','numeric','min:0'],
      'capacidad_um'     => ['nullable','string','max:20'],
      'estatus'          => ['required', Rule::in(['activo','inactivo'])],
      'descripcion'      => ['nullable','string','max:255'],
    ]);

    $item->update($data);
    return response()->json($item);
  }

  // DELETE /v1/flota-virtual/{id}
  public function destroy($id)
  {
    $item = FlotaVirtual::findOrFail($id);
    $item->delete();
    return response()->json(['message' => 'Eliminado']);
  }
}
