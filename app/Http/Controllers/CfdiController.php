<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cfdi;

class CfdiController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'evento_id' => 'required|exists:eventos_almacen,id',

        // estas llaves llegan desde el front en PascalCase
        'UUID'                 => 'required|string',
        'RFCEmisorCFDI'        => 'required|string',
        'NombreEmisorCFDI'     => 'nullable|string',
        'RFCProveedorReceptor' => 'nullable|string',
        'MontoTotalOperacion'  => 'nullable|numeric',
        'FechaCFDI'            => 'required|date',

        // si también te mandan estos, valídalos:
        'TipoComplemento'            => 'nullable|string',
        'Version'                    => 'nullable|string',
        'TipoCFDI'                   => 'nullable|string',
        'VolumenDocumentadoValor'    => 'nullable|numeric',
        'VolumenDocumentadoUnidad'   => 'nullable|string',
    ]);

    // Mapeo PascalCase (request) -> snake_case (DB)
    $cfdi = Cfdi::create([
        'evento_id'                   => $request->input('evento_id'),
        'tipo_complemento'            => $request->input('TipoComplemento', 'CFDI'),
        'version'                     => $request->input('Version', '1.0'),
        'uuid'                        => $request->input('UUID'),
        'rfc_emisor_cfdi'             => $request->input('RFCEmisorCFDI'),
        'nombre_emisor_cfdi'          => $request->input('NombreEmisorCFDI'),
        'rfc_proveedor_receptor'      => $request->input('RFCProveedorReceptor'),
        'monto_total_operacion'       => $request->input('MontoTotalOperacion'),
        'fecha_cfdi'                  => $request->input('FechaCFDI'),
        'tipo_cfdi'                   => $request->input('TipoCFDI'),
        'volumen_documentado_valor'   => $request->input('VolumenDocumentadoValor'),
        'volumen_documentado_unidad'  => $request->input('VolumenDocumentadoUnidad'),
    ]);

    return response()->json(['success' => true, 'cfdi' => $cfdi], 201);
}


    public function index($evento_id)
    {
        $cfdis = Cfdi::where('evento_id', $evento_id)->get();
        return response()->json($cfdis);
    }

    
}
