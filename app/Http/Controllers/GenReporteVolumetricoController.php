<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InformacionGeneralReporte;
use App\Models\EventoAlmacen;
use App\Models\BitacoraEventos;
use App\Models\Almacen;
use Carbon\Carbon;
use App\Models\Cfdis;

class GenReporteVolumetricoController extends Controller
{
    public function generarReporte($idPlanta, $yearAndMonth, $tipoDM)
    {
        date_default_timezone_set('America/Mexico_City');
        $parts = explode('-', $yearAndMonth);
        $year = $parts[0];
        $month= $parts[1];

        if ($tipoDM == 0) {
            return $this->generarReporteMensualAlmacen($idPlanta, $year, $month);
        } elseif ($tipoDM == 1) {
            return $this->generarReportesDiariosPorMes($idPlanta, $year, $month);
        } else {
            return [
                "MENSUALES" => $this->generarReporteMensualAlmacen($idPlanta, $year, $month),
                "DIARIOS" => $this->generarReportesDiariosPorMes($idPlanta, $year, $month)
            ];
        }
    }

    private function generarReporteMensualAlmacen($idPlanta, $year, $month)
    {
        $dataGeneral = InformacionGeneralReporte::where('id_planta', $idPlanta)->first();
        $almacenes = Almacen::where('id_planta', $idPlanta)->get();
        $almacenesIds = $almacenes->pluck('id');

        $eventos = EventoAlmacen::whereIn('id_almacen', $almacenesIds)
            ->whereYear('fecha_inicio_evento', $year)
            ->whereMonth('fecha_inicio_evento', $month)
            ->orderBy('fecha_inicio_evento')
            ->get();

        $caracter = $this->obtenerCaracter($dataGeneral);

        $totalRecepciones = $eventos->where('tipo_evento', 'entrada');
        $totalEntregas = $eventos->where('tipo_evento', 'salida');

        $volumenRecepcion = (float) $totalRecepciones->sum('volumen_movido');
        $volumenEntrega = (float) $totalEntregas->sum('volumen_movido');

        $importeRecepciones = 96000.00;
        $importeEntregas = 135000.00;

        $complementosRecepcion = Cfdis::whereIn('evento_id', $totalRecepciones->pluck('id'))->get()->map(function ($cfdi) {
            return [
                "UUID" => $cfdi->UUID,
                "Fecha" => Carbon::parse($cfdi->FechaCFDI)->format('Y-m-d\TH:i:s'),
                "Proveedor" => $cfdi->NombreEmisorCFDI,
                "VolumenRelacionado" => (float) $cfdi->MontoTotalOperacion,
                "Unidad" => "L"
            ];
        });


        $complementosEntrega = Cfdis::whereIn('evento_id', $totalEntregas->pluck('id'))->get()->map(function ($cfdi) {
        return [
            "UUID" => $cfdi->UUID,
            "Fecha" => Carbon::parse($cfdi->FechaCFDI)->format('Y-m-d\TH:i:s'),
            "Cliente" => $cfdi->NombreEmisorCFDI,
            "VolumenRelacionado" => (float) $cfdi->MontoTotalOperacion,
            "Unidad" => "L"
            ];
        });

        $tanques = $almacenes->map(function ($tanque) {
        return [
                "ClaveIdentificacionTanque" => $tanque->clave_almacen,
                "LocalizacionDescripcionTanque" => $tanque->localizacion_descripcion_almacen,
                "VigenciaCalibracionTanque" => $tanque->vigencia_calibracion_tanque,
                "CapacidadTotalTanque" => ["ValorNumerico" => (float) $tanque->capacidad_almacen, "UnidadDeMedida" => "UM03"],
                "CapacidadOperativaTanque" => ["ValorNumerico" => (float) $tanque->capacidad_operativa, "UnidadDeMedida" => "UM03"],
                "CapacidadUtilTanque" => ["ValorNumerico" => (float) $tanque->capacidad_util, "UnidadDeMedida" => "UM03"],
                "CapacidadFondajeTanque" => ["ValorNumerico" => (float) $tanque->capacidad_fondaje, "UnidadDeMedida" => "UM03"],
                "CapacidadGasTalon" => ["ValorNumerico" => 0, "UnidadDeMedida" => "UM03"],
                "VolumenMinimoOperacion" => ["ValorNumerico" => (float) $tanque->volumen_minimo_operacion, "UnidadDeMedida" => "UM03"],
                "EstadoTanque" => $tanque->estado_tanque,
                "Medidores" => []
            ];
        })->toArray();

        $bitacora = BitacoraEventos::where('id_planta', $idPlanta)
        ->whereYear('FechaYHoraEvento', $year)
        ->whereMonth('FechaYHoraEvento', $month)
        ->orderBy('FechaYHoraEvento')
        ->get()
        ->map(function ($registro, $index) {
            return [
                "NumeroRegistro" => $index + 1,
                "FechaYHoraEvento" => Carbon::parse($registro->FechaYHoraEvento)->format('Y-m-d\TH:i:sP'),
                "UsuarioResponsable" => $registro->UsuarioResponsable,
                "TipoEvento" => $registro->TipoEvento,
                "DescripcionEvento" => $registro->DescripcionEvento,
                "IdentificacionComponenteAlarma" => $registro->IdentificacionComponenteAlarma
            ];
        })->toArray();



        return response()->json([
            "TipoReporte" => "M",
            "Version" => "1.0",
            "RfcContribuyente" => $dataGeneral->rfc_contribuyente,
            "RfcProveedor" => $dataGeneral->rfc_proveedor,
            "Caracter" => $caracter,
            "ClaveInstalacion" => $dataGeneral->clave_instalacion,
            "DescripcionInstalacion" => $dataGeneral->descripcion_instalacion,
            "Geolocalizacion" => [
                "GeolocalizacionLatitud" => $dataGeneral->geolocalizacion_latitud,
                "GeolocalizacionLongitud" => $dataGeneral->geolocalizacion_latitud
            ],
            "NumeroPozos" => $dataGeneral->numero_pozos,
            "NumeroTanques" => $dataGeneral->numero_tanques,
            "NumeroDuctosEntradaSalida" => $dataGeneral->numero_ductos_entrada_salida,
            "NumeroDuctosTransporteDistribucion" => $dataGeneral->numero_ductos_transporte,
            "NumeroDispensarios" => $dataGeneral->numero_dispensarios,
            "FechaYHoraReporteMes" => Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d\T23:59:59P'),
            "PRODUCTO" => [[
                "ClaveProducto" => "PR12",
                "ComposDePropanoEnGasLP" => 91.5720,
                "ComposDeButanoEnGasLP" => 8.4279,
                "TANQUE" => $tanques,
                "REPORTEDEVOLUMENMENSUAL" => [
                    "CONTROLDEEXISTENCIAS" => [
                        "VolumenExistenciasMes" => ["ValorNumerico" => 2500.00, "UM" => "UM03"],
                        "FechaYHoraEstaMedicionMes" => Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d\T23:59:00P')
                    ],
                    "RECEPCIONES" => [
                        "TotalRecepcionesMes" => $totalRecepciones->count(),
                        "SumaVolumenRecepcionMes" => ["ValorNumerico" => $volumenRecepcion, "UM" => "UM03"],
                        "PoderCalorifico" => ["ValorNumerico" => 11500, "UM" => "UM03"],
                        "TotalDocumentosMes" => $totalRecepciones->count(),
                        "ImporteTotalRecepcionesMensual" => $importeRecepciones,
                        "Complemento" => $complementosRecepcion
                    ],
                    "ENTREGAS" => [
                        "TotalEntregasMes" => $totalEntregas->count(),
                        "SumaVolumenEntregadoMes" => ["ValorNumerico" => $volumenEntrega, "UM" => "UM03"],
                        "PoderCalorifico" => ["ValorNumerico" => 11500, "UM" => "UM03"],
                        "TotalDocumentosMes" => $totalEntregas->count(),
                        "ImporteTotalEntregasMes" => $importeEntregas,
                        "Complemento" => $complementosEntrega
                    ]
                ]
            ]],
            "BITACORA" => $bitacora
        ]);
    }


