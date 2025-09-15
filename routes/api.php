<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginRegisterController;
use App\Http\Controllers\PipaController;
use App\Http\Controllers\RegistroLlenadoAlmacenController;
use App\Http\Controllers\ReporteVolumetrico;
use App\Http\Controllers\RegistroEntradasSalidasPipaController;
use App\Http\Controllers\InformacionGeneralReporteController;
use App\Http\Controllers\GenReporteVolumetricoController;
use App\Http\Controllers\RolesUsuariosController;
use App\Http\Controllers\PlantaGasController;
use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BitacoraEventosController;
use App\Http\Controllers\EventoAlmacenController;
use App\Http\Controllers\ExistenciaAlmacenController;
use App\Http\Controllers\CfdiController;

// Rutas publicas para acceder o registrar una cuenta
Route::controller(LoginRegisterController::class)->group(function() {
    Route::post('/local/auth/register', 'register');
    Route::post('/local/auth/login', 'login');
});

// Protected routes of product and logout
Route::middleware('auth:sanctum')->group( function () {
    Route::post('/local/auth/logout', [LoginRegisterController::class, 'logout']);

    // INFORMACIÓN DE ROLES Y PLANTA
    
    Route::controller(RolesUsuariosController::class)->group(function() {
        Route::get('/v1/rol', 'index');
        Route::get('/v1/rol/{id}', 'show');
        Route::post('/v1/rol', 'store');
        Route::post('/v1/rol/{id}', 'update');
        Route::delete('/v1/rol/{id}', 'destroy');
    });
    
    Route::controller(PlantaGasController::class)->group(function() {
        Route::get('/v1/planta', 'index');
        Route::get('/v1/planta/{id}', 'show');
        Route::post('/v1/planta', 'store');
        Route::post('/v1/planta/{id}', 'update');
        Route::delete('/v1/planta/{id}', 'destroy');
    });

    // Rutas en las que se realiozará todas las operaciones siempre y cuenado el usuario este logueado
    // Rutas para el medidor de turbina

    // Ruta para la informacion de pipas
    Route::controller(PipaController::class)->group(function(){
        Route::get('/v1/pipa/{idPlanta}', 'index');
        Route::get('/v1/pipa/{id}', 'show');
        Route::post('/v1/pipa', 'store');
        Route::post('/v1/pipa/{id}', 'update');
        Route::delete('/v1/pipa/{id}', 'destroy');
    });

    //Registro llenado del almacen
    Route::controller(RegistroLlenadoAlmacenController::class)->group(function(){
        Route::get('/v1/almacen-registro/{idPlanta}', 'index');
        Route::get('/v1/almacen-registro/{id}', 'show');
        Route::post('/v1/almacen-registro', 'store');
        Route::post('/v1/almacen-registro/{id}', 'update');
        Route::delete('/v1/almacen-registro/{id}', 'destroy'); 
    });

    //Registro para el reporte volumetrico
    Route::controller(ReporteVolumetrico::class)->group(function(){
        Route::get('/v1/reporte/volumetrico/{idPlanta}', 'index');
        Route::get('/v1/reporte/volumetrico/{id}', 'show');
        Route::post('/v1/reporte/volumetrico', 'store');
        Route::post('/v1/reporte/volumetrico/{id}', 'update');
        Route::delete('/v1/reporte/volumetrico/{id}', 'destroy');
    });

    //Registro para entradas y salidas
    Route::controller(RegistroEntradasSalidasPipaController::class)->group(function(){
        Route::get('/v1/entrada-salida-pipa/registro/{idPlanta}', 'index');
        Route::get('/v1/entrada-salida-pipa/registro/{id}', 'show');
        Route::post('/v1/entrada-salida-pipa/registro', 'store');
        Route::post('/v1/entrada-salida-pipa/registro/{id}', 'update');
        Route::delete('/v1/entrada-salida-pipa/registro/{id}', 'destroy'); 
    });

    //Registro de mantetnimiento del medidor

    //Registro Información Generar del reporte
    Route::controller(InformacionGeneralReporteController::class)->group(function() {
        Route::get('/v1/reporteVolumetrico/informacion-general/{idPlanta}', 'index');
        Route::get('/v1/reporteVolumetrico/informacion-general/{id}', 'show');
        Route::post('/v1/reporteVolumetrico/informacion-general', 'store');
        Route::post('/v1/reporteVolumetrico/informacion-general/{id}', 'update');
        Route::delete('/v1/reporteVolumetrico/informacion-general/{id}', 'destroy');
    });

    Route::controller(AlmacenController::class)->group(function() {
        Route::get('/v1/almacen/{idPlanta}', 'index');
        Route::get('/v1/almacen/{id}', 'show');
        Route::post('/v1/almacen', 'store');
        Route::post('/v1/almacen/{id}', 'update');
        Route::delete('/v1/almacen/{id}', 'destroy');
    });

    Route::controller(GenReporteVolumetricoController::class)->group(function() {
        Route::get('/v1/generar-reporte/{idPlanta}/{yearAndMonth}/{tipoDM}', 'generarReporte');
        Route::get('/v1/ConsultCDFI', 'consultarCFDI');
    });

    Route::controller(UserController::class)->group(function() {
        Route::get('/v1/usuario/{idPlanta}', 'index');
        Route::get('/v1/usuario/{idPlanta}/{idUsuario}', 'show');
        Route::post('/v1/usuario', 'store');
        Route::post('/v1/usuario/{id}', 'update');
        Route::delete('/v1/usuario/{id}', 'destroy');
    });

    Route::controller(BitacoraEventosController::class)->group(function() {
        Route::get('/v1/bitacoraEventos/{idPlanta}', 'index');
        Route::get('/v1/bitacoraEventos/{idPlanta}/{id}', 'show');
        Route::post('/v1/bitacoraEventos', 'store');
        Route::post('/v1/bitacoraEventos/{id}', 'update');
        Route::delete('/v1/bitacoraEventos/{id}', 'destroy');
    });

    Route::controller(EventoAlmacenController::class)->group(function() {
        Route::get('/v1/eventoAlmacen/{idPlanta}', 'index');
        Route::get('/v1/eventoAlmacen/{idPlanta}/{id}', 'show');
        Route::post('/v1/eventoAlmacen', 'store');
        Route::post('/v1/eventoAlmacen/{id}', 'update');
        Route::delete('/v1/eventoAlmacen/{id}', 'destroy');
    });

    Route::controller(ExistenciaAlmacenController::class)->group(function() {
        Route::get('/v1/existenciaAlmacen/{idPlanta}', 'index');
        Route::get('/v1/existenciaAlmacen/{idPlanta}/{id}', 'show');
        Route::get('/v1/existenciaAlmacen/verificar/{id}', 'verificar');
        Route::post('/v1/existenciaAlmacen', 'store');
        Route::post('/v1/existenciaAlmacen/{id}', 'update');
    });

    Route::controller(CfdiController::class)->group(function() {
        Route::get('/v1/cfdis/{idPlanta}', 'index');
        Route::post('/v1/cfdis', 'store');
    });
    
});
