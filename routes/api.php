<?php

use Illuminate\Http\Request;
use App\Models\Ventadet;

use App\Exports\VentasGeneralExport;
use Maatwebsite\Excel\Facades\Excel;
// use Excel;
// use Fpdf;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
 */

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();    
});

Route::get('/pdf', function (Request $request) {
    Fpdf::AddPage();
    Fpdf::SetFont('Courier', 'B', 18);
    Fpdf::Cell(50, 25, 'Hello World!');
    Fpdf::Output();
    exit;
});

Route::get('/phpinfo', function (Request $request) {



    return phpinfo();
    exit;
    // return Excel::download(new VentasGeneralExport, 'users.xlsx');

    $collection = collect(['taylor', 'abigail', null])->map(function ($name) {return strtoupper($name);})->reject(function ($name) {return empty($name);});
    dd($collection->all());

    // Illuminate\Support\Collection
    $data = \DB::table('ventadet')
            ->select('idproducto')
            ->where('idproducto', 143)
            ->whereNull('deleted')
            ->get();
    // dd($data);

    // Illuminate\Database\Eloquent\Collection
    $data = Ventadet::where('idproducto', 143)->get();
    dd($data);

    if (!$data->isEmpty()) {
        \Log::info(print_r($id, true));
        \Log::info(print_r($data, true));
        return ['validator' => true, 'message' => 'Tiene ventass. No puede ser eliminado.'];
    } 

    return phpinfo();
});

Route::get('/copiafirma', function (Request $request) {
    // '2019-07-01', '2019-07-31' Julio // 4134 //581
    // '2019-08-01', '2019-08-31' Agosto // 42 //2
    // '2019-09-01', '2019-10-30' Setiembre // 41 //0

    $data = \DB::table('terapia')
            ->select('idterapia', 'idsede', 'firma', 'fecha', 'idpaciente')
            ->whereBetween('fecha', ['2019-07-01', '2019-07-31'])
            ->where('idestado', 38)
            ->whereNull('firma')
            ->whereNull('deleted')
            ->count();
    
    dd($data);
    
    foreach ($data as $value) {  

        $terapia = \DB::table('terapia')
            ->select('idterapia', 'firma')
            ->where('idpaciente', '=', $value->idpaciente)
            ->where('idestado', 38)
            ->whereNotNull('firma')
            ->whereNull('deleted')
            ->orderBy('idterapia', 'desc')
            ->first();
        
        if ($terapia) {

            $update = [
                    'identidadfirma' => 4844,
                    'fechafirma' => '2020-02-11',
                    'firma' => $terapia->firma,
                    'firmaterapia' => $terapia->idterapia
            ]; 

            \DB::table('terapia')
                ->where(['idterapia' => $value->idterapia])
                ->update($update);
        }
        // dd($terapia, $update);
    } 

    dd('Excelente'); 

    return phpinfo();
    exit;
});

//CronJobs
Route::get('/cerrarciclos', ['middleware' => 'cors', 'uses' => 'entidadController@cerrarCiclosdeatencionV2']);
Route::get('/setearcitaaciclo', ['middleware' => 'cors', 'uses' => 'entidadController@setearCitaACiclo']);
Route::get('/definircmprincipalenciclo', ['middleware' => 'cors', 'uses' => 'entidadController@definirCmPrincipalEnCiclo']);

Route::get('/mensajetexto', ['middleware' => 'cors', 'uses' => 'cronController@store']);
Route::get('/mensajetextodiario', ['middleware' => 'cors', 'uses' => 'cronController@storediario']);
Route::get('/citasatendidas', ['middleware' => 'cors', 'uses' => 'cronController@storeAtendidos']);
Route::get('/setearseguroacitaterapeutica', ['middleware' => 'cors', 'uses' => 'cronController@setearSeguroaCitaTerapeutica']);

// Cron activos en VPS
Route::get('/setearcitaterapeuticaparaterapia', ['middleware' => 'cors', 'uses' => 'cronController@SetearCitaTerapeuticaParaTerapia']);
Route::get('/automatizacionestado', ['middleware' => 'cors', 'uses' => 'cronController@automatizacionCantEstado']);
Route::get('/enviaremailinvoice', ['middleware' => 'cors', 'uses' => 'cronController@enviarEmailInvoice']);
Route::get('/regenerarpdf', ['middleware' => 'cors', 'uses' => 'cronController@reGenerarPDF']);
Route::get('/automatizacion1', ['middleware' => 'cors', 'uses' => 'cronController@automatizacion1']);
Route::get('/automatizacion2', ['middleware' => 'cors', 'uses' => 'cronController@automatizacion2']);
Route::get('/automatizacion3', ['middleware' => 'cors', 'uses' => 'cronController@automatizacion3']);
Route::get('/automatizacion4', ['middleware' => 'cors', 'uses' => 'cronController@automatizacion4']);
Route::get('/eliminarcitasterapeutas', ['middleware' => 'cors', 'uses' => 'cronController@eliminarCitasterapeutas']);

Route::post('/receivesms', ['uses' => 'cronController@receivesms']);
Route::get('/testeocron', ['uses' => 'ventaController@pdf']);
// Route::get('/insistenciacitamedica', ['middleware' => 'cors', 'uses' => 'cronController@insistenciaCitamedica']);
// Route::get('/{enterprise}/actcicloatencion', ['uses' => 'terapiaController@actcicloatencion']);

//Empresa
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/empresa/home', 'empresaController@home'); //ok
    Route::get('/{enterprise}/empresa', 'empresaController@show'); //ok
    Route::post('/{enterprise}/empresa', 'empresaController@update'); //ok
    Route::post('/{enterprise}/empresa/upload', 'empresaController@upload'); //ok
    Route::post('/{enterprise}/empresa/uploadtmp', 'empresaController@uploadtmp'); //ok
    Route::post('/{enterprise}/empresa/uploadtmpfile', 'empresaController@uploadtmpFile'); //ok
    Route::post('/{enterprise}/empresa/generarcargo', 'empresaController@generarCargo'); //ok
});
//End Empresa

//Entidad
Route::post('/{enterprise}/authenticate', ['middleware' => 'cors', 'uses' => 'entidadController@authenticate']); //ok

