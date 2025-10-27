<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\InformacionGeneralReporte;
use App\Models\EventoAlmacen;
use App\Models\BitacoraEventos;
use App\Models\Almacen;
use App\Models\Cfdi;
use Carbon\Carbon;

class GenReporteVolumetricoController extends Controller
{
    public function generarReporte($idPlanta, $yearAndMonth, $tipoDM)
    {
        date_default_timezone_set('America/Mexico_City');

        [$year, $month] = explode('-', $yearAndMonth);
        $year  = (int) $year;
        $month = (int) $month;

        if ($tipoDM == 0) {
            // Mensual del mes indicado
            return $this->generarReporteMensualAlmacen($idPlanta, $year, $month);
        } elseif ($tipoDM == 1) {
            // Todos los diarios del mes indicado
            return $this->generarReportesDiariosPorMes($idPlanta, $year, $month);
        } else {
            // Paquete: mensual + diarios del mes
            return [
                "MENSUALES" => $this->generarReporteMensualAlmacen($idPlanta, $year, $month),
                "DIARIOS"   => $this->generarReportesDiariosPorMes($idPlanta, $year, $month)
            ];
        }
    }

    /* ===========================
     *  REPORTE MENSUAL (ALMACÉN)
     * ===========================
     */
    private function generarReporteMensualAlmacen($idPlanta, $year, $month)
    {
        $dataGeneral = InformacionGeneralReporte::where('id_planta', $idPlanta)->firstOrFail();

        $almacenes    = Almacen::where('id_planta', $idPlanta)->get();
        $almacenesIds = $almacenes->pluck('id');

        // Si fecha_inicio_evento es TIMESTAMP/DATE:
        $eventos = EventoAlmacen::whereIn('id_almacen', $almacenesIds)
            ->whereYear('fecha_inicio_evento', $year)
            ->whereMonth('fecha_inicio_evento', $month)
            ->orderBy('fecha_inicio_evento')
            ->get();

        // Carácter: buscamos en tipo_caracter_planta la modalidad ALM vigente para fin de mes
        $fechaCorteMes = Carbon::create($year, $month, 1)->endOfMonth();
        $caracter = $this->resolverCaracterDesdeTabla($dataGeneral, 'ALM', $fechaCorteMes);

        $totalRecepciones = $eventos->where('tipo_evento', 'entrada');
        $totalEntregas    = $eventos->where('tipo_evento', 'salida');

        $volumenRecepcion = (float) $totalRecepciones->sum('volumen_movido');
        $volumenEntrega   = (float) $totalEntregas->sum('volumen_movido');

        // TODO: sustituir por cálculos reales si corresponde
        $importeRecepciones = 96000.00;
        $importeEntregas    = 135000.00;

        $complementosRecepcion = Cfdi::whereIn('evento_id', $totalRecepciones->pluck('id'))
            ->get()
            ->map(function ($cfdi) {
                return [
                    "UUID"               => $cfdi->uuid,
                    "Fecha"              => $this->fmtIso($cfdi->FechaCFDI, 'Y-m-d\TH:i:s'),
                    "Proveedor"          => $cfdi->NombreEmisorCFDI,
                    "VolumenRelacionado" => (float) $cfdi->MontoTotalOperacion,
                    "Unidad"             => "L",
                ];
            });

        $complementosEntrega = Cfdi::whereIn('evento_id', $totalEntregas->pluck('id'))
            ->get()
            ->map(function ($cfdi) {
                return [
                    "UUID"               => $cfdi->uuid,
                    "Fecha"              => $this->fmtIso($cfdi->FechaCFDI, 'Y-m-d\TH:i:s'),
                    "Cliente"            => $cfdi->NombreEmisorCFDI,
                    "VolumenRelacionado" => (float) $cfdi->MontoTotalOperacion,
                    "Unidad"             => "L",
                ];
            });

        $tanques = $almacenes->map(function ($tanque) {
            return [
                "ClaveIdentificacionTanque"     => $tanque->clave_almacen,
                "LocalizacionDescripcionTanque" => $tanque->localizacion_descripcion_almacen,
                "VigenciaCalibracionTanque"     => $tanque->vigencia_calibracion_tanque,
                "CapacidadTotalTanque"          => ["ValorNumerico" => (float) $tanque->capacidad_almacen,           "UnidadDeMedida" => "UM03"],
                "CapacidadOperativaTanque"      => ["ValorNumerico" => (float) $tanque->capacidad_operativa,         "UnidadDeMedida" => "UM03"],
                "CapacidadUtilTanque"           => ["ValorNumerico" => (float) $tanque->capacidad_util,              "UnidadDeMedida" => "UM03"],
                "CapacidadFondajeTanque"        => ["ValorNumerico" => (float) $tanque->capacidad_fondaje,           "UnidadDeMedida" => "UM03"],
                "CapacidadGasTalon"             => ["ValorNumerico" => 0.0,                                          "UnidadDeMedida" => "UM03"],
                "VolumenMinimoOperacion"        => ["ValorNumerico" => (float) $tanque->volumen_minimo_operacion,    "UnidadDeMedida" => "UM03"],
                "EstadoTanque"                  => $tanque->estado_tanque,
                "Medidores"                     => [],
            ];
        })->toArray();

        // Bitácora mensual (rango de mes)
        $inicioMes = Carbon::create($year, $month, 1)->startOfMonth();
        $finMes    = Carbon::create($year, $month, 1)->endOfMonth();

        $bitacora = BitacoraEventos::where('id_planta', $idPlanta)
            ->whereRaw('"FechaYHoraEvento"::timestamp BETWEEN ? AND ?', [
                $inicioMes->toDateTimeString(),
                $finMes->toDateTimeString(),
            ])
            ->orderBy('FechaYHoraEvento')
            ->get()
            ->map(function ($registro, $index) {
                return [
                    "NumeroRegistro"                 => $index + 1,
                    "FechaYHoraEvento"               => $this->fmtIso($registro->FechaYHoraEvento, 'Y-m-d\TH:i:sP'),
                    "UsuarioResponsable"             => $registro->UsuarioResponsable,
                    "TipoEvento"                     => $registro->TipoEvento,
                    "DescripcionEvento"              => $registro->DescripcionEvento,
                    "IdentificacionComponenteAlarma" => $registro->IdentificacionComponenteAlarma,
                ];
            })
            ->toArray();

        return response()->json([
            "TipoReporte"             => "M",
            "Version"                 => "1.0",
            "RfcContribuyente"        => $dataGeneral->rfc_contribuyente,
            "RfcProveedor"            => $dataGeneral->rfc_proveedor,
            "Caracter"                => $caracter,
            "ClaveInstalacion"        => $dataGeneral->clave_instalacion,
            "DescripcionInstalacion"  => $dataGeneral->descripcion_instalacion,
            "Geolocalizacion"         => [
                "GeolocalizacionLatitud"  => $dataGeneral->geolocalizacion_latitud,
                "GeolocalizacionLongitud" => $dataGeneral->geolocalizacion_longitud,
            ],
            "NumeroPozos"                         => (int) $dataGeneral->numero_pozos,
            "NumeroTanques"                       => (int) $dataGeneral->numero_tanques,
            "NumeroDuctosEntradaSalida"           => (int) $dataGeneral->numero_ductos_entrada_salida,
            "NumeroDuctosTransporteDistribucion"  => (int) $dataGeneral->numero_ductos_transporte,
            "NumeroDispensarios"                  => (int) $dataGeneral->numero_dispensarios,
            "FechaYHoraReporteMes"                => Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d\T23:59:59P'),

            "Producto" => [[
                "ClaveProducto"           => "PR12",
                "ComposDePropanoEnGasLP"  => 91.5720,
                "ComposDeButanoEnGasLP"   => 8.4279,

                "TANQUE" => $tanques,

                "REPORTEDEVOLUMENMENSUAL" => [
                    "CONTROLDEEXISTENCIAS" => [
                        "VolumenExistenciasMes"     => ["ValorNumerico" => 2500.00,      "UnidadDeMedida" => "UM03"],
                        "FechaYHoraEstaMedicionMes" => Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d\T23:59:00P'),
                    ],
                    "RECEPCIONES" => [
                        "TotalRecepcionesMes"            => $totalRecepciones->count(),
                        "SumaVolumenRecepcionMes"        => ["ValorNumerico" => $volumenRecepcion, "UnidadDeMedida" => "UM03"],
                        "PoderCalorifico"                => ["ValorNumerico" => 11500,             "UnidadDeMedida" => "UM03"],
                        "TotalDocumentosMes"             => $totalRecepciones->count(),
                        "ImporteTotalRecepcionesMensual" => $importeRecepciones,
                        "Complemento"                    => $complementosRecepcion,
                    ],
                    "ENTREGAS" => [
                        "TotalEntregasMes"               => $totalEntregas->count(),
                        "SumaVolumenEntregadoMes"        => ["ValorNumerico" => $volumenEntrega,   "UnidadDeMedida" => "UM03"],
                        "PoderCalorifico"                => ["ValorNumerico" => 11500,             "UnidadDeMedida" => "UM03"],
                        "TotalDocumentosMes"             => $totalEntregas->count(),
                        "ImporteTotalEntregasMensual"    => $importeEntregas,
                        "Complemento"                    => $complementosEntrega,
                    ],
                ],
            ]],

            "BITACORA" => $bitacora,
        ]);
    }

