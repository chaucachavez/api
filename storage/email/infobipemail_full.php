<?php
define('MULTIPART_BOUNDARY','-----------------------'.md5(time()));
define('EOL',"\r\n");// PHP_EOL cannot be used for emails we need the CRFL '\r\n'

function getBodyPart($FORM_FIELD, $value) {
    if ($FORM_FIELD === 'attachment') {
        $content = 'Content-Disposition: form-data; name="'.$FORM_FIELD.'"; filename="'.basename($value).'"' . EOL;
        $content .= 'Content-Type: '.mime_content_type($value) . EOL;
        $content .= 'Content-Transfer-Encoding: binary' . EOL;
        $content .= EOL . file_get_contents($value) .EOL;
    } else {
        $content = 'Content-Disposition: form-data; name="' . $FORM_FIELD . '"' . EOL;
        $content .= EOL . $value . EOL;
    }

    return $content;
}

/*
 * Method to convert an associative array of parameters into the HTML body string
*/
function getBody($fields) {
    $content = '';
    foreach ($fields as $FORM_FIELD => $value) {
        $values = is_array($value) ? $value : array($value);
        foreach ($values as $v) {
            $content .= '--' . MULTIPART_BOUNDARY . EOL . getBodyPart($FORM_FIELD, $v);
        }
    }
    return $content . '--' . MULTIPART_BOUNDARY . '--'; // Email body should end with "--"
}

/*
 * Method to get the headers for a basic authentication with username and passowrd
*/
function getHeader($username, $password){
    // basic Authentication
    $auth = base64_encode("$username:$password");

    // Define the header
    return array("Authorization:Basic TU9VVEVDOjEyMzQ1Njc4", 'Content-Type: multipart/form-data ; boundary=' . MULTIPART_BOUNDARY );
}

// URL to the API that sends the email.
$url = 'https://api.infobip.com/email/1/send';

// Associate Array of the post parameters to be sent to the API
$postData = array(
    'from' => 'admision@centromedicoosi.com',
    'to' => 'chaucachavez@gmail.com',
    'subject' => 'Orden de compra - Centro Médico OSI',
    'html' => '<img src="https://sistemas.centromedicoosi.com/img/osi/email/emailhead.png" width="100%"><div style="padding: 0px 30px 0px 30px; color: #333; font-family: Arial; line-height: 20px;"><h5><strong>HOLA! JULIO CÉSAR,</strong></h5><p>¡Bienvenido al sistema de Citas en Línea del Centro Médico OSI! Ahora desde la portal web del paciente podrás realizar:</p><ul><li>Reserva de cita médica.</li><li>Pagos en linea de tu cita médica.</li><li>Consultar resultados de citas médicas(Tratamientos).</li><li>Consultar terapias realizadas.</li><li>Editar tus datos datos personales y mucho más.</li></ul><br><p><strong>Tus datos de ingreso al portal del paciente son:</strong></p><p><strong>Documento:</strong> DNI<br><strong>N° Documento:</strong> 44120026<br><strong>Contraseña:</strong> 44120026<br></p><p>Para ingresar al portal del paciente aqui:<a href="https://sistemas.centromedicoosi.com">https://sistemas.centromedicoosi.com</a></p><p>Que tengas un buen día.</p></div><img src="https://sistemas.centromedicoosi.com/img/osi/email/emailfooter.jpg" width="100%"></body>'
);

// Create the stream context.
$context = stream_context_create(array(
    'http' => array(
          'method' => 'POST',
          'header' => getHeader('username', 'password'),
          'content' =>  getBody($postData),
    )
));

// Read the response using the Stream Context.
$response = file_get_contents($url, false, $context);
var_dump($response);
?>