<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cfdis;

class CfdiController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'evento_id' => 'required|exists:eventos_almacen,id',
            'UUID' => 'required|string',
            'RFCEmisorCFDI' => 'required|string',
            'NombreEmisorCFDI' => 'nullable|string',
            'RFCProveedorReceptor' => 'nullable|string',
            'MontoTotalOperacion' => 'nullable|numeric',
            'FechaCFDI' => 'required|date'
        ]);

        $cfdi = Cfdis::create([
            'evento_id' => $request->evento_id,
            'TipoComplemento' => 'CFDI',
            'Version' => '1.0',
            'UUID' => $request->UUID,
            'RFCEmisorCFDI' => $request->RFCEmisorCFDI,
            'NombreEmisorCFDI' => $request->NombreEmisorCFDI,
            'RFCProveedorReceptor' => $request->RFCProveedorReceptor,
            'MontoTotalOperacion' => $request->MontoTotalOperacion,
            'FechaCFDI' => $request->FechaCFDI,
        ]);

        return response()->json(['success' => true, 'cfdi' => $cfdi]);
    }

    public function index($evento_id)
    {
        $cfdis = Cfdis::where('evento_id', $evento_id)->get();
        return response()->json($cfdis);
    }

    
}