    /* ==================================
     *  GENERADOR DE REPORTES DIARIOS x MES
     * ==================================
     */
    private function generarReportesDiariosPorMes($idPlanta, $year, $month)
    {
        $almacenes    = Almacen::where('id_planta', $idPlanta)->get();
        $almacenesIds = $almacenes->pluck('id');

        // Si 'fecha_inicio_evento' es TIMESTAMP/DATE:
        $fechasUnicas = EventoAlmacen::whereIn('id_almacen', $almacenesIds)
            ->whereYear('fecha_inicio_evento', $year)
            ->whereMonth('fecha_inicio_evento', $month)
            ->pluck('fecha_inicio_evento')
            ->map(fn($f) => $this->fmtIso($f, 'Y-m-d'))
            ->unique()
            ->values();

        if ($fechasUnicas->isEmpty()) {
            return [];
        }

        $reportes = [];
        foreach ($fechasUnicas as $fecha) {
            $reporte    = $this->generarReporteDiarioPorFecha($idPlanta, $fecha);
            $reportes[] = ["Fecha" => $fecha, "REPORTE" => $reporte];
        }

        return $reportes;
    }

    /* ===========================
     *  REPORTE DIARIO POR FECHA
     * ===========================
     */
    private function generarReporteDiarioPorFecha($idPlanta, $fecha)
    {
        if (empty($fecha)) {
            abort(400, 'La fecha es obligatoria para el reporte diario');
        }

        $dataGeneral = InformacionGeneralReporte::where('id_planta', $idPlanta)->firstOrFail();

        $almacenes    = Almacen::where('id_planta', $idPlanta)->get();
        $almacenesIds = $almacenes->pluck('id');

        // Si fecha_inicio_evento es DATE/TIMESTAMP:
        $eventos = EventoAlmacen::whereIn('id_almacen', $almacenesIds)
            ->whereDate('fecha_inicio_evento', $fecha)
            ->orderBy('fecha_inicio_evento')
            ->get();

        $cfdis = Cfdi::whereIn('evento_id', $eventos->pluck('id'))->get()->groupBy('evento_id');

        // Carácter diario (ALM)
        $caracter = $this->resolverCaracterDesdeTabla($dataGeneral, 'ALM', $fecha);

        $tanquesBase = $almacenes->map(function ($tanque) use ($eventos, $cfdis, $fecha) {

            $eventosTanque  = $eventos->where('id_almacen', $tanque->id);
            $primerEvento   = $eventosTanque->sortBy('fecha_inicio_evento')->first();
            $ultimoEvento   = $eventosTanque->sortByDesc('fecha_fin_evento')->first();

            $volumenInicial = $primerEvento->volumen_inicial ?? 0;
            $volumenFinal   = $ultimoEvento->volumen_final   ?? 0;

            $recepcionesEventos = $eventosTanque->where('tipo_evento', 'entrada');
            $entregasEventos    = $eventosTanque->where('tipo_evento', 'salida');

            $horaRecepcion = optional($recepcionesEventos->last())->fecha_fin_evento;
            $horaEntrega   = optional($entregasEventos->last())->fecha_fin_evento;

            $horaRecepFmt = $this->fmtIso($horaRecepcion, 'H:i:sP');
            $horaEntreFmt = $this->fmtIso($horaEntrega,   'H:i:sP');

            $existencia = [
                "VolumenExistenciasAnterior" => ["ValorNumerico" => (float) $volumenInicial, "UnidadDeMedida" => "UM03"],
                "VolumenAcumOpsRecepcion"    => ["ValorNumerico" => (float) $recepcionesEventos->sum('volumen_movido'), "UnidadDeMedida" => "UM03"],
                "HoraRecepcionAcumulado"     => $horaRecepFmt,
                "VolumenAcumOpsEntrega"      => ["ValorNumerico" => (float) $entregasEventos->sum('volumen_movido'),   "UnidadDeMedida" => "UM03"],
                "HoraEntregaAcumulado"       => $horaEntreFmt,
                "VolumenExistencias"         => ["ValorNumerico" => (float) $volumenFinal, "UnidadDeMedida" => "UM03"],
                "FechaYHoraEstaMedicion"     => $this->fmtIso(Carbon::parse($fecha)->endOfDay(), 'Y-m-d\TH:i:sP'),
                "FechaYHoraMedicionAnterior" => $this->fmtIso(Carbon::parse($fecha)->subDay()->endOfDay(), 'Y-m-d\TH:i:sP'),
            ];

            $recepciones = $recepcionesEventos->map(function ($evento) use ($cfdis) {
                return [
                    "VolumenDespuesRecepcion"    => ["ValorNumerico" => (float) $evento->volumen_final,  "UnidadDeMedida" => "UM03"],
                    "VolumenRecepcion"           => ["ValorNumerico" => (float) $evento->volumen_movido, "UnidadDeMedida" => "UM03"],
                    "Temperatura"                => (float) $evento->temperatura,
                    "PresionAbsoluta"            => (float) $evento->presion_absoluta,
                    "FechaYHoraInicioRecepcion"  => $this->fmtIso($evento->fecha_inicio_evento, 'Y-m-d\TH:i:sP'),
                    "FechaYHoraFinRecepcion"     => $this->fmtIso($evento->fecha_fin_evento,    'Y-m-d\TH:i:sP'),
                    "Complemento" => collect($cfdis->get($evento->id))->map(function ($cfdi) {
                        return [
                            "TipoComplemento"       => "CFDI",
                            "Version"               => $cfdi->version,
                            "UUID"                  => $cfdi->uuid,
                            "RFCEmisorCFDI"         => $cfdi->rfc_emisor,
                            "NombreEmisorCFDI"      => $cfdi->nombre_emisor,
                            "RFCProveedorReceptor"  => $cfdi->rfc_receptor,
                            "MontoTotalOperacion"   => (float) $cfdi->monto_total,
                            "FechaCFDI"             => $this->fmtIso($cfdi->fecha_hora, 'Y-m-d'),
                        ];
                    })->values(),
                ];
            })->values();

            $entregas = $entregasEventos->map(function ($evento) use ($cfdis) {
                return [
                    "VolumenDespuesEntrega"      => ["ValorNumerico" => (float) $evento->volumen_final,  "UnidadDeMedida" => "UM03"],
                    "VolumenEntregado"           => ["ValorNumerico" => (float) $evento->volumen_movido, "UnidadDeMedida" => "UM03"],
                    "Temperatura"                => (float) $evento->temperatura,
                    "PresionAbsoluta"            => (float) $evento->presion_absoluta,
                    "FechaYHoraInicioEntrega"    => $this->fmtIso($evento->fecha_inicio_evento, 'Y-m-d\TH:i:sP'),
                    "FechaYHoraFinEntrega"       => $this->fmtIso($evento->fecha_fin_evento,    'Y-m-d\TH:i:sP'),
                    "Complemento" => collect($cfdis->get($evento->id))->map(function ($cfdi) {
                        return [
                            "TipoComplemento"       => "CFDI",
                            "Version"               => $cfdi->version,
                            "UUID"                  => $cfdi->uuid,
                            "RFCEmisorCFDI"         => $cfdi->rfc_emisor,
                            "NombreEmisorCFDI"      => $cfdi->nombre_emisor,
                            "RFCProveedorReceptor"  => $cfdi->rfc_receptor,
                            "MontoTotalOperacion"   => (float) $cfdi->monto_total,
                            "FechaCFDI"             => $this->fmtIso($cfdi->fecha_hora, 'Y-m-d'),
                        ];
                    })->values(),
                ];
            })->values();

            return [
                "ClaveIdentificacionTanque"     => $tanque->clave_almacen,
                "LocalizacionDescripcionTanque" => $tanque->localizacion_descripcion_almacen,
                "VigenciaCalibracionTanque"     => $tanque->vigencia_calibracion_tanque,
                "CapacidadTotalTanque"          => ["ValorNumerico" => (float) $tanque->capacidad_almacen,        "UnidadDeMedida" => "UM03"],
                "CapacidadOperativaTanque"      => ["ValorNumerico" => (float) $tanque->capacidad_operativa,      "UnidadDeMedida" => "UM03"],
                "CapacidadUtilTanque"           => ["ValorNumerico" => (float) $tanque->capacidad_util,           "UnidadDeMedida" => "UM03"],
                "CapacidadFondajeTanque"        => ["ValorNumerico" => (float) $tanque->capacidad_fondaje,        "UnidadDeMedida" => "UM03"],
                "CapacidadGasTalon"             => ["ValorNumerico" => 0.0,                                       "UnidadDeMedida" => "UM03"],
                "VolumenMinimoOperacion"        => ["ValorNumerico" => (float) $tanque->volumen_minimo_operacion, "UnidadDeMedida" => "UM03"],
                "EstadoTanque"                  => $tanque->estado_tanque,
                "Medidores"                     => [],
                "EXISTENCIAS"                   => $existencia,
                "RECEPCIONES"                   => $recepciones,
                "ENTREGAS"                      => $entregas,
            ];
        })->toArray();

        // Bitácora diaria
        $bitacora = BitacoraEventos::where('id_planta', $idPlanta)
            ->whereRaw('DATE("FechaYHoraEvento"::timestamp) = ?', [$fecha])
            ->orderBy('FechaYHoraEvento')
            ->get()
            ->map(function ($registro, $index) {
                return [
                    "NumeroRegistro"                 => $index + 1,
                    "FechaYHoraEvento"               => $this->fmtIso($registro->FechaYHoraEvento, 'Y-m-d\TH:i:sP'),
                    "UsuarioResponsable"             => $registro->UsuarioResponsable,
                    "TipoEvento"                     => $registro->TipoEvento,
                    "DescripcionEvento"              => $registro->DescripcionEvento,
                    "IdentificacionComponenteAlarma" => $registro->IdentificacionComponenteAlarma,
                ];
            })
            ->toArray();

        return [
            "TipoReporte"            => "D",
            "Version"                => "1.0",
            "RfcContribuyente"       => $dataGeneral->rfc_contribuyente,
            "RfcProveedor"           => $dataGeneral->rfc_proveedor,
            "Caracter"               => $caracter,
            "ClaveInstalacion"       => $dataGeneral->clave_instalacion,
            "DescripcionInstalacion" => $dataGeneral->descripcion_instalacion,
            "Geolocalizacion"        => [
                "GeolocalizacionLatitud"  => $dataGeneral->geolocalizacion_latitud,
                "GeolocalizacionLongitud" => $dataGeneral->geolocalizacion_longitud,
            ],
            "NumeroPozos"                        => (int) $dataGeneral->numero_pozos,
            "NumeroTanques"                      => (int) $dataGeneral->numero_tanques,
            "NumeroDuctosEntradaSalida"          => (int) $dataGeneral->numero_ductos_entrada_salida,
            "NumeroDuctosTransporteDistribucion" => (int) $dataGeneral->numero_ductos_transporte,
            "NumeroDispensarios"                 => (int) $dataGeneral->numero_dispensarios,
            "FechaYHoraCorte"                    => $this->fmtIso(Carbon::parse($fecha)->endOfDay(), 'Y-m-d\TH:i:sP'),
            "Producto" => [[
                "ClaveProducto"           => "PR12",
                "ComposDePropanoEnGasLP"  => 91.5720,
                "ComposDeButanoEnGasLP"   => 8.4279,
                "TANQUE"                  => $tanquesBase,
            ]],
            "BITACORA" => $bitacora,
        ];
    }