Route::post('/{enterprise}/entidad/paciente', ['middleware' => 'cors', 'uses' =>'entidadController@storePaciente']); //ok

Route::post('/{enterprise}/entidad/confirm/{tokenconfirm}', ['middleware' => 'cors', 'uses' =>'entidadController@pacienteToken']); //ok

Route::post('/{enterprise}/entidad/recovery', ['middleware' => 'cors', 'uses' =>'entidadController@recovery']); //ok

Route::post('/logout', ['middleware' => 'cors', 'uses' => 'entidadController@logout']); //ok
Route::group(['middleware' => ['cors', 'ch.token']], function ($app) { //'cors', 'ch.token'
    Route::get('/{enterprise}/entidad/construct', 'entidadController@construct'); //ok
    Route::get('/{enterprise}/entidad/modulos', 'entidadController@modulos'); //ok
    Route::get('/{enterprise}/entidad', 'entidadController@index'); //ok
    Route::get('/{enterprise}/entidad/search', 'entidadController@search'); //ok
    Route::get('/{enterprise}/entidad/cumpleanos', 'entidadController@cumpleanos'); //ok
    Route::get('/{enterprise}/entidad/new', 'entidadController@newentidad'); //ok
    Route::get('/{enterprise}/entidad/pacientehistorias', 'entidadController@pacientehistorias'); //ok
    Route::get('/{enterprise}/entidad/{id}', 'entidadController@show'); //ok
    Route::get('/{enterprise}/entidad/profile/{id}', 'entidadController@showprofile'); //ok
    Route::get('/{enterprise}/entidad/documento/nro', 'entidadController@nrodocumento'); //ok
    Route::get('/{enterprise}/entidad/documento/nrohc', 'entidadController@nrodocumentohc'); //ok
    Route::post('/{enterprise}/entidad', 'entidadController@store'); //ok    
    Route::post('/{enterprise}/entidad/generarhc', 'entidadController@generarhc'); //ok
    Route::post('/{enterprise}/entidad/unirduplicados', 'entidadController@unirduplicados'); //ok
    Route::post('/{enterprise}/entidad/{id}', 'entidadController@update'); //ok
    Route::post('/{enterprise}/entidad/profile/{id}', 'entidadController@updateProfile'); //ok
    Route::post('/{enterprise}/entidad/delete/{id}', 'entidadController@destroy'); //ok
    Route::post('/{enterprise}/entidad/subentidad/{id}', 'entidadController@updatesubentidad'); //ok
    Route::post('/{enterprise}/entidad/password/{id}', 'entidadController@updatepassword'); //ok
});
//End Entidad

//Categoria
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/categoria', 'arbolController@index'); //ok
    Route::post('/{enterprise}/categoria', 'arbolController@store'); //ok
    Route::post('/{enterprise}/categoria/{id}', 'arbolController@update'); //ok
    Route::post('/{enterprise}/categoria/delete/{id}', 'arbolController@destroy'); //ok
    Route::get('/{enterprise}/categoria/descarga', 'arbolController@descarga'); //ok
});
//End Categoria

//Producto
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/producto', 'productoController@index'); //ok
    Route::get('/{enterprise}/producto/tarifas', 'productoController@indextarifas'); //ok
    Route::get('/{enterprise}/producto/metas', 'productoController@metas'); //ok
    Route::get('/{enterprise}/producto/new', 'productoController@newproducto'); //ok
    Route::get('/{enterprise}/producto/{id}', 'productoController@show'); //ok
    Route::get('/{enterprise}/producto/{id}/material', 'productoController@material');
    Route::post('/{enterprise}/producto', 'productoController@store'); //ok
    Route::post('/{enterprise}/producto/{id}', 'productoController@update'); //ok
    Route::post('/{enterprise}/producto/delete/{id}', 'productoController@destroy'); //ok
});
//End Producto

//Publicacion
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/publicacion', 'publicacionController@index'); //ok
    Route::get('/{enterprise}/publicacion/new', 'publicacionController@newpublicacion'); //ok
    Route::get('/{enterprise}/publicacion/personal', 'publicacionController@indexPersonal'); //ok
    Route::get('/{enterprise}/publicacion/{id}', 'publicacionController@show'); //ok;
    Route::post('/{enterprise}/publicacion', 'publicacionController@store'); //ok
    Route::post('/{enterprise}/publicacion/{id}', 'publicacionController@update'); //ok
    Route::post('/{enterprise}/publicacion/delete/{id}', 'publicacionController@destroy'); //ok
});
//End Publicacion

//Comunicado
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/comunicado', 'comunicadoController@index'); //ok 
    Route::get('/{enterprise}/comunicado/{id}', 'comunicadoController@show'); //ok;
    Route::get('/{enterprise}/comunicado/respuestas/excel', 'comunicadoController@descargaRespuestas'); //ok;
    Route::post('/{enterprise}/comunicado', 'comunicadoController@store'); //ok
    Route::post('/{enterprise}/comunicado/calificacion', 'comunicadoController@calificacion');
    Route::post('/{enterprise}/comunicado/{id}', 'comunicadoController@update'); //ok
    Route::post('/{enterprise}/comunicado/delete/{id}', 'comunicadoController@destroy'); //ok
});
//End Comunicado

//Etiqueta
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/etiqueta', 'etiquetaController@index'); //ok
    Route::get('/{enterprise}/etiqueta/{id}', 'etiquetaController@show'); //ok;
    Route::post('/{enterprise}/etiqueta', 'etiquetaController@store'); //ok
    Route::post('/{enterprise}/etiqueta/{id}', 'etiquetaController@update'); //ok
    Route::post('/{enterprise}/etiqueta/delete/{id}', 'etiquetaController@destroy'); //ok
});
//End Etiqueta

//GrupoDx
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/grupodx', 'grupodxController@index'); //ok
    Route::get('/{enterprise}/grupodx/{id}', 'grupodxController@show'); //ok;
    Route::post('/{enterprise}/grupodx', 'grupodxController@store'); //ok
    Route::post('/{enterprise}/grupodx/{id}', 'grupodxController@update'); //ok
    Route::post('/{enterprise}/grupodx/delete/{id}', 'grupodxController@destroy'); //ok
});
//End GrupoDx

