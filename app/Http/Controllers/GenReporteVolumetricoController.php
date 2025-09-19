<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\InformacionGeneralReporte;
use App\Models\EventoAlmacen;
use App\Models\BitacoraEventos;
use App\Models\Almacen;
use App\Models\Cfdi; // Si tu modelo es Cfdis, cambia este use y las referencias abajo.
use Carbon\Carbon;

class GenReporteVolumetricoController extends Controller
{
    /**
     * Punto de entrada:
     *  - $tipoDM = 0 -> Mensual (almacenamiento)
     *  - $tipoDM = 1 -> Diario por fecha (almacenamiento)
     *  - $tipoDM = 2 -> Serie de diarios por mes (almacenamiento)
     */
    public function generarReporte($idPlanta, $yearAndMonth, $tipoDM)
    {
        date_default_timezone_set('America/Mexico_City');

        [$year, $month] = explode('-', $yearAndMonth);
        $year  = (int) $year;
        $month = (int) $month;

        if ($tipoDM == 0) {
            return $this->generarReporteMensualAlmacen($idPlanta, $year, $month);
        } elseif ($tipoDM == 1) {
            // Espera ?fecha=YYYY-MM-DD en query
            $fecha = request()->query('fecha');
            if (!$fecha) {
                return response()->json(['error' => 'Falta parámetro fecha=YYYY-MM-DD'], 422);
            }
            return $this->generarReporteDiarioPorFecha($idPlanta, $fecha);
        } elseif ($tipoDM == 2) {
            return $this->generarReportesDiariosPorMes($idPlanta, $year, $month);
        }

        return response()->json(['error' => 'tipoDM inválido'], 400);
    }

