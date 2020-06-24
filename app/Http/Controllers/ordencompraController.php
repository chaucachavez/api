<?php

namespace App\Http\Controllers;

use App\Models\cicloatencion;
use App\Models\citamedica;
use App\Models\entidad;
use App\Models\presupuesto;
use App\Models\sede;
use Illuminate\Http\Request;
use App\Models\empresa;
use App\Models\ordencompra;
use \Firebase\JWT\JWT;
use DB;

class ordencompraController extends sendEmail {

    public function __construct(Request $request) {
        $this->getToken($request);
    }

    public function construct(Request $request, $enterprise) {

        $sede = new sede();

        $empresa = empresa::where('url', '=', $enterprise)->first();
        $idempresa = $empresa->idempresa;

        $param = array(
            'sede.idempresa' => $idempresa,
            'entidadsede.identidad' => $this->objTtoken->my
        );

        $data = array(
            'sedes' => $sede->autorizadas($param, $this->objTtoken->mysede)
        );

        return $this->crearRespuesta($data, 200);
    }

    public function index(Request $request, $enterprise) {

        $request = $request->all();

        $empresa = new empresa();
        $ordencompra = new ordencompra();

        $idempresa = $empresa->idempresa($enterprise);

        $param = array();
        $param['ordencompra.idempresa'] = $empresa->idempresa($enterprise);

        if (isset($request['idsede']) && !empty($request['idsede'])) {
            $param['ordencompra.idsede'] = $request['idsede'];
        }

        $pageSize = '';
        $orderName = '';
        $orderSort = '';
        if (isset($request['pageSize']) && !empty($request['pageSize'])) {
            $orderName = !empty($request['orderName']) ? $request['orderName'] : 'ordencompra.idordencompra';
            $orderSort = !empty($request['orderSort']) ? $request['orderSort'] : 'desc';
            $pageSize = !empty($request['pageSize']) ? $request['pageSize'] : 25;
        }

        if (isset($request['desde']) && isset($request['hasta'])) {
            $request['desde'] = $this->formatFecha($request['desde'], 'yyyy-mm-dd');
            $request['hasta'] = $this->formatFecha($request['hasta'], 'yyyy-mm-dd');
            $between = [$request['desde'], $request['hasta']];
        }

        $like = !empty($request['likeentidad']) ? trim($request['likeentidad']) : '';

        $data = $ordencompra->grid($param, $between, $like, $pageSize, $orderName, $orderSort);

        $total = '';
        if (isset($request['pageSize']) && !empty($request['pageSize'])) {
            $total = $data->total();
            $data = $data->items();
        }

        if (isset($request['formato']) && !empty($request['formato'])) {
            if(in_array($request['formato'], ['xls', 'xlsx'])){

            }
        } else {
            return $this->crearRespuesta($data, 200, $total);
        }
    }

    public function show(Request $request, $enterprise, $id) {

        $empresa = new empresa();
        $Objordencompra = new ordencompra();

        $idempresa = $empresa->idempresa($enterprise);
        $ordencompra = $Objordencompra->ordencompra($id);

        if ($ordencompra) {
            $listcombox = array(
                'ordencompradet' => $Objordencompra->ordencompradet($id)
            );
 
            return $this->crearRespuesta($ordencompra, 200, '', '', $listcombox);
        }

        return $this->crearRespuestaError('Orden compra no encotrado', 404);
    }

