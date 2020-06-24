<?php

namespace App\Http\Controllers;

use Excel;
use \Firebase\JWT\JWT;
use Illuminate\Http\Request;

use App\Models\entidad;
use App\Models\informe; 

class informeController extends Controller {
    
    // public $pathImg =  'C:\\xampp7.3\\htdocs\\apiosi\\public\\informes_medicos\\';
    public $pathImg =  '/home/centromedico/public_html/apiosi/public/informes_medicos/';
    // public $pathImg =  '/home/ositest/public_html/apiosi/public/informes_medicos/';
    
    public function __construct(Request $request) {
        $this->getToken($request);
    }  
 
    public function destroy($enterprise, $id) {
  
        $informe = informe::find($id);

        //VALIDACIONES
        /* 1.- Validar firma.
         */
        if(!empty($informe->identidad_firma)) {
            return $this->crearRespuesta('Historia clínica tiene firma electrónica. No se puede eliminar.', [200, 'info']);
        } 
        //VALIDACIONES
        
        if ($informe) {
            \DB::beginTransaction();
            try {                 

                if (isset($informe->archivo) && !empty($informe->archivo)) {                  
                    if (unlink($this->pathImg . $informe->archivo)) {  
                        $auditoria = ['deleted_at' => date('Y-m-d H:i:s'), 'id_deleted_at' => $this->objTtoken->my];
                        $informe->fill($auditoria);
                        $informe->save();
                    }
                }  

               
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Historia clínica a sido eliminado.', 200);
        }
        return $this->crearRespuestaError('Historia clínica no encotrado', 404);
    }

    public function firmar(Request $request, $enterprise, $id) {

        $informe = informe::find($id);

        $request = $request->all();

        if (isset($request['idmedico'])) {
            $identidad_firma = $request['idmedico'];
        } else {
            $identidad_firma = $this->objTtoken->my;            
        }

        $entidad = entidad::find($identidad_firma); 

        //VALIDACIONES
        /* 1.- Validar firma */
        if(!empty($informe->identidad_firma)) {
            return $this->crearRespuesta('Historia tiene firma electrónica. No se puede firmar.', [200, 'info']);
        } 

        // \Log::info(print_r($entidad, true));
        // return $this->crearRespuesta('Historia clínica a sido firmada.', 200);

        if(empty($entidad) || empty($entidad->audittrail)) {
            return $this->crearRespuesta('No cuenta con permiso de firma digital.', [200, 'info']);
        }

        $credenciales = $entidad->audittrail;

        $data = array(
            'email' => $credenciales
        );

        $leer_respuesta = $this->wsFirmaCertificado('auth', $data);
    
        if (empty($leer_respuesta) || empty($leer_respuesta['token'])) {
            return $this->crearRespuesta('Personal no cuenta con certificado digital de firma.', [200, 'info']); 
        } 

        $data = array(
            'file_id' => $informe->idinforme,
            'file_name' => $informe->archivo
        );

        $dataFirma = $this->wsFirmaCertificado('firmar', $data, $leer_respuesta['id'], $leer_respuesta['token']); 
        
        if (!isset($dataFirma['status']) && !isset($dataFirma['success'])) {
            return $this->crearRespuesta('No se pudo firmar historia clínica. Comunicarse con Sistemas.', [200, 'info']);
        }

        if (isset($dataFirma['status']) && $dataFirma['status'] === 500) {  
            $auditoria = ['mensaje' => date('Y-m-d H:i:s') . ',' . $this->objTtoken->my . ',' . $dataFirma['error']];
            $informe->fill($auditoria);
            $informe->save(); 

            return $this->crearRespuesta($dataFirma['error'], [200, 'info'], '', '', $dataFirma);    
        }

        if (isset($dataFirma['success']) && !$dataFirma['success']) {  
            $auditoria = ['mensaje' => date('Y-m-d H:i:s') . ',' . $this->objTtoken->my . ',' . ' No se pudo firmar historia clínica.'];
            $informe->fill($auditoria);
            $informe->save();

            return $this->crearRespuesta('No se pudo firmar historia clínica. Comunicarse con Sistemas.', [200, 'info'], '', '', $dataFirma);    
        }

        if ($informe) {
            \DB::beginTransaction();
            try {       
                $auditoria = array(
                    'fecha_firma' => date('Y-m-d H:i:s'), 
                    'identidad_firma' => $identidad_firma, 
                    'mensaje' => $dataFirma['message']
                );

                $informe->fill($auditoria);
                $informe->save(); 
            } catch (QueryException $e) {
                \DB::rollback();
            }
            \DB::commit();

            return $this->crearRespuesta('Historia clínica a sido firmada.', 200);
        }

        return $this->crearRespuestaError('Historia clínica no encotrado', 404);
    }

    private function wsFirmaCertificado($accion, $data, $id = '', $token = '') {

        $url = '';
        $header = [];

        if ($accion === 'auth') {
            $url = 'http://51.81.23.11:8001/auth/';
            $header = array( 
                'Content-Type: application/json'
            );

            // \Log::info('url');
            // \Log::info($url);

            // \Log::info('data');
            // \Log::info($data);
            
            $dataParam = json_encode($data);
        }   

        if ($accion === 'firmar') { 
            // $ckfile = tempnam ("/tmp", 'cookiename'); 
            $url = 'http://51.81.23.11:8001/signature/' . $id .'/';

            $header = array( 
                'Content-Type: application/json',
                'Authorization: JWT '.$token
            );  

            $dataParam = array(
                "path_begin" => "/home/centromedico/public_html/apiosi/public/informes_medicos/",
                "path_end" => "/home/centromedico/public_html/apiosi/public/informes_medicos/firmados/",
                "token" => $token,
                "reason" => "Soy autor del documento",
                "position_x" => 0,
                "position_y" => 0,
                "number_page" => 0,
                "vb" => false,
                "files" => [
                    array(
                        'file_id' => $data['file_id'],
                        'file_name' => $data['file_name'], //"Historia_70573_2019-10-11_08-34-41.pdf"
                    )
                ]
            ); 

            // \Log::info('url');
            // \Log::info($url);

            // \Log::info('dataParam');
            // \Log::info($dataParam);

            $dataParam = json_encode($dataParam);                
        } 

        // 1.Enviar   
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataParam);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $respuesta  = curl_exec($ch); 
        $headers = curl_getinfo($ch, CURLINFO_HTTP_CODE); 

        curl_close($ch);

        // 2.Leer respuesta    
        $leer_respuesta = json_decode($respuesta, true);          
        \Log::info('leer_respuesta');
        \Log::info($leer_respuesta);

        if (isset($leer_respuesta['message']) && $leer_respuesta['message'] === 'Se a firmado [ 1/1 ] archivo(s)') {
            //Todo bien
        } else {
            // \Log::info(print_r($leer_respuesta, true));      
        }

        return $leer_respuesta;
    }
}