//Galería
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/galeria', 'galeriaController@index'); //ok
    Route::get('/{enterprise}/galeria/{id}', 'galeriaController@show'); //ok;
    Route::post('/{enterprise}/galeria', 'galeriaController@store'); //ok
    Route::post('/{enterprise}/galeria/{id}', 'galeriaController@update'); //ok
    Route::post('/{enterprise}/galeria/delete/{id}', 'galeriaController@destroy'); //ok
});
//End Galería

//Autorizacionimagen
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/autorizacionimagen', 'autorizacionimagenController@index'); //ok
    Route::get('/{enterprise}/autorizacionimagen/{id}', 'autorizacionimagenController@show'); //ok;
    Route::post('/{enterprise}/autorizacionimagen', 'autorizacionimagenController@store'); //ok
    Route::post('/{enterprise}/autorizacionimagen/{id}', 'autorizacionimagenController@update'); //ok
    Route::post('/{enterprise}/autorizacionimagen/delete/{id}', 'autorizacionimagenController@destroy'); //ok
});
//End Autorizacionimagen

//Citamedicaarchivo
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/citamedicaarchivo', 'citamedicaarchivoController@index'); //ok
    Route::post('/{enterprise}/citamedicaarchivo', 'citamedicaarchivoController@store'); //ok
    Route::post('/{enterprise}/citamedicaarchivo/delete/{id}', 'citamedicaarchivoController@destroy'); //ok
});
//End Citamedicaarchivo

//Diagnostico
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/diagnostico', 'diagnosticoController@index'); //ok
    Route::post('/{enterprise}/diagnostico', 'diagnosticoController@store'); //ok
});
//End Diagnostico

//especialidad
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/especialidad', 'especialidadController@index'); //ok
});
//End especialidad

//examen
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/examen', 'examenController@index'); //ok
});
//End examen

//Modulo
Route::group(['middleware' => ['cors', 'ch.token']], function ($app) {
    Route::get('/{enterprise}/modulo', 'moduloController@index'); //ok
    Route::post('/{enterprise}/modulo', 'moduloController@store'); //ok
    Route::post('/{enterprise}/modulo/{id}', 'moduloController@update'); //ok
    Route::post('/{enterprise}/modulo/delete/{id}', 'moduloController@destroy'); //ok
});
//End Modulo

//Perfil
Route::group(['middleware' => ['cors', 'ch.token']], function ($app) {
    Route::get('/{enterprise}/perfil', 'perfilController@index'); //ok
    Route::post('/{enterprise}/perfil', 'perfilController@store'); //ok
    Route::get('/{enterprise}/perfil/{id}', 'perfilController@show'); //ok
    Route::post('/{enterprise}/perfil/{id}', 'perfilController@update'); //ok
    Route::post('/{enterprise}/perfil/perfilmodulo/{id}', 'perfilController@update_perfilmodulo'); //ok
    Route::post('/{enterprise}/perfil/delete/{id}', 'perfilController@destroy'); //ok
});
//End Perfil

//Sede
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/sede', 'sedeController@index'); //ok
    Route::get('/{enterprise}/sede/autorizadas', 'sedeController@autorizadas'); //ok
    Route::get('/{enterprise}/sede/{id}', 'sedeController@show'); //ok
    Route::post('/{enterprise}/sede', 'sedeController@store'); //ok
    Route::post('/{enterprise}/sede/{id}', 'sedeController@update'); //ok
    Route::post('/{enterprise}/sede/delete/{id}', 'sedeController@destroy'); //ok

});
//End Sede

//Ubigeo
Route::group(['middleware' => ['cors', 'ch.token']], function ($app) {
    Route::get('/{enterprise}/ubigeo', 'ubigeoController@index'); //ok
});
//End Ubigeo

//Horariomedico
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/horariomedico/construct', 'horariomedicoController@construct'); //ok
    Route::get('/{enterprise}/horariomedico', 'horariomedicoController@index'); //ok
    Route::get('/{enterprise}/horariomedico/new', 'horariomedicoController@newhorariomedico'); //ok
    Route::get('/{enterprise}/horariomedico/bloquehorarios', 'horariomedicoController@bloquehorarios'); //ok
    Route::get('/{enterprise}/horariomedico/medicos', 'horariomedicoController@medicos'); //ok
    Route::get('/{enterprise}/horariomedico/{id}', 'horariomedicoController@show'); //ok
    Route::post('/{enterprise}/horariomedico', 'horariomedicoController@store'); //ok
    Route::post('/{enterprise}/horariomedico/mensual', 'horariomedicoController@storemensual');
    Route::post('/{enterprise}/horariomedico/mensualmasivo/{id}', 'horariomedicoController@storemensualmasivo'); //ok
    Route::post('/{enterprise}/horariomedico/{id}', 'horariomedicoController@update'); //ok
    Route::post('/{enterprise}/horariomedico/delete/mensual', 'horariomedicoController@destroymensual'); //ok
    Route::post('/{enterprise}/horariomedico/delete/{id}', 'horariomedicoController@destroy'); //ok
});
//End Horariomedico

