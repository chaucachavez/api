<div style="background: #eceff4; padding: 50px 20px; color: #514d6a;" width="100%">
  <div style="max-width: 700px; margin: 0px auto; font-size: 14px">
    
    <div style="margin-bottom: 10px;">
      <div style="float: left;">
        <a href="https://pacientes.centromedicoosi.com"><img alt="OSI" src="{{$imgHeader}}" height="56"></a>
      </div>

      <div style="float: right; padding-top: 20px;">
        <span style="color: #005CB8; font-size: 16px;"> Reservación de <strong>citas en linea</strong> </span>
      </div>
      <div style="clear: both;"></div>
    </div>

    <div style="padding: 40px 40px 20px 40px; background: #fff;">
      <table border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
        <tbody>
          <tr>
            <td>
             

            <p>Hola <strong>{{$entidad->nombre}}</strong></p>
            <p>
                 <span style="font-size: 18px;">B</span>ienvenido a citas en linea <a href="https://pacientes.centromedicoosi.com" style="text-decoration: none">https://pacientes.centromedicoosi.com</a> ahora de forma fácil y segura podrás realizar: 
            </p> 

            <table border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
              <tr>
                <td style="padding-left: 30px; padding-top: 5px; height: 12px;" width="20" height="18">                   
                  <img alt="OSI" src="https://sistemas.centromedicoosi.com/apiosi/public/img/osi/vineta.png"> 
                </td>
                <td style="height: 12px;" height="18"><span>Reservar cita médica</span></td>
              </tr>
              <tr>
                <td style="padding-left: 30px; padding-top: 5px; height: 12px;" width="20"> 
                  <img alt="OSI" src="https://sistemas.centromedicoosi.com/apiosi/public/img/osi/vineta.png"> 
                </td>
                <td style="height: 12px;">
                  <span>Reservar cita a terapia</span>
                </td>
              </tr>
              <tr>
                <td style="padding-left: 30px; padding-top: 5px; height: 12px;" width="20"> 
                  <img alt="OSI" src="https://sistemas.centromedicoosi.com/apiosi/public/img/osi/vineta.png"> 
                </td>
                <td style="height: 12px;">
                  <span>Realizar pagos de cita</span>
                </td>
              </tr>
              <tr>
                <td style="padding-left: 30px; padding-top: 5px; height: 12px;" width="20"> 
                  <img alt="OSI" src="https://sistemas.centromedicoosi.com/apiosi/public/img/osi/vineta.png"> 
                </td>
                <td style="height: 12px;">
                  <span>Consultar tus tratamientos realizados(Ciclos de atención)</span>
                </td>
              </tr>
              <tr>
                <td style="padding-left: 30px; padding-top: 5px; height: 12px;" width="20"> 
                  <img alt="OSI" src="https://sistemas.centromedicoosi.com/apiosi/public/img/osi/vineta.png"> 
                </td>
                <td style="height: 12px;">
                  <span>Consultar tus pagos y descargar comprobante</span>
                </td>
              </tr>
            </table>  

            <br>
            Tus datos de ingreso son:<br>

            @if ($entidad->iddocumento === 1)
              <strong>Documento:</strong> DNI<br>
            @endif

            @if ($entidad->iddocumento === 3)
              <strong>Documento:</strong> CARNET EXT.<br>
            @endif

            @if ($entidad->iddocumento === 4)
              <strong>Documento:</strong> PASAPORTE Y OTROS<br>
            @endif
            
            <strong>N° Documento:</strong> {{$entidad->numerodoc}} <br>
            <strong>Contraseña:</strong> {{$entidad->password}} <br>

            <p>Vamos, empecemos reservando tu cita.</p>

            <div style="text-align: center"><a href="https://pacientes.centromedicoosi.com"
                  style="display: inline-block; padding: 20px; margin: 10px 0px 30px; font-size: 18px; color: #fff; background: #00AF41; border-radius: 8px; text-decoration: none; font-weight: bold;">Ingresar aquí</a>
              </div>

            <p>Saludos.<br>
            <a href="https://pacientes.centromedicoosi.com" style="text-decoration: none">https://pacientes.centromedicoosi.com</a>
            </p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div style="text-align: center; font-size: 12px; color: #333333; margin-top: 20px">
      	<p> Centro Médico OSI © Copyright {{date('Y')}}
      	<br> 
      	Central de reservas<br>
        <a href="tel:017390888" class="llamarfono" style="color: #000000; text-decoration: none; font-weight: bold;">01 739 0888</a>
    	</p>
    </div>
  </div>
</div>