    public function store(Request $request, $enterprise) {

        $empresa = new empresa();
        $objCicloatencion = new cicloatencion();
        $objCitamedica = new citamedica();
        $objEntidad = new entidad();
        $sendEmail = new sendEmail();
 
        $tmpempresa = $empresa->empresa(['url' => $enterprise]);
        $idempresa = $tmpempresa->idempresa;

        $request = $request->all();

        $entidad = $objEntidad->entidad(['entidad.identidad' => $request['ordencompra']['idcliente']]);

        $documentoserie = DB::table('documentoserie')
            ->where(array('identidad' => $tmpempresa->codeordencompra, 'iddocumentofiscal' => 14, 'activo' => '1'))  
            ->whereNull('documentoserie.deleted')
            ->first();

        $request['ordencompra']['idempresa'] = $idempresa;
        $request['ordencompra']['idafiliado'] = $documentoserie->identidad;
        $request['ordencompra']['iddocumentofiscal'] = $documentoserie->iddocumentofiscal;
        $request['ordencompra']['serie'] = $documentoserie->serie;
        $request['ordencompra']['serienumero'] = ($documentoserie->numero + 1);
        $request['ordencompra']['fechaventa'] = date('Y-m-d');
        $request['ordencompra']['horaventa'] = date('H:i:s');
        $request['ordencompra']['created_at'] = date('Y-m-d H:i:s');
        $request['ordencompra']['id_created_at'] = $this->objTtoken->my;


        if (isset($request['ordencompra']['idcicloatencion'])) {
            //Solo para presupuesto
            $presupuesto = presupuesto::where('idcicloatencion', '=', $request['ordencompra']['idcicloatencion'])->first();

            $presupuestodet = $presupuesto->presupuestodet($presupuesto->idpresupuesto);

            $presupuestodetUpdate = [];
            $ventatotal = 0;
            foreach ($request['ordencompradet'] as $row) {
                foreach ($presupuestodet as $rowpres) {
                    if ($rowpres->idproducto === $row['idproducto']) {
                        $presupuestodetUpdate[] = array(
                            'data' => ['cantpagada' => $rowpres->cantpagada + $row['cantidad']],
                            'where' => ['idpresupuestodet' => $rowpres->idpresupuestodet]
                        );

                        $ventatotal +=  $row['total'];
                        break;
                    }

                    switch ($idempresa) {
                        case 2: 
                            $idproducto = 58;
                            break; 
                        default:
                            $idproducto = 22;
                            break;
                    } 
                    //TALVEZ SEA UN ACUENTA
                    if ($row['idproducto'] === $idproducto) {
                        $ventatotal +=  $row['total'];
                        break;
                    }
                }
            }

            $regular = 0;
            $tarjeta = 0;
            $efectivo = 0;
            foreach ($presupuestodet as $rowpres) {
                $regular = $regular + $rowpres->totalregular;
                $tarjeta = $tarjeta + $rowpres->totaltarjeta;
                $efectivo = $efectivo + $rowpres->totalefectivo;
            }

            $montopago = $presupuesto->montopago + $ventatotal;
            /* Campos auditores */
            $paramPresupuesto['updated_at'] = date('Y-m-d H:i:s');
            $paramPresupuesto['id_updated_at'] = $this->objTtoken->my;
            /* Campos auditores */
            $paramPresupuesto['regular'] = $regular;
            $paramPresupuesto['tarjeta'] = $tarjeta;
            $paramPresupuesto['efectivo'] = $efectivo;
            $paramPresupuesto['montopago'] = $montopago;
            $paramPresupuesto['montocredito'] = $montopago;
            $total = ($presupuesto->tipotarifa === 1 ? $regular : ($presupuesto->tipotarifa === 2 ? $tarjeta : $efectivo));
            $paramPresupuesto['idestadopago'] = $montopago >= $total && $total > 0 ? 68 : ($montopago > 0 && $montopago < $total ? 67 : 66);
            $paramPresupuesto['total'] = $total;
            $presupuesto->fill($paramPresupuesto);
            //$presupuesto->save();
        }

        DB::beginTransaction();
        try {
            //Creacion de orden de compra
            $ordencompra = ordencompra::create($request['ordencompra']);

            //Creacion de detalle de orden de compra
            $dataOrdencompradet = [];
            foreach ($request['ordencompradet'] as $row) {
                $dataOrdencompradet[] = array(
                    'idordencompra' => $ordencompra->idordencompra,
                    'idproducto' => $row['idproducto'],
                    'cantidad' => $row['cantidad'],
                    'idunidadmedida' => $row['idunidadmedida'],
                    'preciounit' => $row['preciounit'],
                    'codigocupon' => $row['codigocupon'],
                    'descuento' => $row['descuento'],
                    'total' => $row['total'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'id_created_at' => $this->objTtoken->my
                );
            }

            DB::table('ordencompradet')->insert($dataOrdencompradet);

            //Actualizacion de cita medica
            if(isset($request['ordencompra']['idcitamedica'])) {
                \DB::table('citamedica')
                    ->where('idcitamedica', $request['ordencompra']['idcitamedica'])
                    ->update(['idordencompra' => $ordencompra->idordencompra, 'idestadopago' => 71]);
            }

            //Actualizacion de DocumentoSerie
            $paramDocSerie = array();
            $paramDocSerie['numero'] = $documentoserie->numero + 1;
            $paramDocSerie['updated_at'] = date('Y-m-d H:i:s');
            $paramDocSerie['id_updated_at'] = $this->objTtoken->my;
            \DB::table('documentoserie')->where('iddocumentoserie', $documentoserie->iddocumentoserie)->update($paramDocSerie);

            //Actualizacion de PresupuestoEconomico
            if (isset($request['ordencompra']['idcicloatencion'])) {

                $presupuesto->save();

                foreach ($presupuestodetUpdate as $fila) {
                    \DB::table('presupuestodet')->where($fila['where'])->update($fila['data']);
                }

                //LogPresupuesto
                $presupuesto->grabarLog($presupuesto->idpresupuesto, $this->objTtoken->my);

                /* Setear 'todo A', 'Acuenta B', 'Todo B', 'Acuenta C', 'Todo C' en tabla CITAMEDICA;
                 * Se considera primera cita, por orden de fecha y hora de inicio de cita.
                 */
                $montopago = $presupuesto->montopago;
                $citasmedicas = $objCicloatencion->cicloCitasmedicas(['cicloatencion.idcicloatencion' => $request['ordencompra']['idcicloatencion']]);
                $citasmedicas = $this->ordenarMultidimension($citasmedicas, 'fecha', SORT_ASC, 'inicio', SORT_ASC);

                foreach ($citasmedicas as $row) {
                    $CMCosto = 0;

                    $tratamientosmedicos = $objCitamedica->tratamientomedico(['citamedica.idcitamedica' => $row->idcitamedica]);
                    foreach ($tratamientosmedicos as $tratamiento) {
                        $costo = 0;
                        foreach ($presupuestodet as $rowpres) {
                            if ($tratamiento->idproducto === $rowpres->idproducto) {
                                $cantidad = $tratamiento->cantidad;
                                if (!empty($tratamiento->parentcantidad)) { 
                                    $cantidad = $tratamiento->cantidad * $tratamiento->parentcantidad;
                                }
                                $preciounit = $presupuesto->tipotarifa === 1 ? $rowpres->preciounitregular : ($presupuesto->tipotarifa === 2 ? $rowpres->preciounittarjeta : $rowpres->preciounitefectivo);
                                $costo = $preciounit * $cantidad;
                                break;
                            }
                        }
                        $CMCosto += $costo;
                    }

                    if ($montopago > 0) {
                        $fechaventa = date('d/m/Y');

                        if ($row->fecha === $fechaventa) {
                            $dinero = 0;
                            if ($CMCosto <= $montopago) {
                                if ($row->presupuesto === 'Acuenta B') {
                                    $row->presupuesto = 'Todo B';
                                }

                                if (empty($row->presupuesto)) {
                                    $row->presupuesto = 'Todo A';
                                }
                                $dinero = $CMCosto;
                            }

                            if ($CMCosto > $montopago) {
                                $row->presupuesto = 'Acuenta B';
                                $dinero = $montopago;
                            }
                            $montopago = $montopago - $dinero;
                        }

                        if ($row->fecha !== $fechaventa) {
                            $dinero = 0;
                            if ($CMCosto <= $montopago) {
                                if ($row->presupuesto === 'Acuenta C' || empty($row->presupuesto)) {
                                    $row->presupuesto = 'Todo C';
                                }
                                if ($row->presupuesto === 'Acuenta B') {
                                    $row->presupuesto = 'Todo B';
                                }
                                $dinero = $CMCosto;
                            }

                            if ($CMCosto > $montopago) {
                                if ($row->presupuesto === 'Acuenta C' || empty($row->presupuesto)) {
                                    $row->presupuesto = 'Acuenta C';
                                }
                                if ($row->presupuesto === 'Acuenta B') {
                                    $row->presupuesto = 'Acuenta B';
                                }
                                $dinero = $montopago;
                            }

                            $montopago = $montopago - $dinero;
                        }

                        \DB::table('citamedica')->where(['idcitamedica' => $row->idcitamedica])->update(['presupuesto' => $row->presupuesto]);
                    }
                }
            }

            if($entidad->email){

                $nroOrden = ' N° ' . $ordencompra->serie . '-' . str_pad($ordencompra->serienumero, 6, "0", STR_PAD_LEFT);
                $subject = 'Orden de compra ' . $nroOrden . ' - Centro Médico OSI';

                $html = '<img src="https://sistemas.centromedicoosi.com/img/osi/email/emailhead.png" width="100%">
                        <div style="padding: 0px 30px 0px 30px; color: #333; font-family: Arial; line-height: 20px;">
                            <h5><strong>HOLA! '.mb_strtoupper($entidad->nombre).',</strong></h5>
                            <p>Recibimos la orden de compra que realizaste en nuestro portal web del paciente.</p>
                            <div style="background-color: #f0f0f0; padding: 10px 10px; font-weight: bold;">RESUMEN DE TU ORDEN</div>
                            <div style="line-height: 25px; padding-left: 10px;">
                                <strong>Nombre:</strong> '. $entidad->entidad .'<br>
                                <strong>Orden:</strong> '. $nroOrden .'<br>
                                <strong>Total s/.:</strong> '. $ordencompra->total .'<br>
                            </div>
                            <hr>
                            <p>Le recordamos acercarse a nuestro centro médico para solicitar su comprobante de venta.</p>
                            <p>¡Gracias por preferir <a href="https://sistemas.centromedicoosi.com">http://sistemas.centromedicoosi.com</a></p>
                            <p>Que tengas un buen día.</p>
                        </div>
                        <img src="https://sistemas.centromedicoosi.com/img/osi/email/emailfooter.jpg" width="100%">';

                $build = $sendEmail->send($entidad->email, $subject, $html);

                //$ordencompra->message = $build;
                $ordencompra->fill(array('mail' => $entidad->email));
                $ordencompra->save();
            }

        } catch (QueryException $e) {
            DB::rollback();
        }
        DB::commit();

        return $this->crearRespuesta('Orden compra ha sido creado.', 201, '', '', $ordencompra);
    }

}