//Citamedica
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/citamedica/construct', 'citamedicaController@construct'); //ok
    Route::get('/{enterprise}/citamedica', 'citamedicaController@index'); //ok
    Route::get('/{enterprise}/citamedica/light', 'citamedicaController@indexLight'); //ok
    Route::get('/{enterprise}/citamedica/controldiario', 'citamedicaController@controldiario'); //ok
    Route::get('/{enterprise}/citamedica/dashboard', 'citamedicaController@dashboard'); //ok
    Route::get('/{enterprise}/citamedica/tratamientosmedicos', 'citamedicaController@tratamientosmedicos'); //ok
    Route::get('/{enterprise}/citamedica/dashboard/detail', 'citamedicaController@dashboarddetail'); //ok
    Route::get('/{enterprise}/citamedica/dashboard/medico/detail', 'citamedicaController@dashboardmedicodetail'); //ok
    Route::get('/{enterprise}/citamedica/new', 'citamedicaController@newcitamedica'); //ok
    Route::get('/{enterprise}/citamedica/{id}', 'citamedicaController@show'); //ok
    Route::get('/{enterprise}/citamedica/referencia/{id}', 'citamedicaController@referencia'); //ok
    Route::get('/{enterprise}/citamedica/consulta/{id}', 'citamedicaController@showconsulta'); //ok
    Route::get('/{enterprise}/citamedica/horario/disponibilidad', 'citamedicaController@disponibilidadHora'); //ok
    Route::get('/{enterprise}/citamedica/medico/disponibilidad', 'citamedicaController@disponibilidadMedico'); //ok
    Route::get('/{enterprise}/citamedica/log/{id}', 'citamedicaController@log'); //ok
    Route::post('/{enterprise}/citamedica', 'citamedicaController@store'); //ok
    Route::post('/{enterprise}/citamedica/pagar', 'citamedicaController@storePagarCm'); //ok
    Route::post('/{enterprise}/citamedica/pagartrat', 'citamedicaController@storePagarTrat'); //ok
    Route::post('/{enterprise}/citamedica/diagnostico', 'citamedicaController@updatediagnostico'); //ok
    Route::post('/{enterprise}/citamedica/{id}', 'citamedicaController@update'); //ok
    Route::post('/{enterprise}/citamedica/reagenda/masivo', 'citamedicaController@updatereagendamasivo'); //ok
    Route::post('/{enterprise}/citamedica/tratamiento/{id}', 'citamedicaController@updatetratamiento'); //ok
    Route::post('/{enterprise}/citamedica/delete/{id}', 'citamedicaController@destroy'); //ok

    Route::post('/{enterprise}/citamedica/reagenda/masivo', 'citamedicaController@updatereagendamasivo'); //ok
});
//End Citamedica

//Informe
Route::group(['middleware' => ['cors']], function ($app) {
    Route::post('/{enterprise}/informe/firmar/{id}', 'informeController@firmar'); //ok
    Route::post('/{enterprise}/informe/delete/{id}', 'informeController@destroy'); //ok
});
//End Informe

//Notificacion
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/notificacion', 'notificacionController@index');
});

//proceso
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/proceso/construct', 'procesoController@construct');
    Route::get('/{enterprise}/proceso', 'procesoController@index');
    Route::get('/{enterprise}/proceso/{id}', 'procesoController@show');
    Route::post('/{enterprise}/proceso/{id}', 'procesoController@update'); //ok
});
//End proceso

//Tarea
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/tarea/construct', 'tareaController@construct');
    Route::get('/{enterprise}/tarea', 'tareaController@index');
    Route::get('/{enterprise}/tarea/{id}', 'tareaController@show');
});
//End Tarea

//Tareadet
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/tareadet', 'tareadetController@index');
    Route::post('/{enterprise}/tareadet', 'tareadetController@store'); //ok
    Route::post('/{enterprise}/tareadet/{id}', 'tareadetController@update'); //ok
    Route::post('/{enterprise}/tareadet/delete/{id}', 'tareadetController@destroy'); //ok
});
//End Tareadet

//Citaterapeutica , 'ch.token'
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/citaterapeutica/construct', 'citaterapeuticaController@construct'); //ok
    Route::get('/{enterprise}/citaterapeutica/turnos', 'citaterapeuticaController@indexturnos');
    Route::get('/{enterprise}/citaterapeutica', 'citaterapeuticaController@index'); //ok
    Route::get('/{enterprise}/citaterapeutica/reservas', 'citaterapeuticaController@indexreservas'); //ok
    Route::get('/{enterprise}/citaterapeutica/agenda', 'citaterapeuticaController@agenda'); //ok
    Route::get('/{enterprise}/citaterapeutica/new', 'citaterapeuticaController@newcitaterapeutica'); //ok
    Route::get('/{enterprise}/citaterapeutica/aseguradoraspaciente', 'citaterapeuticaController@aseguradorasPaciente'); //ok
    Route::get('/{enterprise}/citaterapeutica/{id}', 'citaterapeuticaController@show'); //ok
    Route::get('/{enterprise}/citaterapeutica/horario/disponibilidad', 'citaterapeuticaController@disponibilidadHora'); //ok
    Route::get('/{enterprise}/citaterapeutica/horario/disponibilidad/postcovid', 'citaterapeuticaController@disponibilidadHoraPostCovid'); //ok
    Route::get('/{enterprise}/citaterapeutica/terapista/disponibilidad', 'citaterapeuticaController@disponibilidadTerapista'); //ok

    Route::get('/{enterprise}/citaterapeutica/terapista/disponibilidad/postcovid', 'citaterapeuticaController@disponibilidadTerapistaPostCovid'); //ok

    Route::get('/{enterprise}/citaterapeutica/log/{id}', 'citaterapeuticaController@log');

    Route::post('/{enterprise}/citaterapeutica', 'citaterapeuticaController@store'); //ok
    Route::post('/{enterprise}/citaterapeutica/masivo', 'citaterapeuticaController@storemasivo'); //ok
    Route::post('/{enterprise}/citaterapeutica/programacion', 'citaterapeuticaController@storeprogramacion');
    Route::post('/{enterprise}/citaterapeutica/calificacion/{id}', 'citaterapeuticaController@calificacion');
    Route::post('/{enterprise}/citaterapeutica/programacion/reasignacion', 'citaterapeuticaController@reasignacionprogramacion');

    Route::post('/{enterprise}/citaterapeutica/programacion/copiar', 'citaterapeuticaController@copiarprogramacion');

    Route::post('/{enterprise}/citaterapeutica/programacion/delete', 'citaterapeuticaController@deleteprogramacion');

    Route::post('/{enterprise}/citaterapeutica/reprogramacion', 'citaterapeuticaController@reprogramacion');

    Route::post('/{enterprise}/citaterapeutica/{id}', 'citaterapeuticaController@update'); //ok
    Route::post('/{enterprise}/citaterapeutica/delete/{id}', 'citaterapeuticaController@destroy'); //ok
    Route::post('/{enterprise}/citaterapeutica/deletereservacion/{id}', 'citaterapeuticaController@destroyreservacion'); //ok

});
//End Citaterapeutica