    /* ======================================================
     *  RESOLVER CARÁCTER DESDE tipo_caracter_planta (NUEVO)
     * ======================================================
     *
     * @param \App\Models\InformacionGeneralReporte $dataGeneral
     * @param string $modalidadNecesaria  Ej. 'ALM', 'COM', 'DIS', 'TRN'
     * @param \Carbon\Carbon|string|null $fechaRef
     * @return array
     */
    private function resolverCaracterDesdeTabla($dataGeneral, string $modalidadNecesaria, $fechaRef = null): array
    {
        $ref  = $fechaRef ? ($fechaRef instanceof \Carbon\Carbon ? $fechaRef : \Carbon\Carbon::parse($fechaRef)) : \Carbon\Carbon::now();
        $igrId = $dataGeneral->id;

        // 1) Intento estricto: permisionario + modalidad solicitada (normalizando a mayúsculas)
        $row = DB::table('tipo_caracter_planta')
            ->where('informacion_general_reporte_id', $igrId)
            ->whereRaw('LOWER(tipo_caracter) = ?', ['permisionario'])
            // OJO: aquí usamos comillas DOBLES afuera para no romper el string
            ->whereRaw("UPPER(COALESCE(modalidad_permiso, '')) = ?", [strtoupper($modalidadNecesaria)])
            ->orderByDesc('id')
            ->first();

        // 2) Si no hay con esa modalidad, toma el más reciente (cualquiera)
        if (!$row) {
            $row = DB::table('tipo_caracter_planta')
                ->where('informacion_general_reporte_id', $igrId)
                ->orderByDesc('id')
                ->first();
        }

        if ($row) {
            $tipo = strtolower($row->tipo_caracter ?? '');

            if ($tipo === 'permisionario') {
                return [
                    'TipoCaracter'     => 'permisionario',
                    'ModalidadPermiso' => $row->modalidad_permiso,          // ALM/COM/DIS/TRN
                    'NumPermiso'       => $row->numero_permiso,
                ];
            }

            if ($tipo === 'contratista' || $tipo === 'asignatario') {
                return [
                    'TipoCaracter'           => $tipo,
                    'NumContratoOAsignacion' => $row->numero_contrato_asignacion,
                ];
            }

            if ($tipo === 'usuario') {
                return [
                    'TipoCaracter'                 => 'usuario',
                    'InstalacionAlmacenGasNatural' => $dataGeneral->instalacion_almacen_gas,
                ];
            }
        }

        // 3) Fallback al esquema legacy si no encontramos nada
        return $this->obtenerCaracter($dataGeneral);
    }