    private function generarReportesDiariosPorMes($idPlanta, $year, $month)
    {
        $almacenes = Almacen::where('id_planta', $idPlanta)->get();
        $almacenesIds = $almacenes->pluck('id');

        $fechasUnicas = EventoAlmacen::whereIn('id_almacen', $almacenesIds)
            ->whereYear('fecha_inicio_evento', $year)
            ->whereMonth('fecha_inicio_evento', $month)
            ->pluck('fecha_inicio_evento')
            ->map(fn($fecha) => Carbon::parse($fecha)->toDateString())
            ->unique()
            ->values();

        $reportes = [];

        foreach ($fechasUnicas as $fecha) {
            $reporte = $this->generarReporteDiarioPorFecha($idPlanta, $fecha);
            $reportes[] = ["Fecha" => $fecha, "REPORTE" => $reporte];
        }

        return $reportes;
    }


    private function generarReporteDiarioPorFecha($idPlanta, $fecha)
    {
        $dataGeneral = InformacionGeneralReporte::where('id_planta', $idPlanta)->first();
        $almacenes = Almacen::where('id_planta', $idPlanta)->get();
        $almacenesIds = $almacenes->pluck('id');

        $eventos = EventoAlmacen::whereIn('id_almacen', $almacenesIds)
            ->whereDate('fecha_inicio_evento', $fecha)
            ->orderBy('fecha_inicio_evento')
            ->get();

        $cfdis = Cfdis::whereIn('evento_id', $eventos->pluck('id'))->get()->groupBy('evento_id');

        $caracter = $this->obtenerCaracter($dataGeneral);

        $tanquesBase = $almacenes->map(function ($tanque) use ($eventos, $cfdis) {
            $eventosTanque = $eventos->where('id_almacen', $tanque->id);

            $primerEvento = $eventosTanque->sortBy('fecha_inicio_evento')->first();
            $ultimoEvento = $eventosTanque->sortByDesc('fecha_fin_evento')->first();

            $volumenInicial = $primerEvento->volumen_inicial ?? 0;
            $volumenFinal = $ultimoEvento->volumen_final ?? 0;

            $recepcionesEventos = $eventosTanque->where('tipo_evento', 'entrada');
            $entregasEventos = $eventosTanque->where('tipo_evento', 'salida');

            $horaRecepcion = optional($recepcionesEventos->last())->fecha_fin_evento;
            $horaEntrega = optional($entregasEventos->last())->fecha_fin_evento;

            $existencia = [
                "VolumenExistenciasAnterior" => ["ValorNumerico" => (float) $volumenInicial, "UnidadDeMedida" => "UM03"],
                "VolumenAcumOpsRecepcion" => ["ValorNumerico" => (float) $recepcionesEventos->sum('volumen_movido'), "UnidadDeMedida" => "UM03"],
                "HoraRecepcionAcumulado" => optional($horaRecepcion)->format('H:i:sP'),
                "VolumenAcumOpsEntrega" => ["ValorNumerico" => (float) $entregasEventos->sum('volumen_movido'), "UnidadDeMedida" => "UM03"],
                "HoraEntregaAcumulado" => optional($horaEntrega)->format('H:i:sP'),
                "VolumenExistencias" => ["ValorNumerico" => (float) $volumenFinal, "UnidadDeMedida" => "UM03"],
                "FechaYHoraEstaMedicion" => now()->format('Y-m-d\T23:59:59P'),
                "FechaYHoraMedicionAnterior" => now()->subDay()->format('Y-m-d\T23:59:59P')
            ];

            $recepciones = $recepcionesEventos->map(function ($evento) use ($cfdis) {
                return [
                    "VolumenDespuesRecepcion" => ["ValorNumerico" => (float) $evento->volumen_final, "UnidadDeMedida" => "UM03"],
                    "VolumenRecepcion" => ["ValorNumerico" => (float) $evento->volumen_movido, "UnidadDeMedida" => "UM03"],
                    "Temperatura" => (float) $evento->temperatura,
                    "PresionAbsoluta" => (float) $evento->presion_absoluta,
                    "FechaYHoraInicioRecepcion" => Carbon::parse($evento->fecha_inicio_evento)->format('Y-m-d\TH:i:sP'),
                    "FechaYHoraFinRecepcion" => Carbon::parse($evento->fecha_fin_evento)->format('Y-m-d\TH:i:sP'),
                    "Complemento" => collect($cfdis->get($evento->id))->map(function ($cfdi) {
                        return [
                            "TipoComplemento" => "CFDI",
                            "Version" => $cfdi->Version,
                            "UUID" => $cfdi->UUID,
                            "RFCEmisorCFDI" => $cfdi->RFCEmisorCFDI,
                            "NombreEmisorCFDI" => $cfdi->NombreEmisorCFDI,
                            "RFCProveedorReceptor" => $cfdi->RFCProveedorReceptor,
                            "MontoTotalOperacion" => (float) $cfdi->MontoTotalOperacion,
                            "FechaCFDI" => \Carbon\Carbon::parse($cfdi->FechaCFDI)->format('Y-m-d')
                        ];
                    })->values()
                ];
            })->values();

            $entregas = $entregasEventos->map(function ($evento) use ($cfdis) {
                return [
                    "VolumenDespuesEntrega" => ["ValorNumerico" => (float) $evento->volumen_final, "UnidadDeMedida" => "UM03"],
                    "VolumenEntregado" => ["ValorNumerico" => (float) $evento->volumen_movido, "UnidadDeMedida" => "UM03"],
                    "Temperatura" => (float) $evento->temperatura,
                    "PresionAbsoluta" => (float) $evento->presion_absoluta,
                    "FechaYHoraInicioEntrega" => Carbon::parse($evento->fecha_inicio_evento)->format('Y-m-d\TH:i:sP'),
                    "FechaYHoraFinEntrega" => Carbon::parse($evento->fecha_fin_evento)->format('Y-m-d\TH:i:sP'),
                    "Complemento" => collect($cfdis->get($evento->id))->map(function ($cfdi) {
                        return [
                            "TipoComplemento" => "CFDI",
                            "Version" => $cfdi->Version,
                            "UUID" => $cfdi->UUID,
                            "RFCEmisorCFDI" => $cfdi->RFCEmisorCFDI,
                            "NombreEmisorCFDI" => $cfdi->NombreEmisorCFDI,
                            "RFCProveedorReceptor" => $cfdi->RFCProveedorReceptor,
                            "MontoTotalOperacion" => (float) $cfdi->MontoTotalOperacion,
                            "FechaCFDI" => \Carbon\Carbon::parse($cfdi->FechaCFDI)->format('Y-m-d')
                        ];
                    })->values()
                ];
            })->values();

            return [
                "ClaveIdentificacionTanque" => $tanque->clave_almacen,
                "LocalizacionDescripcionTanque" => $tanque->localizacion_descripcion_almacen,
                "VigenciaCalibracionTanque" => $tanque->vigencia_calibracion_tanque,
                "CapacidadTotalTanque" => ["ValorNumerico" => (float) $tanque->capacidad_almacen, "UnidadDeMedida" => "UM03"],
                "CapacidadOperativaTanque" => ["ValorNumerico" => (float) $tanque->capacidad_operativa, "UnidadDeMedida" => "UM03"],
                "CapacidadUtilTanque" => ["ValorNumerico" => (float) $tanque->capacidad_util, "UnidadDeMedida" => "UM03"],
                "CapacidadFondajeTanque" => ["ValorNumerico" => (float) $tanque->capacidad_fondaje, "UnidadDeMedida" => "UM03"],
                "CapacidadGasTalon" => ["ValorNumerico" => 0, "UnidadDeMedida" => "UM03"],
                "VolumenMinimoOperacion" => ["ValorNumerico" => (float) $tanque->volumen_minimo_operacion, "UnidadDeMedida" => "UM03"],
                "EstadoTanque" => $tanque->estado_tanque,
                "Medidores" => [],
                "EXISTENCIAS" => $existencia,
                "RECEPCIONES" => $recepciones,
                "ENTREGAS" => $entregas
            ];
        })->toArray();

        $bitacora = BitacoraEventos::where('id_planta', $idPlanta)
            ->whereDate('FechaYHoraEvento', $fecha)
            ->orderBy('FechaYHoraEvento')
            ->get()
            ->map(function ($registro, $index) {
                return [
                    "NumeroRegistro" => $index + 1,
                    "FechaYHoraEvento" => Carbon::parse($registro->FechaYHoraEvento)->format('Y-m-d\TH:i:sP'),
                    "UsuarioResponsable" => $registro->UsuarioResponsable,
                    "TipoEvento" => $registro->TipoEvento,
                    "DescripcionEvento" => $registro->DescripcionEvento,
                    "IdentificacionComponenteAlarma" => $registro->IdentificacionComponenteAlarma
                ];
            })->toArray();

        return [
            "TipoReporte" => "D",
            "Version" => "1.0",
            "RfcContribuyente" => $dataGeneral->rfc_contribuyente,
            "RfcProveedor" => $dataGeneral->rfc_proveedor,
            "Caracter" => $caracter,
            "ClaveInstalacion" => $dataGeneral->clave_instalacion,
            "DescripcionInstalacion" => $dataGeneral->descripcion_instalacion,
            "Geolocalizacion" => [
                "GeolocalizacionLatitud" => $dataGeneral->geolocalizacion_latitud,
                "GeolocalizacionLongitud" => $dataGeneral->geolocalizacion_latitud
            ],
            "NumeroPozos" => $dataGeneral->numero_pozos,
            "NumeroTanques" => $dataGeneral->numero_tanques,
            "NumeroDuctosEntradaSalida" => $dataGeneral->numero_ductos_entrada_salida,
            "NumeroDuctosTransporteDistribucion" => $dataGeneral->numero_ductos_transporte,
            "NumeroDispensarios" => $dataGeneral->numero_dispensarios,
            "FechaYHoraCorte" => Carbon::parse($fecha . ' 23:59:59')->format('Y-m-d\TH:i:sP'),
            "Producto" => [[
                "ClaveProducto" => "PR12",
                "ComposDePropanoEnGasLP" => 91.5720,
                "ComposDeButanoEnGasLP" => 8.4279,
                "TANQUE" => $tanquesBase
            ]],
            "BITACORA" => $bitacora
        ]; 
    }

    private function obtenerCaracter($dataGeneral)
    {
        switch ($dataGeneral->tipo_caracter) {
            case 'permisionario':
                return [
                    'TipoCaracter' => $dataGeneral->tipo_caracter,
                    'ModalidadPermiso' => $dataGeneral->modalidad_permiso,
                    'NumPermiso' => $dataGeneral->numero_permiso
                ];
            case 'asignatario':
            case 'contratista':
                return [
                    'TipoCaracter' => $dataGeneral->tipo_caracter,
                    'NumContratoOAsignacion' => $dataGeneral->numero_contrato_asignacion
                ];
            case 'usuario':
                return [
                    'TipoCaracter' => $dataGeneral->tipo_caracter,
                    'InstalacionAlmacenGasNatural' => $dataGeneral->instalacion_almacen_gas
                ];
            default:
                return [
                    'TipoCaracter' => $dataGeneral->tipo_caracter,
                    'Mensaje' => 'Tipo de caracter no reconocido'
                ];
        }
    }
}