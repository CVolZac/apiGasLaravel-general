<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\InformacionGeneralReporte;
use App\Models\EventoAlmacen;
use App\Models\BitacoraEventos;
use App\Models\Almacen;
use App\Models\Cfdi; // Si tu modelo es Cfdis, cámbialo aquí y abajo.
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
            return $this->generarReporteMensualAlmacen($idPlanta, $year, $month);
        } elseif ($tipoDM == 1) {
            // Antes fallaba si no venía ?fecha=
            $fecha = request()->query('fecha');
            if (!$fecha) {
                $fecha = Carbon::now('America/Mexico_City')->toDateString();
            }
            return $this->generarReporteDiarioPorFecha($idPlanta, $fecha);
        } elseif ($tipoDM == 2) {
            return $this->generarReportesDiariosPorMes($idPlanta, $year, $month);
        }

        return response()->json(['error' => 'tipoDM inválido'], 400);
    }

    /* ==================== MENSUAL ==================== */
    private function generarReporteMensualAlmacen(int $idPlanta, int $year, int $month)
    {
        $inicioMes = Carbon::create($year, $month, 1, 0, 0, 0, 'America/Mexico_City');
        $finMes    = (clone $inicioMes)->endOfMonth()->setTime(23, 59, 59);

        $dataGeneral = InformacionGeneralReporte::where('id_planta', $idPlanta)->first();
        if (!$dataGeneral) {
            return response()->json(['error' => 'No existe InformacionGeneralReporte para la planta'], 404);
        }

        // IDs de tanques por planta (soporta id_planta o planta_id)
        [$tanques, $idsTanques] = $this->idsTanquesPorPlanta($idPlanta);

        // Eventos del mes (por id_almacen)
        $eventosMes = EventoAlmacen::whereIn('id_almacen', $idsTanques)
            ->whereBetween('fecha_inicio_evento', [$inicioMes, $finMes])
            ->orderBy('fecha_inicio_evento', 'asc')
            ->get();

        // Bitácora del mes (detecta columna disponible)
        $bitacoraQuery = BitacoraEventos::query();
        $this->aplicarFiltroBitacoraPorPlantaOTanques($bitacoraQuery, $idPlanta, $idsTanques);
        $bitacora = $bitacoraQuery
            ->whereBetween('fecha_evento', [$inicioMes, $finMes])
            ->orderBy('fecha_evento', 'asc')
            ->get()
            ->map(function ($b) {
                return [
                    'Evento'     => $b->evento ?? null,
                    'Descripcion' => $b->descripcion ?? null,
                    'FechaHora'  => $this->fmtIso($b->fecha_evento, 'Y-m-d\TH:i:sP'),
                ];
            });

        $reporteTanques = [];
        foreach ($tanques as $t) {
            $evs = $eventosMes->where('id_almacen', $t->id);

            $primer = $evs->sortBy('fecha_inicio_evento')->first();
            $ultimo = $evs->sortByDesc('fecha_fin_evento')->first();

            $volRecep = (float) $evs->where('tipo_evento', 'entrada')->sum('volumen_movido');
            $volEnt   = (float) $evs->where('tipo_evento', 'salida')->sum('volumen_movido');

            $existInicial = (float) ($primer->volumen_inicial ?? 0);
            $existFinal   = (float) ($ultimo->volumen_final   ?? 0);

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
                'permiso_proveedor'         => $dataGeneral->permiso_cre ?? null,
            ]);

            $compEntr = $this->buildComplementoAlmacenamiento([
                'cfdis'                     => $cfdisEntrTanque,
                'dictamen'                  => $this->obtenerDictamenMensual($idPlanta, $t->id, $inicioMes, $finMes),
                'certificado'               => $this->obtenerCertificadoVigente($idPlanta),
                'transporte'                => $this->obtenerTransporteMensual($idPlanta, $t->id, $inicioMes, $finMes),
                'trasvase'                  => $this->obtenerTrasvaseMensual($idPlanta, $t->id, $inicioMes, $finMes),
                'rfc_cliente_proveedor'     => $dataGeneral->rfc_cliente ?? null,
                'nombre_cliente_proveedor'  => $dataGeneral->nombre_cliente ?? null,
            ]);

            $reporteTanques[] = [
                'IdentificadorTanque' => $t->identificador ?? ("TQ-" . $t->id),
                'CapacidadTotal' => [
                    'ValorNumerico'   => (float) ($t->capacidad_total ?? 0),
                    'UnidadDeMedida'  => 'UM03',
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
                            'ValorNumerico'  => 11500,
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

        $caracter = $this->obtenerCaracter($dataGeneral);

        $respuesta = [
            'INSTALACION' => [
                'NombreORazonSocial' => $dataGeneral->razon_social ?? null,
                'RFC'                 => $dataGeneral->rfc ?? null,
                'CURP'                => $dataGeneral->curp ?? null,
                'Domicilio'           => [
                    'Calle'         => $dataGeneral->calle ?? null,
                    'NumeroExterior' => $dataGeneral->numero_exterior ?? null,
                    'NumeroInterior' => $dataGeneral->numero_interior ?? null,
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

                // Solo almacenamiento
                'NumeroPozos'                        => 0,
                'NumeroDuctosEntradaSalida'          => (int)($dataGeneral->numero_ductos_entrada_salida ?? 0),
                'NumeroDuctosTransporteDistribucion' => 0,
                'NumeroDispensarios'                 => 0,

                'Periodo' => [
                    'Tipo'       => 'MENSUAL',
                    'Anio'       => $year,
                    'Mes'        => $month,
                    'FechaInicio' => $this->fmtIso($inicioMes, 'Y-m-d'),
                    'FechaFin'   => $this->fmtIso($finMes,   'Y-m-d'),
                ],

                'TANQUE'   => $reporteTanques,
                'BITACORA' => $bitacora,
            ],
        ];

        return response()->json($respuesta);
    }

    /* ==================== DIARIO ==================== */
    private function generarReporteDiarioPorFecha(int $idPlanta, string $fechaYmd)
    {
        $fecha  = Carbon::parse($fechaYmd, 'America/Mexico_City')->startOfDay();
        $inicio = (clone $fecha);
        $fin    = (clone $fecha)->endOfDay();

        $dataGeneral = InformacionGeneralReporte::where('id_planta', $idPlanta)->first();
        if (!$dataGeneral) {
            return response()->json(['error' => 'No existe InformacionGeneralReporte para la planta'], 404);
        }

        [$tanques, $idsTanques] = $this->idsTanquesPorPlanta($idPlanta);

        $eventosDia = EventoAlmacen::whereIn('id_almacen', $idsTanques)
            ->whereBetween('fecha_inicio_evento', [$inicio, $fin])
            ->orderBy('fecha_inicio_evento', 'asc')
            ->get();

        $reporteTanques = [];
        foreach ($tanques as $t) {
            $evs = $eventosDia->where('id_almacen', $t->id);

            $primer = $evs->sortBy('fecha_inicio_evento')->first();
            $ultimo = $evs->sortByDesc('fecha_fin_evento')->first();

            $volRecep = (float) $evs->where('tipo_evento', 'entrada')->sum('volumen_movido');
            $volEnt   = (float) $evs->where('tipo_evento', 'salida')->sum('volumen_movido');

            $existInicial = (float) ($primer->volumen_inicial ?? 0);
            $existFinal   = (float) ($ultimo->volumen_final   ?? 0);

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
                'IdentificadorTanque'      => $t->identificador ?? ("TQ-" . $t->id),
                'REPORTEDEVOLUMENDIARIO'   => [
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

    /* ==================== SERIE DIARIA POR MES ==================== */
    private function generarReportesDiariosPorMes(int $idPlanta, int $year, int $month)
    {
        $inicioMes = Carbon::create($year, $month, 1, 0, 0, 0, 'America/Mexico_City');
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
                'inicio' => $inicioMes->toDateString(),
                'fin'   => $finMes->toDateString(),
            ],
            'reportes' => $lista,
        ]);
    }

    /* ==================== Complemento Almacenamiento ==================== */
    private function buildComplementoAlmacenamiento(array $contexto): array
    {
        $comp = [];

        // NACIONAL (CFDIs)
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
                'CFDIs' => ['CFDI' => $cfdisArr],
            ];
        }

        // EXTRANJERO (si aplica importación/pedimentos)
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

        // DICTAMEN (si aplica)
        if (!empty($contexto['dictamen'])) {
            $d = (object)$contexto['dictamen'];
            $comp['DICTAMEN'] = [
                'RfcDictamen'          => $d->rfc ?? 'XAX010101000',
                'LoteDictamen'         => $d->lote ?? null,
                'NumeroFolioDictamen'  => $d->folio ?? null,
                'FechaEmisionDictamen' => $this->fmtIso($d->fecha ?? null, 'Y-m-d'),
                'ResultadoDictamen'    => $d->resultado ?? null,
            ];
        }

        // CERTIFICADO (si aplica)
        if (!empty($contexto['certificado'])) {
            $c = (object)$contexto['certificado'];
            $comp['CERTIFICADO'] = [
                'RfcCertificado'          => $c->rfc ?? null,
                'NumeroFolioCertificado'  => $c->folio ?? null,
                'FechaEmisionCertificado' => $this->fmtIso($c->fecha ?? null, 'Y-m-d'),
                'ResultadoCertificado'    => $c->resultado ?? null,
            ];
        }

        // TRANSPORTE (si se contrató)
        if (!empty($contexto['transporte'])) {
            $t = (object)$contexto['transporte'];
            $comp['TRANSPORTE'] = [
                'Transporte' => [
                    'PermisoTransporte'      => $t->permiso ?? null,
                    'ClaveDeVehiculo'        => $t->vehiculo ?? null,
                    'TarifaDeTransporte'     => $t->tarifa ?? 0,
                    'CargoPorCapacidadTrans' => $t->cargo_capacidad ?? null,
                    'CargoPorUsoTrans'       => $t->cargo_uso ?? null,
                    'CargoVolumetricoTrans'  => $t->cargo_volumetrico ?? null,
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

    /* ==================== Helpers datos ==================== */
    private function buscarCfdisPorEventos(array $eventoIds)
    {
        if (empty($eventoIds)) return collect();
        return Cfdi::whereIn('evento_id', $eventoIds)->get(); // Ajusta si tu campo difiere
    }

    private function obtenerDictamenMensual(int $idPlanta, int $idTanque, Carbon $ini, Carbon $fin)
    {
        return null;
    }
    private function obtenerDictamenDiario(int $idPlanta, int $idTanque, Carbon $ini, Carbon $fin)
    {
        return null;
    }
    private function obtenerCertificadoVigente(int $idPlanta)
    {
        return null;
    }
    private function obtenerTransporteMensual(int $idPlanta, int $idTanque, Carbon $ini, Carbon $fin)
    {
        return null;
    }
    private function obtenerTransporteDiario(int $idPlanta, int $idTanque, Carbon $ini, Carbon $fin)
    {
        return null;
    }
    private function obtenerTrasvaseMensual(int $idPlanta, int $idTanque, Carbon $ini, Carbon $fin)
    {
        return null;
    }
    private function obtenerTrasvaseDiario(int $idPlanta, int $idTanque, Carbon $ini, Carbon $fin)
    {
        return null;
    }

    /* ==================== Utilerías ==================== */
    private function idsTanquesPorPlanta(int $idPlanta): array
    {
        // Soporta esquemas distintos: 'id_planta' o 'planta_id'
        $tanquesQuery = Almacen::query();
        if (Schema::hasColumn((new Almacen)->getTable(), 'id_planta')) {
            $tanquesQuery->where('id_planta', $idPlanta);
        } elseif (Schema::hasColumn((new Almacen)->getTable(), 'planta_id')) {
            $tanquesQuery->where('planta_id', $idPlanta);
        } else {
            // Último recurso: todos los tanques (evitar 0 resultados en entornos variados)
            $tanquesQuery->whereRaw('1=1');
        }
        $tanques = $tanquesQuery->get();
        return [$tanques, $tanques->pluck('id')->all()];
    }

    private function aplicarFiltroBitacoraPorPlantaOTanques($query, int $idPlanta, array $idsTanques): void
    {
        $table = (new BitacoraEventos)->getTable(); // p.ej. 'bitacora_eventos'
        // Preferencia: por id_almacen si existe; si no, almacen_id; si no, por id_planta; si no, sin filtro por tanque
        if (!empty($idsTanques)) {
            if (Schema::hasColumn($table, 'id_almacen')) {
                $query->whereIn('id_almacen', $idsTanques);
                return;
            }
            if (Schema::hasColumn($table, 'almacen_id')) {
                $query->whereIn('almacen_id', $idsTanques);
                return;
            }
        }
        if (Schema::hasColumn($table, 'id_planta')) {
            $query->where('id_planta', $idPlanta);
            return;
        }
        // Si tu esquema usa otro nombre, agrega un elseif aquí.
        // Como último recurso, no filtro por planta/tanque (solo por fechas) para no romper.
    }

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
        return (float) collect($cfdis)->sum(fn($c) => (float) ($c->MontoTotalOperacion ?? 0));
    }

    private function obtenerCaracter($dataGeneral): array
    {
        $tipo = $dataGeneral->tipo_caracter ?? 'PERMISIONARIO';
        $out = ['TipoCaracter' => $tipo];

        if ($tipo === 'PERMISIONARIO') {
            $out['Modalidad']  = $dataGeneral->modalidad ?? 'ALMACENAMIENTO'; // <= aquí va "="
            $out['PermisoCRE'] = $dataGeneral->permiso_cre ?? null;           // <= aquí va "="
        } elseif ($tipo === 'ASIGNATARIO_O_CONTRATISTA') {
            $out['NumeroContratoAsignacion'] = $dataGeneral->numero_contrato_asignacion ?? null;
            $out['Tipo'] = $dataGeneral->tipo_asignacion ?? null;
        } elseif ($tipo === 'USUARIO') {
            $out['InstalacionGasNatural'] = [
                'Nombre' => $dataGeneral->descripcion_instalacion ?? null,
                'RFC'    => $dataGeneral->rfc ?? null,
            ];
        }

        return $out;
    }
}