    /* =========================
     *  CARACTER (FALLBACK LEGACY)
     * =========================
     */
    private function obtenerCaracter($dataGeneral)
    {
        switch ($dataGeneral->tipo_caracter) {
            case 'permisionario':
                return [
                    'TipoCaracter'     => $dataGeneral->tipo_caracter,
                    'ModalidadPermiso' => $dataGeneral->modalidad_permiso,
                    'NumPermiso'       => $dataGeneral->numero_permiso,
                ];
            case 'asignatario':
            case 'contratista':
                return [
                    'TipoCaracter'           => $dataGeneral->tipo_caracter,
                    'NumContratoOAsignacion' => $dataGeneral->numero_contrato_asignacion,
                ];
            case 'usuario':
                return [
                    'TipoCaracter'                 => $dataGeneral->tipo_caracter,
                    'InstalacionAlmacenGasNatural' => $dataGeneral->instalacion_almacen_gas,
                ];
            default:
                return [
                    'TipoCaracter' => $dataGeneral->tipo_caracter,
                    'Mensaje'      => 'Tipo de caracter no reconocido',
                ];
        }
    }

    /* ============================================
     *  HELPERS de fecha: parseo y formateo seguros
     * ============================================
     */
    private function toCarbonOrNull($value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }
        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable $e) {
                return null;
            }
        }
        return null;
    }

    private function fmtIso($value, string $pattern)
    {
        $dt = $value instanceof Carbon ? $value : $this->toCarbonOrNull($value);
        return $dt ? $dt->format($pattern) : null;
    }
}