//Cicloatencion
Route::group(['middleware' => ['cors', 'ch.token']], function ($app) {
    Route::get('/{enterprise}/cicloatencion/construct', 'cicloatencionController@construct'); //ok
    Route::get('/{enterprise}/cicloatencion', 'cicloatencionController@index'); //ok
    Route::get('/{enterprise}/cicloatencion/cobrocitasmedicas', 'cicloatencionController@cobroCitasmedicas'); //ok
    Route::get('/{enterprise}/cicloatencion/new', 'cicloatencionController@newcicloatencion');
    Route::get('/{enterprise}/cicloatencion/{id}', 'cicloatencionController@show'); //ok
    Route::get('/{enterprise}/cicloatencion/{id}/generarfactura', 'cicloatencionController@generarfactura'); //OK PROBAR EL idcliente y cliente en respuesta
    Route::post('/{enterprise}/cicloatencion', 'cicloatencionController@store'); //ok
    Route::post('/{enterprise}/cicloatencion/anadir', 'cicloatencionController@storeanadir'); //ok NO TIENE SENTIDO
    Route::post('/{enterprise}/cicloatencion/estadocontabilidad', 'cicloatencionController@updateContabilidad'); //ok NO TIENE SENTIDO
    Route::post('/{enterprise}/cicloatencion/{id}', 'cicloatencionController@update'); //ok PROBAR muy complejo
    Route::post('/{enterprise}/cicloatencion/tratamiento/{id}', 'cicloatencionController@updatetratamientoextra'); //ok

    Route::post('/{enterprise}/cicloatencion/tratamiento/delete/{id}', 'cicloatencionController@deletetratamientoextra'); //ok

    Route::post('/{enterprise}/cicloatencion/openclose/{id}', 'cicloatencionController@updateAbrirCerrar'); //ok
    Route::post('/{enterprise}/cicloatencion/mover/cm/{id}', 'cicloatencionController@updateMovercm'); //ok PROBAR muy complejo
    Route::post('/{enterprise}/cicloatencion/mover/ciclo/{id}', 'cicloatencionController@updateMoverciclo'); //ok PROBAR muy complejo
    Route::post('/{enterprise}/cicloatencion/delete/{id}', 'cicloatencionController@destroy'); //ok
    
    Route::post('/{enterprise}/cicloatencion/regenerarhayau/{id}', 'cicloatencionController@regenerarHAyAU'); //ok
    
    Route::get('/{enterprise}/cicloatencion/{id}/previoenvio', 'cicloatencionController@showPrevioEnvio');
    Route::get('/{enterprise}/cicloatencion/{id}/previsualizacionha/{idgrupodx}', 'cicloatencionController@previsualizacionHA'); 
    Route::get('/{enterprise}/cicloatencion/{idcicloautorizacion}/previsualizacionsited', 'cicloatencionController@previsualizacionSITED'); 
    Route::post('/{enterprise}/cicloatencion/enviaracontabilidad/{id}', 'cicloatencionController@enviarAContabilidad'); //ok
    Route::post('/{enterprise}/cicloatencion/anularenvioacontabilidad/{id}', 'cicloatencionController@anularEnvioAContabilidad'); //ok
});
//End Cicloatencion

//Post
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/post/construct', 'postController@construct'); //ok
    Route::get('/{enterprise}/post', 'postController@index'); //ok
    Route::get('/{enterprise}/post/new', 'postController@newpost'); //ok
    Route::post('/{enterprise}/post', 'postController@store'); //ok
    Route::post('/{enterprise}/post/{id}', 'postController@update'); //ok
    Route::post('/{enterprise}/post/delete/{id}', 'postController@destroy'); //ok
});
//End Post

//Ciclomovimiento
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/ciclomovimiento', 'ciclomovimientoController@index'); //ok
    Route::get('/{enterprise}/ciclomovimiento/{id}', 'ciclomovimientoController@show'); //ok
    Route::post('/{enterprise}/ciclomovimiento', 'ciclomovimientoController@store'); //ok
    Route::post('/{enterprise}/ciclomovimiento/delete/{id}', 'ciclomovimientoController@destroy'); //ok
});
//End Ciclomantenimiento

//Caja
Route::group(['middleware' => ['cors', 'ch.token']], function ($app) {
    Route::get('/{enterprise}/caja/construct', 'cajaController@construct'); //OK
    Route::get('/{enterprise}/caja', 'cajaController@index'); //ok
    Route::get('/{enterprise}/caja/aperturas', 'cajaController@indexaperturas'); //ok
    Route::get('/{enterprise}/caja/new', 'cajaController@newcaja'); //ok
    Route::get('/{enterprise}/caja/porabrir', 'cajaController@porAbrir'); //ok
    Route::get('/{enterprise}/caja/porcerrar', 'cajaController@porCerrar'); //OK
    Route::get('/{enterprise}/caja/{id}', 'cajaController@show'); //ok
    Route::post('/{enterprise}/caja/apertura/abrir/{id}', 'cajaController@abrirapertura'); //OK
    Route::post('/{enterprise}/caja/{id}', 'cajaController@update'); //ok
    Route::post('/{enterprise}/caja/apertura/cerrar/{id}', 'cajaController@cerrarapertura'); //OK
    Route::post('/{enterprise}/caja/apertura/{id}', 'cajaController@updateapertura'); //OK
});
//End Caja

