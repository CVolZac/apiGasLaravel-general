<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cfdi;
use Illuminate\Support\Facades\Validator;

class CfdiController extends Controller
{
    public function store(Request $request)
    {
        // 1) Normalizamos: tomamos primero PascalCase (lo que manda el front),
        // y si no viene, usamos snake_case por si algún cliente ya lo envía así.
        $data = [
            'evento_id'                  => $request->input('evento_id'),
            'tipo_complemento'           => $request->input('TipoComplemento', $request->input('tipo_complemento', 'CFDI')),
            'version'                    => $request->input('Version', $request->input('version', '1.0')),
            'uuid'                       => $request->input('UUID', $request->input('uuid')),
            'rfc_emisor'                 => $request->input('RFCEmisorCFDI', $request->input('rfc_emisor')),
            'nombre_emisor'              => $request->input('NombreEmisorCFDI', $request->input('nombre_emisor')),
            'rfc_receptor'               => $request->input('RFCProveedorReceptor', $request->input('rfc_receptor')),
            'monto_total'                => $request->input('MontoTotalOperacion', $request->input('monto_total')),
            'fecha_hora'                 => $request->input('FechaCFDI', $request->input('fecha_hora')),
            'tipo_cfdi'                  => $request->input('TipoCFDI', $request->input('tipo_cfdi')),
            'precio_compra'              => $request->input('PrecioCompra', $request->input('precio_compra')),
            'contraprestacion'           => $request->input('Contraprestacion', $request->input('contraprestacion')),
            'volumen_documentado_valor'  => $request->input('VolumenDocumentadoValor', $request->input('volumen_documentado_valor')),
            'volumen_documentado_unidad' => $request->input('VolumenDocumentadoUnidad', $request->input('volumen_documentado_unidad')),
        ];

        // 2) Reglas de validación con los nombres REALES de columnas
        $rules = [
            'evento_id' => 'required|exists:eventos_almacen,id',

            'uuid'       => 'required|string',
            'rfc_emisor' => 'required|string',
            'fecha_hora' => 'required|date',

            'tipo_complemento'           => 'nullable|string',
            'version'                    => 'nullable|string',
            'tipo_cfdi'                  => 'nullable|string|in:ingreso,traslado,egreso,Ingreso,Traslado,Egreso',
            'monto_total'                => 'nullable|numeric',
            'precio_compra'              => 'nullable|numeric',
            'contraprestacion'           => 'nullable|numeric',
            'volumen_documentado_valor'  => 'nullable|numeric',
            'volumen_documentado_unidad' => 'nullable|string',
            'nombre_emisor'              => 'nullable|string',
            'rfc_receptor'               => 'nullable|string',
        ];

        Validator::make($data, $rules)->validate();

        // 3) Crear CFDI
        $cfdi = Cfdi::create($data);

        return response()->json(['success' => true, 'cfdi' => $cfdi], 201);
    }

    public function index($evento_id)
    {
        $cfdis = Cfdi::where('evento_id', $evento_id)->get();
        return response()->json($cfdis);
    }
}
