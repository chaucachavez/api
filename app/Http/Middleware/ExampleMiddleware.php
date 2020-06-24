<?php

namespace App\Http\Middleware;

use Closure;
use \Firebase\JWT\JWT;

class ExampleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {   
        /* Autor: chaucachavez@gmail.com
         * Descripcion: Capa de tipo before de seguridad.
         * Middleware para la proteccion de datos no autorizada, basado en JSON Web Tokens https://jwt.io/ 
         */
        
        $key = "x1TLVtPhZxN64JQB3fN8cHSp69999999";
        $tokenRequest = $request->header('AuthorizationToken');
        
        if(empty($tokenRequest) && isset($request->all()['us'])){
            $tokenRequest = $request->all()['us']; 
        }
        
        $tokenDecode = [];        
        if(!empty($tokenRequest)){
            $tokenDecode = JWT::decode($tokenRequest, $key, array('HS256'));
        }
        
        $tokenstatus = ($tokenDecode === 'expirado' || empty($tokenDecode))?false:true;        
                
        if(!$tokenstatus){            
            return response()->json(['tokenstatus'=> $tokenstatus, 'code' =>200/*, 'token' => $tokenDecode*/], 200);
        }
        
        return $next($request);
    }
}