//Venta
Route::group(['middleware' => ['cors', 'ch.token']], function ($app) {
    Route::get('/{enterprise}/venta/construct', 'ventaController@construct'); //ok
    Route::get('/{enterprise}/venta', 'ventaController@index'); //ok
    Route::get('/{enterprise}/ventadet', 'ventadetController@index'); //ok
    Route::get('/{enterprise}/venta/new', 'ventaController@newventa'); //ok

    Route::get('/{enterprise}/venta/cpeemision/{id}', 'ventaController@cpeemision'); //
    Route::get('/{enterprise}/venta/cpeanulacion/{id}', 'ventaController@cpeanulacion'); //ok

    Route::get('/{enterprise}/venta/cajasydocumentos', 'ventaController@cajasydocumentos'); //ok
    Route::get('/{enterprise}/venta/documentoserie', 'ventaController@showdocumentoserie'); //ok
    Route::get('/{enterprise}/venta/{id}', 'ventaController@show'); //ok
    Route::post('/{enterprise}/venta', 'ventaController@store'); //ok
    Route::post('/{enterprise}/venta/factura', 'ventaController@storefactura'); //ok
    Route::post('/{enterprise}/venta/anular/{id}', 'ventaController@anular'); //ok
    Route::post('/{enterprise}/venta/delete/{id}', 'ventaController@destroy'); //ok
    Route::post('/{enterprise}/venta/cpexml', 'ventaController@cpexml'); //ok
    Route::get('/{enterprise}/testing/cpexml', 'ventaController@cpexml'); //ok
    Route::get('/{enterprise}/venta/grafica/mes', 'ventaController@graficames'); //ok
    Route::get('/{enterprise}/venta/grafica/ano', 'ventaController@graficaano'); //ok
    Route::get('/{enterprise}/venta/grafica/afiliado', 'ventaController@graficaafiliado'); //ok
    Route::post('/{enterprise}/venta/cambiarnumero/{id}', 'ventaController@updatenumerodocumento'); //ok
    Route::post('/{enterprise}/venta/cambiarnumerodocventa/{id}', 'ventaController@updatenumerodocventa'); //ok
    Route::post('/{enterprise}/venta/{id}', 'ventaController@update'); //ok
    Route::post('/{enterprise}/venta/talonario/{id}', 'ventaController@updateTalonario');
    Route::post('/{enterprise}/venta/cpe/{id}', 'ventaController@updateCpe'); //ok
    Route::post('/{enterprise}/venta/correoenvio/{id}', 'ventaController@correoenvio'); //ok
});
//End Venta

//ordencompra
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/ordencompra', 'ordencompraController@index'); //ok
    Route::get('/{enterprise}/ordencompra/construct', 'ordencompraController@construct'); //ok
    Route::get('/{enterprise}/ordencompra/{id}', 'ordencompraController@show'); //ok
    Route::post('/{enterprise}/ordencompra', 'ordencompraController@store'); //ok
});
//End ordencompra

//Cupondescuento
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/cupondescuento', 'cupondescuentoController@index'); //ok
    Route::get('/{enterprise}/cupondescuento/new', 'cupondescuentoController@newcupon'); //ok
    Route::get('/{enterprise}/cupondescuento/{id}', 'cupondescuentoController@show'); //ok
    Route::post('/{enterprise}/cupondescuento', 'cupondescuentoController@store'); //ok
    Route::post('/{enterprise}/cupondescuento/{id}', 'cupondescuentoController@update'); //ok
    Route::post('/{enterprise}/cupondescuento/delete/{id}', 'cupondescuentoController@destroy'); //ok
});
//End Cupondescuento


//Dxtratamiento
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/dxtratamiento/construct', 'dxtratamientoController@construct'); //ok
    Route::get('/{enterprise}/dxtratamiento/sugerencias', 'dxtratamientoController@sugerencias'); //ok
    Route::get('/{enterprise}/dxtratamiento', 'dxtratamientoController@index'); //ok
    Route::post('/{enterprise}/dxtratamiento/{id}', 'dxtratamientoController@update'); //ok
    Route::post('/{enterprise}/dxtratamiento/delete/{id}', 'dxtratamientoController@destroy'); //ok
});
//End Dxtratamiento

//Drawing
Route::group(['middleware' => ['cors' /* , 'ch.token' */]], function ($app) {
    Route::get('/{enterprise}/drawing/dashboard', 'drawingController@dashboard'); // Observado, tiene en duro nombre de DISTRITOS
});
//End drawing

//Llamada
Route::group(['middleware' => ['cors' /* , 'ch.token' */]], function ($app) {
    Route::get('/{enterprise}/llamada/dashboard', 'llamadaController@dashboard'); //ok
    Route::get('/{enterprise}/llamada/construct', 'llamadaController@construct'); //ok
    Route::get('/{enterprise}/llamada', 'llamadaController@index'); //ok
    Route::get('/{enterprise}/llamada/reporte1', 'llamadaController@reporte1'); //ok
    Route::get('/{enterprise}/llamada/reporte2', 'llamadaController@reporte2'); //ok
    Route::get('/{enterprise}/llamada/anexos', 'llamadaController@anexos'); //ok
    Route::get('/{enterprise}/llamada/{id}', 'llamadaController@show'); //ok
    Route::post('/{enterprise}/llamada', 'llamadaController@store'); //ok
    Route::post('/{enterprise}/llamada/anexos', 'llamadaController@storeanexos'); //ok
    Route::post('/{enterprise}/llamada/enviarsms', 'llamadaController@enviarsms'); //ok
});
//End Llamada

//Presupuesto
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/presupuesto/log/{id}', 'presupuestoController@log'); //ok
    Route::get('/{enterprise}/presupuesto/log/view/{id}', 'presupuestoController@logshow'); //ok
    Route::get('/{enterprise}/presupuesto/{id}', 'presupuestoController@show'); //ok
});
//End Presupuesto

//Terapia
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/terapia/construct', 'terapiaController@construct'); //ok
    Route::get('/{enterprise}/terapia/dashboard', 'terapiaController@dashboard');
    Route::get('/{enterprise}/terapia', 'terapiaController@index'); //ok
    Route::get('/{enterprise}/terapia/index2', 'terapiaController@index2');
    Route::get('/{enterprise}/terapia/log/{id}', 'terapiaController@log'); //ok
    Route::get('/{enterprise}/terapia/log/view/{id}', 'terapiaController@logshow'); //ok
    Route::post('/{enterprise}/terapia/firma/{id}', 'terapiaController@storeFirma');
    Route::post('/{enterprise}/terapia/firma/delete/{id}', 'terapiaController@deleteFirma');
    Route::post('/{enterprise}/terapia', 'terapiaController@store'); //ok
    Route::post('/{enterprise}/terapia/ingreso', 'terapiaController@ingreso'); //ok
    Route::post('/{enterprise}/terapia/{id}', 'terapiaController@update'); //ok
    Route::get('/{enterprise}/terapia/atender', 'terapiaController@newatencion'); //ok
    Route::get('/{enterprise}/terapia/atender/{id}', 'terapiaController@showatencion'); //ok
    Route::get('/{enterprise}/terapia/historial', 'terapiaController@historial'); //ok
    Route::get('/{enterprise}/terapia/tratamientos', 'terapiaController@tratamientos'); //ok
    Route::post('/{enterprise}/terapia/atender/{id}', 'terapiaController@storeatencion'); //ok
    Route::post('/{enterprise}/terapia/atender/update/{id}', 'terapiaController@updateatencion'); //ok
    Route::post('/{enterprise}/terapia/sms/update/{id}', 'terapiaController@updatesms'); //ok
    Route::get('/{enterprise}/terapia/{id}', 'terapiaController@show'); //ok
    Route::get('/{enterprise}/terapia/copiarfirmas/{idcicloatencion}/grupodx/{idgrupodx}', 'terapiaController@copiarFirmaxGrupo'); //ok

});
//End Terapia

