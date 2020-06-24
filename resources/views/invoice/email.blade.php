<!DOCTYPE html>
<html lang="es">
<head>
    <title>Comprobante electrónico</title>
    <meta charset="utf-8" />
</head>
	<body>
	    <img src="{{$imgHeader}}" width="100%">
	    <div style="padding: 0px 30px 0px 30px; color: #333; font-family: Arial; line-height: 20px;">
		    <p>Buen dia</p>
		    <p>
		        Has recibido un nuevo comprobante.
		    </p> 
		    <p>
		        <strong>Emisor:</strong> {{$venta->afiliado}}<br>
		        <strong>{{$venta->nombredocfiscal}}:</strong> {{$venta->serie}}-{{$venta->serienumero}}<br>
		        <strong>Fecha emisión:</strong> {{$venta->fechaventa}}<br>
		    </p>  
	    </div> 
	</body>
</html>