    /**
     * Reporte MENSUAL (estrictamente almacenamiento)
     */
    private function generarReporteMensualAlmacen(int $idPlanta, int $year, int $month)
    {
        // Rango de mes
        $inicioMes = Carbon::create($year, $month, 1, 0, 0, 0);
        $finMes    = (clone $inicioMes)->endOfMonth()->setTime(23, 59, 59);

        // Información general de la planta / instalación
        $dataGeneral = InformacionGeneralReporte::where('id_planta', $idPlanta)->first();
        if (!$dataGeneral) {
            return response()->json(['error' => 'No existe InformacionGeneralReporte para la planta'], 404);
        }

        // Tanques (almacenes) de la planta
        $tanques = Almacen::where('id_planta', $idPlanta)->get();

        // Eventos del mes (por tanque)
        $eventosMes = EventoAlmacen::where('id_planta', $idPlanta)
            ->whereBetween('fecha_inicio_evento', [$inicioMes, $finMes])
            ->orderBy('fecha_inicio_evento', 'asc')
            ->get();

        // Bitácora del mes (si aplica)
        $bitacora = BitacoraEventos::where('id_planta', $idPlanta)
            ->whereBetween('fecha_evento', [$inicioMes, $finMes])
            ->orderBy('fecha_evento', 'asc')
            ->get()
            ->map(function ($b) {
                return [
                    'Evento'     => $b->evento ?? null,
                    'Descripcion'=> $b->descripcion ?? null,
                    'FechaHora'  => $this->fmtIso($b->fecha_evento, 'Y-m-d\TH:i:sP'),
                ];
            });

        // Cálculo de agregados por tanque para el mes
        $reporteTanques = [];
        foreach ($tanques as $t) {
            $evs = $eventosMes->where('id_almacen', $t->id);

            // Primer y último evento del mes por tanque
            $primer = $evs->sortBy('fecha_inicio_evento')->first();
            $ultimo = $evs->sortByDesc('fecha_fin_evento')->first();

            // Sumas por tipo
            $volRecep = (float) $evs->where('tipo_evento', 'entrada')->sum('volumen_movido');
            $volEnt   = (float) $evs->where('tipo_evento', 'salida')->sum('volumen_movido');

            // Existencias según identidad: EI + R - E = EF
            $existInicial = (float) ($primer->volumen_inicial ?? 0);
            $existFinal   = (float) ($ultimo->volumen_final   ?? 0);

            // Complemento mensual (consolidado) para RECEPCIONES y ENTREGAS
            // - Puedes consolidar los CFDIs del mes asociados a eventos de este tanque
            $cfdisRecepTanque = $this->buscarCfdisPorEventos($evs->where('tipo_evento', 'entrada')->pluck('id')->all());
            $cfdisEntrTanque  = $this->buscarCfdisPorEventos($evs->where('tipo_evento', 'salida')->pluck('id')->all());

            $compRecep = $this->buildComplementoAlmacenamiento([
                'cfdis'                     => $cfdisRecepTanque,
                'dictamen'                  => $this->obtenerDictamenMensual($idPlanta, $t->id, $inicioMes, $finMes),
                'certificado'               => $this->obtenerCertificadoVigente($idPlanta),
                'transporte'                => $this->obtenerTransporteMensual($idPlanta, $t->id, $inicioMes, $finMes),
                'trasvase'                  => $this->obtenerTrasvaseMensual($idPlanta, $t->id, $inicioMes, $finMes),
                'rfc_cliente_proveedor'     => $dataGeneral->rfc_proveedor ?? null,
                'nombre_cliente_proveedor'  => $dataGeneral->descripcion_instalacion ?? null,
                'permiso_proveedor'         => $dataGeneral->permiso_cre ?? null, // ajusta si manejas permiso del proveedor
            ]);

            $compEntr = $this->buildComplementoAlmacenamiento([
                'cfdis'                     => $cfdisEntrTanque,
                'dictamen'                  => $this->obtenerDictamenMensual($idPlanta, $t->id, $inicioMes, $finMes),
                'certificado'               => $this->obtenerCertificadoVigente($idPlanta),
                'transporte'                => $this->obtenerTransporteMensual($idPlanta, $t->id, $inicioMes, $finMes),
                'trasvase'                  => $this->obtenerTrasvaseMensual($idPlanta, $t->id, $inicioMes, $finMes),
                'rfc_cliente_proveedor'     => $dataGeneral->rfc_cliente ?? null,
                'nombre_cliente_proveedor'  => $dataGeneral->nombre_cliente ?? null,
                'permiso_proveedor'         => null,
            ]);

            $reporteTanques[] = [
                'IdentificadorTanque' => $t->identificador ?? ("TQ-".$t->id),
                'CapacidadTotal' => [
                    'ValorNumerico'   => (float) ($t->capacidad_total ?? 0),
                    'UnidadDeMedida'  => 'UM03', // litros
                ],
                'CapacidadOperativa' => [
                    'ValorNumerico'   => (float) ($t->capacidad_operativa ?? 0),
                    'UnidadDeMedida'  => 'UM03',
                ],
                'CapacidadFondaje' => [
                    'ValorNumerico'   => (float) ($t->capacidad_fondaje ?? 0),
                    'UnidadDeMedida'  => 'UM03',
                ],
                'ValorMinimoOperacion' => [
                    'ValorNumerico'   => (float) ($t->valor_min_operacion ?? 0),
                    'UnidadDeMedida'  => 'UM03',
                ],
                'VigenciaCalibracion' => [
                    'FechaInicio' => $this->fmtIso($t->calibracion_inicio ?? null, 'Y-m-d'),
                    'FechaFin'    => $this->fmtIso($t->calibracion_fin ?? null,    'Y-m-d'),
                ],
                'EstadoTanque' => $t->estado ?? 'OPERATIVO',

                'REPORTEDEVOLUMENMENSUAL' => [
                    'RECEPCIONES' => [
                        'TotalRecepcionesMes' => (int) $evs->where('tipo_evento', 'entrada')->count(),
                        'SumaVolumenRecepcionMes' => [
                            'ValorNumerico'  => $this->round2($volRecep),
                            'UM'             => 'UM03',
                        ],
                        'PoderCalorifico' => [
                            'ValorNumerico'  => 11500, // TODO: si manejas dictamen con poder calorífico, cámbialo aquí
                            'UM'             => 'UM03',
                        ],
                        'TotalDocumentosMes' => (int) ($cfdisRecepTanque->count()),
                        'ImporteTotalRecepcionesMensual' => $this->sumImporteCfdis($cfdisRecepTanque),
                        'Complemento' => $compRecep,
                    ],
                    'ENTREGAS' => [
                        'TotalEntregasMes' => (int) $evs->where('tipo_evento', 'salida')->count(),
                        'SumaVolumenEntregadoMes' => [
                            'ValorNumerico'  => $this->round2($volEnt),
                            'UM'             => 'UM03',
                        ],
                        'PoderCalorifico' => [
                            'ValorNumerico'  => 11500,
                            'UM'             => 'UM03',
                        ],
                        'TotalDocumentosMes' => (int) ($cfdisEntrTanque->count()),
                        'ImporteTotalEntregasMensual' => $this->sumImporteCfdis($cfdisEntrTanque),
                        'Complemento' => $compEntr,
                    ],
                    'CONTROLDEEXISTENCIAS' => [
                        'ExistenciaInicialMes' => [
                            'ValorNumerico' => $this->round2($existInicial),
                            'UM'            => 'UM03',
                        ],
                        'ExistenciaFinalMes' => [
                            'ValorNumerico' => $this->round2($existFinal),
                            'UM'            => 'UM03',
                        ],
                        'FechaCorte' => $this->fmtIso($finMes, 'Y-m-d'),
                    ],
                ],
            ];
        }

        // Armado del objeto instalación (limpiando campos NO aplicables a almacenamiento)
        $caracter = $this->obtenerCaracter($dataGeneral);

        $respuesta = [
            'INSTALACION' => [
                'NombreORazonSocial' => $dataGeneral->razon_social ?? null,
                'RFC'                 => $dataGeneral->rfc ?? null,
                'CURP'                => $dataGeneral->curp ?? null,
                'Domicilio'           => [
                    'Calle'         => $dataGeneral->calle ?? null,
                    'NumeroExterior'=> $dataGeneral->numero_exterior ?? null,
                    'NumeroInterior'=> $dataGeneral->numero_interior ?? null,
                    'Colonia'       => $dataGeneral->colonia ?? null,
                    'Municipio'     => $dataGeneral->municipio ?? null,
                    'Entidad'       => $dataGeneral->entidad ?? null,
                    'CP'            => $dataGeneral->cp ?? null,
                ],
                'Georreferencias' => [
                    'Latitud'  => $dataGeneral->latitud ?? null,
                    'Longitud' => $dataGeneral->longitud ?? null,
                ],
                'Caracter' => $caracter,

                // >>> Campos de "instalación" depurados para almacenamiento <<<
                'NumeroPozos'                        => 0,
                'NumeroDuctosEntradaSalida'          => (int)($dataGeneral->numero_ductos_entrada_salida ?? 0), // si reportas líneas internas; si no, pon 0
                'NumeroDuctosTransporteDistribucion' => 0,
                'NumeroDispensarios'                 => 0,

                // Periodo de reporte
                'Periodo' => [
                    'Tipo'       => 'MENSUAL',
                    'Anio'       => $year,
                    'Mes'        => $month,
                    'FechaInicio'=> $this->fmtIso($inicioMes, 'Y-m-d'),
                    'FechaFin'   => $this->fmtIso($finMes,   'Y-m-d'),
                ],

                'TANQUE' => $reporteTanques,
                'BITACORA' => $bitacora,
            ],
        ];

        return response()->json($respuesta);
    }