//Movimiento
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/movimiento/construct', 'movimientoController@construct'); //ok
    Route::get('/{enterprise}/movimiento', 'movimientoController@index'); //ok
    Route::get('/{enterprise}/movimiento/new', 'movimientoController@newmovimiento'); //ok
    Route::post('/{enterprise}/movimiento', 'movimientoController@store'); //ok
    Route::post('/{enterprise}/movimiento/{id}', 'movimientoController@update'); //ok
    Route::post('/{enterprise}/movimiento/delete/{id}', 'movimientoController@destroy'); //ok
    Route::get('/{enterprise}/movimiento/{id}', 'movimientoController@show'); //ok
});
//End movimiento

//Paquete
Route::group(['middleware' => ['cors']], function ($app) {
    //Route::get('/{enterprise}/paquete/construct', 'movimientoController@construct'); //ok
    Route::get('/{enterprise}/paquete', 'paqueteController@index'); //ok
    //Route::get('/{enterprise}/paquete/new', 'movimientoController@newmovimiento'); //ok
    Route::post('/{enterprise}/paquete', 'paqueteController@store'); //ok
    Route::post('/{enterprise}/paquete/{id}', 'paqueteController@update'); //ok
    Route::post('/{enterprise}/paquete/delete/{id}', 'paqueteController@destroy'); //ok
    Route::get('/{enterprise}/paquete/{id}', 'paqueteController@show'); //ok
});
//End paquete

//Asistencia
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/asistencia/construct', 'asistenciaController@construct'); //ok
    Route::get('/{enterprise}/asistencia', 'asistenciaController@index'); //ok
    Route::get('/{enterprise}/asistencia/new', 'asistenciaController@newasistencia'); //ok
    Route::get('/{enterprise}/asistencia/consolidado', 'asistenciaController@consolidado'); //ok
    Route::post('/{enterprise}/asistencia', 'asistenciaController@store'); //ok
    Route::post('/{enterprise}/asistencia/marcacionnueva', 'asistenciaController@storemarcacionnueva'); //ok
    Route::post('/{enterprise}/asistencia/marcacion', 'asistenciaController@storemarcacionnueva'); //ok storemarcacion
    Route::post('/{enterprise}/asistencia/masivo', 'asistenciaController@storemasivo'); //ok
    Route::get('/{enterprise}/asistencia/{id}', 'asistenciaController@show'); //ok
    Route::post('/{enterprise}/asistencia/{id}', 'asistenciaController@update'); //ok
    Route::post('/{enterprise}/asistencia/horario/copiar', 'asistenciaController@copiarhorario');
    Route::post('/{enterprise}/asistencia/horario/{id}', 'asistenciaController@updatehorario'); //ok
    Route::post('/{enterprise}/asistencia/delete/masivo', 'asistenciaController@destroymasivo'); //
    Route::post('/{enterprise}/asistencia/delete/{id}', 'asistenciaController@destroy'); //ok
});
//End Asistencia

//Autorizacionterapia
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/autorizacionterapia/construct', 'autorizacionterapiaController@construct'); //ok
    Route::get('/{enterprise}/autorizacionterapia', 'autorizacionterapiaController@index'); //ok
    Route::post('/{enterprise}/autorizacionterapia', 'autorizacionterapiaController@store'); //ok
    Route::post('/{enterprise}/autorizacionterapia/delete/{id}', 'autorizacionterapiaController@destroy'); //ok
});
//End Autorizacionterapia

