<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BitacoraEventos;

class BitacoraEventosController extends Controller
{
         /**
     * Display a listing of the resource.
     */
    public function index($idPlanta)
    {
        try {
            $data = BitacoraEventos::where('id_planta', $idPlanta)->get();
            return  response()->json($data);
        } catch (\Throwable $th) {
            return response()->json([ 'error' => $th->getMessage() ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            $data["id_planta"] = $request["id_planta"];

            $data["NumeroRegistro"] = $request["NumeroRegistro"];
            $data["FechaYHoraEvento"] = $request["FechaYHoraEvento"];
            $data["UsuarioResponsable"] = $request["UsuarioResponsable"];
            $data["TipoEvento"] = $request["TipoEvento"];
            $data["DescripcionEvento"] = $request["DescripcionEvento"];
            $data["IdentificacionComponenteAlarma"] = $request["IdentificacionComponenteAlarma"];
            $data["TipoEquipo"] = $request["TipoEquipo"];
            $data["EquipoRelacionado"] = $request["EquipoRelacionado"];


            $res = BitacoraEventos::create($data);
            return response()->json($res, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(BitacoraEventos $bitacoraEventos)
    {
        try {
            $data = BitacoraEventos::find($id);
            return response()->json($data, 200);
        } catch (\Throwable $th) {
            return response() -> json(['error'=>$th->getMessage()],500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BitacoraEventos $bitacoraEventos)
    {
        try {
            $data["NumeroRegistro"] = $request["NumeroRegistro"];
            $data["FechaYHoraEvento"] = $request["FechaYHoraEvento"];
            $data["UsuarioResponsable"] = $request["UsuarioResponsable"];
            $data["TipoEvento"] = $request["TipoEvento"];
            $data["DescripcionEvento"] = $request["DescripcionEvento"];
            $data["IdentificacionComponenteAlarma"] = $request["IdentificacionComponenteAlarma"];
            $data["TipoEquipo"] = $request["TipoEquipo"];
            $data["EquipoRelacionado"] = $request["EquipoRelacionado"];


            BitacoraEventos::find($request->id)->update($data);
            $res = BitacoraEventos::find($request->id);
            return response()->json($res, 200);
        } catch (\Throwable $th) {
            return response()->json([ 'error' => $th->getMessage() ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, BitacoraEventos $bitacoraEventos)
    {
        try {
            $res = BitacoraEventos::find($request->id)->delete();
            return response()->json(["deleted" => $res], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }
}