    /**
     * Reporte DIARIO por fecha (estrictamente almacenamiento)
     */
    private function generarReporteDiarioPorFecha(int $idPlanta, string $fechaYmd)
    {
        $fecha = Carbon::parse($fechaYmd)->startOfDay();
        $inicio = (clone $fecha);
        $fin    = (clone $fecha)->endOfDay();

        $dataGeneral = InformacionGeneralReporte::where('id_planta', $idPlanta)->first();
        if (!$dataGeneral) {
            return response()->json(['error' => 'No existe InformacionGeneralReporte para la planta'], 404);
        }

        $tanques = Almacen::where('id_planta', $idPlanta)->get();
        $eventosDia = EventoAlmacen::where('id_planta', $idPlanta)
            ->whereBetween('fecha_inicio_evento', [$inicio, $fin])
            ->orderBy('fecha_inicio_evento', 'asc')
            ->get();

        $reporteTanques = [];
        foreach ($tanques as $t) {
            $evs = $eventosDia->where('id_almacen', $t->id);

            // Primer/último del día por tanque
            $primer = $evs->sortBy('fecha_inicio_evento')->first();
            $ultimo = $evs->sortByDesc('fecha_fin_evento')->first();

            $volRecep = (float) $evs->where('tipo_evento', 'entrada')->sum('volumen_movido');
            $volEnt   = (float) $evs->where('tipo_evento', 'salida')->sum('volumen_movido');

            $existInicial = (float) ($primer->volumen_inicial ?? 0);
            $existFinal   = (float) ($ultimo->volumen_final   ?? 0);

            // Complemento diario (por simplicidad, consolidado por tanque en el día)
            $cfdisRecepTanque = $this->buscarCfdisPorEventos($evs->where('tipo_evento', 'entrada')->pluck('id')->all());
            $cfdisEntrTanque  = $this->buscarCfdisPorEventos($evs->where('tipo_evento', 'salida')->pluck('id')->all());

            $compRecep = $this->buildComplementoAlmacenamiento([
                'cfdis'                    => $cfdisRecepTanque,
                'dictamen'                 => $this->obtenerDictamenDiario($idPlanta, $t->id, $inicio, $fin),
                'certificado'              => $this->obtenerCertificadoVigente($idPlanta),
                'transporte'               => $this->obtenerTransporteDiario($idPlanta, $t->id, $inicio, $fin),
                'trasvase'                 => $this->obtenerTrasvaseDiario($idPlanta, $t->id, $inicio, $fin),
                'rfc_cliente_proveedor'    => $dataGeneral->rfc_proveedor ?? null,
                'nombre_cliente_proveedor' => $dataGeneral->descripcion_instalacion ?? null,
                'permiso_proveedor'        => $dataGeneral->permiso_cre ?? null,
            ]);

            $compEntr = $this->buildComplementoAlmacenamiento([
                'cfdis'                    => $cfdisEntrTanque,
                'dictamen'                 => $this->obtenerDictamenDiario($idPlanta, $t->id, $inicio, $fin),
                'certificado'              => $this->obtenerCertificadoVigente($idPlanta),
                'transporte'               => $this->obtenerTransporteDiario($idPlanta, $t->id, $inicio, $fin),
                'trasvase'                 => $this->obtenerTrasvaseDiario($idPlanta, $t->id, $inicio, $fin),
                'rfc_cliente_proveedor'    => $dataGeneral->rfc_cliente ?? null,
                'nombre_cliente_proveedor' => $dataGeneral->nombre_cliente ?? null,
            ]);

            $reporteTanques[] = [
                'IdentificadorTanque' => $t->identificador ?? ("TQ-".$t->id),
                'REPORTEDEVOLUMENDIARIO' => [
                    'FechaReporte' => $this->fmtIso($fecha, 'Y-m-d'),
                    'RECEPCIONES' => [
                        'TotalRecepcionesDia' => (int) $evs->where('tipo_evento', 'entrada')->count(),
                        'VolumenRecepcionDia' => [
                            'ValorNumerico' => $this->round2($volRecep),
                            'UM'            => 'UM03',
                        ],
                        'FechaYHoraRecepcionDia' => $this->fmtIso($primer->fecha_inicio_evento ?? null, 'H:i:sP'),
                        'Complemento' => $compRecep,
                    ],
                    'ENTREGAS' => [
                        'TotalEntregasDia' => (int) $evs->where('tipo_evento', 'salida')->count(),
                        'VolumenEntregadoDia' => [
                            'ValorNumerico' => $this->round2($volEnt),
                            'UM'            => 'UM03',
                        ],
                        'FechaYHoraEntregaDia' => $this->fmtIso($ultimo->fecha_fin_evento ?? null, 'H:i:sP'),
                        'Complemento' => $compEntr,
                    ],
                    'CONTROLDEEXISTENCIAS' => [
                        'ExistenciaInicialDia' => [
                            'ValorNumerico' => $this->round2($existInicial),
                            'UM'            => 'UM03',
                        ],
                        'ExistenciaFinalDia' => [
                            'ValorNumerico' => $this->round2($existFinal),
                            'UM'            => 'UM03',
                        ],
                    ],
                ],
            ];
        }

        $caracter = $this->obtenerCaracter($dataGeneral);

        $respuesta = [
            'INSTALACION' => [
                'NombreORazonSocial' => $dataGeneral->razon_social ?? null,
                'RFC'                 => $dataGeneral->rfc ?? null,
                'Caracter'            => $caracter,
                'Periodo' => [
                    'Tipo'       => 'DIARIO',
                    'Fecha'      => $this->fmtIso($fecha, 'Y-m-d'),
                ],
                'TANQUE' => $reporteTanques,
            ],
        ];

        return response()->json($respuesta);
    }