//Pdf
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/pdf/template', 'Pdfs\templateController@holamundo');
    Route::get('/{enterprise}/pdf/comunicado/{id}/{idpersonal}', 'Pdfs\comunicadoController@reporte');
    Route::get('/{enterprise}/pdf/venta/{id}', 'Pdfs\ventaController@reporte');
    Route::get('/{enterprise}/pdf/venta/factura/{id}', 'Pdfs\facturaController@reporte');
    Route::get('/{enterprise}/pdf/invoice/{id}', 'Pdfs\invoiceController@reporte');
    Route::get('/{enterprise}/pdf/comprobantes/masivo', 'Pdfs\invoicemasivoController@reporte');
    Route::get('/{enterprise}/pdf/venta/facturahtml/{id}', 'Pdfs\facturaController@reportehtml');

    Route::get('/{enterprise}/pdf/presupuesto/{id}', 'Pdfs\presupuestoController@reporte');
    Route::get('/{enterprise}/pdf/presupuesto/medico/{id}', 'Pdfs\presupuestomedicoController@reporte');
    Route::get('/{enterprise}/pdf/presupuesto/movimiento/{id}', 'Pdfs\presupuestomovimientoController@reporte');

    Route::get('/{enterprise}/pdf/presupuesto/exoneracion/{id}', 'Pdfs\presupuestoexoneracionController@reporte');

    Route::get('/{enterprise}/pdf/ordencompra/{id}', 'Pdfs\ordencompraController@reporte');
    Route::get('/{enterprise}/pdf/citasmedicas', 'pdfController@citasmedicas');
    Route::get('/{enterprise}/pdf/ventascaja', 'pdfController@ventascaja');
    Route::get('/{enterprise}/pdf/terapiadiaria', 'pdfController@terapiadiaria');
    Route::get('/{enterprise}/pdf/terapiadiariav2', 'pdfController@terapiadiariaV2');
    Route::get('/{enterprise}/pdf/terapias', 'Pdfs\terapiasController@reporte');
    Route::get('/{enterprise}/pdf/terapias-standar', 'Pdfs\terapiasStandarController@reporte');
    Route::get('/{enterprise}/pdf/terapias/auditoria', 'Pdfs\terapiasauditoriaController@reporte');
    Route::get('/{enterprise}/pdf/caja/cierre/{id}', 'Pdfs\cajacierreController@reporte');
    Route::get('/{enterprise}/pdf/caja/consultamedicas/{id}', 'Pdfs\consultasController@reporte');
    Route::get('/{enterprise}/pdf/caja/consultamedicas/auditoria/{id}', 'Pdfs\consultasauditoriaController@reporte');
    Route::get('/{enterprise}/pdf/movimiento', 'Pdfs\movimientoController@reporte');
    Route::get('/{enterprise}/pdf/ciclo/deudas', 'Pdfs\ciclodeudaController@reporte');
    Route::get('/{enterprise}/pdf/consultamedicas/reporte/uno', 'Pdfs\consultasunoController@reporte');
    Route::get('/{enterprise}/pdf/consultamedicas/reporte/dos', 'Pdfs\consultasdosController@reporte');
    Route::get('/{enterprise}/pdf/consultamedicas/reporte/tres', 'Pdfs\consultastresController@reporte');
    Route::get('/{enterprise}/pdf/consultamedicas/reporte/cuatro', 'Pdfs\consultascuatroController@reporte');
    Route::get('/{enterprise}/pdf/consultamedicas/reporte/cinco', 'Pdfs\consultascincoController@reporte');
    Route::get('/{enterprise}/pdf/consultamedicas/reporte/seis', 'Pdfs\consultasseisController@reporte');
    Route::get('/{enterprise}/pdf/consultamedicas/reporte/siete', 'Pdfs\consultassieteController@reporte');
    Route::get('/{enterprise}/pdf/consultamedicas/reporte/ocho', 'Pdfs\consultasochoController@reporte');
    Route::get('/{enterprise}/pdf/terapias/reporte/uno', 'Pdfs\terapiasunoController@reporte');
    Route::get('/{enterprise}/pdf/vistapreviafactura', 'Pdfs\vistapreviafacturaController@reporte');
    Route::get('/{enterprise}/pdf/citaterapeuticaporasistir/{id}', 'Pdfs\citaterapeuticaporasistirController@reporte');
    Route::get('/{enterprise}/pdf/citaterapeuticaasistencia/{id}', 'Pdfs\citaterapeuticaasistenciaController@reporte');

    Route::get('/{enterprise}/pdf/informemedico/{id}', 'Pdfs\informemedicoelectronicoController@reporte');
    Route::get('/{enterprise}/pdf/informemedicoelectronico/{id}', 'Pdfs\informemedicoController@reporte');
    Route::get('/{enterprise}/pdf/informemedico/procedimiento/{id}', 'Pdfs\informeprocedimientoelectronicoController@reporte');
    Route::get('/{enterprise}/pdf/firmasdeatencion/{id}', 'Pdfs\firmasdeatencionController@reporte');
    Route::get('/{enterprise}/pdf/imagenautorizacion/{id}', 'Pdfs\imagenautorizacionController@reporte');
});
//End Pdf

//Modelo
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/modelo', 'modeloController@index'); //ok
    Route::get('/{enterprise}/modelo/new', 'modeloController@newmodelo'); //ok
    Route::post('/{enterprise}/modelo', 'modeloController@store'); //ok
    Route::post('/{enterprise}/modelo/{id}', 'modeloController@update'); //ok
    Route::post('/{enterprise}/modelo/delete/{id}', 'modeloController@destroy'); //ok
    Route::get('/{enterprise}/modelo/{id}', 'modeloController@show'); //ok
});
//End Modelo

//Contrato
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/contratolaboral', 'contratolaboralController@index'); //ok
    Route::get('/{enterprise}/contratolaboral/new', 'contratolaboralController@newcontrato'); //ok
    Route::post('/{enterprise}/contratolaboral', 'contratolaboralController@store'); //ok
    Route::post('/{enterprise}/contratolaboral/{id}', 'contratolaboralController@update'); //ok
    Route::post('/{enterprise}/contratolaboral/delete/{id}', 'contratolaboralController@destroy'); //ok
    Route::get('/{enterprise}/contratolaboral/{id}', 'contratolaboralController@show'); //ok
});
//End Contrato

//Plan horario
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/planhorario', 'planhorarioController@index'); //ok
    Route::post('/{enterprise}/planhorario', 'planhorarioController@store'); //ok
    Route::post('/{enterprise}/planhorario/{id}', 'planhorarioController@update'); //ok
    Route::post('/{enterprise}/planhorario/delete/{id}', 'planhorarioController@destroy'); //ok
    Route::get('/{enterprise}/planhorario/{id}', 'planhorarioController@show'); //ok
});
//Plan horario

//Cicloautorizacion
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/cicloautorizacion', 'cicloautorizacionController@index'); //ok
    Route::post('/{enterprise}/cicloautorizacion/estadoimpresion', 'cicloautorizacionController@updateImpresion'); //ok NO
});
//End Ciclomantenimiento

//Presupuestodetcant
Route::group(['middleware' => ['cors' /* , 'ch.token' */]], function ($app) {
    Route::get('/{enterprise}/presupuestodetcant', 'presupuestodetcantController@index'); //ok
});
//End Presupuestodetcant

//Calls
Route::group(['middleware' => ['cors']], function ($app) {
    Route::get('/{enterprise}/calls', 'callsController@index'); //ok
    Route::get('/{enterprise}/calls/{id}', 'callsController@show'); //ok;
    Route::post('/{enterprise}/calls', 'callsController@store'); //ok
    Route::post('/{enterprise}/calls/{id}', 'callsController@update'); //ok
    Route::post('/{enterprise}/calls/delete/{id}', 'callsController@destroy'); //ok
});
//End Etiqueta 