    /**
     * Reportes DIARIOS del mes (lista)
     */
    private function generarReportesDiariosPorMes(int $idPlanta, int $year, int $month)
    {
        $inicioMes = Carbon::create($year, $month, 1, 0, 0, 0);
        $finMes    = (clone $inicioMes)->endOfMonth()->setTime(23, 59, 59);

        $fechas = [];
        $cursor = (clone $inicioMes);
        while ($cursor->lte($finMes)) {
            $fechas[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $lista = [];
        foreach ($fechas as $f) {
            $lista[$f] = $this->generarReporteDiarioPorFecha($idPlanta, $f)->getData(true);
        }

        return response()->json([
            'periodo' => [
                'year'  => $year,
                'month' => $month,
                'inicio'=> $inicioMes->toDateString(),
                'fin'   => $finMes->toDateString(),
            ],
            'reportes' => $lista,
        ]);
    }

    /* ============================================================
     * Helpers de Complemento Almacenamiento
     * ============================================================
     */

    /**
     * Constructor del Complemento Almacenamiento conforme a la guía.
     * $contexto keys: cfdis(Collection<Cfdi>), pedimentos(Collection), dictamen(obj), certificado(obj),
     *                 transporte(obj/array), trasvase(obj/array),
     *                 rfc_cliente_proveedor, nombre_cliente_proveedor, permiso_proveedor,
     *                 punto_internacion, pais_origen, medio_transporte_aduana, incoterms,
     *                 precio_importacion, volumen_importado
     */
    private function buildComplementoAlmacenamiento(array $contexto): array
    {
        $comp = [];

        // NACIONAL: CFDIs de compra/venta o servicios ligados al volumen
        if (!empty($contexto['cfdis']) && count($contexto['cfdis']) > 0) {
            $cfdisArr = [];
            foreach ($contexto['cfdis'] as $c) {
                $cfdisArr[] = [
                    'UUID'                     => $c->UUID,
                    'TipoCFDI'                 => $c->TipoCFDI ?? null,
                    'PrecioCompra'             => $c->PrecioCompra ?? null,
                    'Contraprestacion'         => $c->Contraprestacion ?? null,
                    'TarifaDeAlmacenamiento'   => $c->TarifaDeAlmacenamiento ?? null,
                    'CargoPorCapacidadAlmac'   => $c->CargoPorCapacidadAlmac ?? null,
                    'CargoPorUsoAlmac'         => $c->CargoPorUsoAlmac ?? null,
                    'CargoVolumetricoAlmac'    => $c->CargoVolumetricoAlmac ?? null,
                    'Descuento'                => $c->Descuento ?? null,
                    'FechaYHoraTransaccion'    => $this->fmtIso($c->FechaCFDI ?? null, 'Y-m-d\TH:i:s'),
                    'VolumenDocumentado'       => [
                        'ValorNumerico' => (float)($c->VolumenDocumentadoValor ?? $c->MontoTotalOperacion ?? 0),
                        'UM'            => 'UM03',
                    ],
                ];
            }

            $comp['NACIONAL'] = [
                'RfcClienteOProveedor'       => $contexto['rfc_cliente_proveedor'] ?? null,
                'NombreClienteOProveedor'    => $contexto['nombre_cliente_proveedor'] ?? null,
                'PermisoProveedor'           => $contexto['permiso_proveedor'] ?? null,
                'CFDIs' => [
                    'CFDI' => $cfdisArr,
                ],
            ];
        }

        // EXTRANJERO: si hubo importación/pedimentos
        if (!empty($contexto['pedimentos']) && count($contexto['pedimentos']) > 0) {
            $comp['EXTRANJERO'] = [
                'PermisoImportacion' => $contexto['permiso_importacion'] ?? null,
                'PEDIMENTOS' => [
                    'PuntoDeInternacion'       => $contexto['punto_internacion'] ?? null,
                    'PaisOrigen'               => $contexto['pais_origen'] ?? null,
                    'MedioDeTransEntraAduana'  => $contexto['medio_transporte_aduana'] ?? null,
                    'PedimentoAduanal'         => $contexto['pedimentos'][0]['numero'] ?? null,
                    'Incoterms'                => $contexto['incoterms'] ?? null,
                    'PrecioDeImportacion'      => $contexto['precio_importacion'] ?? null,
                    'VolumenDocumentado'       => [
                        'ValorNumerico' => (float)($contexto['volumen_importado'] ?? 0),
                        'UM'            => 'UM03',
                    ],
                ],
            ];
        }

        // DICTAMEN (lote / tipo de producto)
        if (!empty($contexto['dictamen'])) {
            $d = (object)$contexto['dictamen'];
            $comp['DICTAMEN'] = [
                'RfcDictamen'          => $d->rfc ?? 'XAX010101000', // genérica si aplica el caso previsto por la guía
                'LoteDictamen'         => $d->lote ?? null,
                'NumeroFolioDictamen'  => $d->folio ?? null,
                'FechaEmisionDictamen' => $this->fmtIso($d->fecha ?? null, 'Y-m-d'),
                'ResultadoDictamen'    => $d->resultado ?? null,
            ];
        }

        // CERTIFICADO (verificación anual)
        if (!empty($contexto['certificado'])) {
            $c = (object)$contexto['certificado'];
            $comp['CERTIFICADO'] = [
                'RfcCertificado'          => $c->rfc ?? null,
                'NumeroFolioCertificado'  => $c->folio ?? null,
                'FechaEmisionCertificado' => $this->fmtIso($c->fecha ?? null, 'Y-m-d'),
                'ResultadoCertificado'    => $c->resultado ?? null,
            ];
        }

        // TRANSPORTE (si se contrató para la recepción/entrega)
        if (!empty($contexto['transporte'])) {
            $t = (object)$contexto['transporte'];
            $comp['TRANSPORTE'] = [
                'Transporte' => [
                    'PermisoTransporte'      => $t->permiso ?? null,
                    'ClaveDeVehiculo'        => $t->vehiculo ?? null, // para medios distintos a ducto
                    'TarifaDeTransporte'     => $t->tarifa ?? 0,
                    'CargoPorCapacidadTrans' => $t->cargo_capacidad ?? null, // ductos
                    'CargoPorUsoTrans'       => $t->cargo_uso ?? null,       // ductos
                    'CargoVolumetricoTrans'  => $t->cargo_volumetrico ?? null, // ductos
                ],
            ];
        }

        // TRASVASE (si se contrató)
        if (!empty($contexto['trasvase'])) {
            $tv = (object)$contexto['trasvase'];
            $comp['TRASVASE'] = [
                'NombreTrasvase'      => $tv->nombre ?? null,
                'RfcTrasvase'         => $tv->rfc ?? null,
                'PermisoTrasvase'     => $tv->permiso ?? null,
                'DescripcionTrasvase' => $tv->descripcion ?? null,
                'CfdiTrasvase'        => $tv->uuid_cfdi ?? null,
            ];
        }

        return $comp;
    }

    /* ============================================================
     * Helpers de obtención de datos (stub: conéctalos a tus tablas)
     * ============================================================
     */

    private function buscarCfdisPorEventos(array $eventoIds)
    {
        if (empty($eventoIds)) return collect();
        // Ajusta: si tu Cfdi tiene campo evento_id
        return Cfdi::whereIn('evento_id', $eventoIds)->get();
    }

    private function obtenerDictamenMensual(int $idPlanta, int $idTanque, Carbon $ini, Carbon $fin)
    {
        // TODO: Conectar a tu tabla de dictámenes (o instrumentos en línea)
        return null;
    }

    private function obtenerDictamenDiario(int $idPlanta, int $idTanque, Carbon $ini, Carbon $fin)
    {
        // TODO: Conectar a tu tabla de dictámenes (o instrumentos en línea)
        return null;
    }

    private function obtenerCertificadoVigente(int $idPlanta)
    {
        // TODO: Conectar a tu tabla de certificados (anual, vigente a la fecha/periodo)
        return null;
    }

    private function obtenerTransporteMensual(int $idPlanta, int $idTanque, Carbon $ini, Carbon $fin)
    {
        // TODO: Conectar a tu tabla de contratos/servicios de transporte (si se contrató)
        return null;
    }

    private function obtenerTransporteDiario(int $idPlanta, int $idTanque, Carbon $ini, Carbon $fin)
    {
        // TODO
        return null;
    }

    private function obtenerTrasvaseMensual(int $idPlanta, int $idTanque, Carbon $ini, Carbon $fin)
    {
        // TODO: Conectar a tu tabla de trasvase (si se contrató)
        return null;
    }

    private function obtenerTrasvaseDiario(int $idPlanta, int $idTanque, Carbon $ini, Carbon $fin)
    {
        // TODO
        return null;
    }

    /* ============================================================
     * Utilerías
     * ============================================================
     */

    private function toCarbonOrNull($value): ?Carbon
    {
        if (!$value) return null;
        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function fmtIso($value, string $format = 'Y-m-d\TH:i:sP'): ?string
    {
        $c = $this->toCarbonOrNull($value);
        return $c ? $c->format($format) : null;
    }

    private function round2($n): float
    {
        return round((float)$n, 2);
    }

    private function sumImporteCfdis($cfdis): float
    {
        if (!$cfdis || count($cfdis) === 0) return 0.0;
        // Ajusta el campo del importe total según tu modelo
        return (float) collect($cfdis)->sum(function ($c) {
            return (float) ($c->MontoTotalOperacion ?? 0);
        });
    }

    /**
     * Construye el nodo "Caracter" según la guía (permisionario/asignatario-contratista/usuario).
     * Ajusta estos campos a los de tu modelo InformacionGeneralReporte.
     */
    private function obtenerCaracter($dataGeneral): array
    {
        $tipo = $dataGeneral->tipo_caracter ?? 'PERMISIONARIO'; // PERMISIONARIO | ASIGNATARIO_O_CONTRATISTA | USUARIO
        $out = ['TipoCaracter' => $tipo];

        if ($tipo === 'PERMISIONARIO') {
            $out['Modalidad']     = $dataGeneral->modalidad ?? null; // p.ej. ALMACENAMIENTO
            $out['PermisoCRE']    = $dataGeneral->permiso_cre ?? null;
        } elseif ($tipo === 'ASIGNATARIO_O_CONTRATISTA') {
            $out['NumeroContratoAsignacion'] = $dataGeneral->numero_contrato_asignacion ?? null;
            $out['Tipo'] = $dataGeneral->tipo_asignacion ?? null; // ASIGNATARIO o CONTRATISTA
        } elseif ($tipo === 'USUARIO') {
            // usuarios de gas natural (aprovechamiento/recepción fija de gas natural)
            $out['InstalacionGasNatural'] = [
                'Nombre' => $dataGeneral->descripcion_instalacion ?? null,
                'RFC'    => $dataGeneral->rfc ?? null,
            ];
        }

        return $out;
    }
}
