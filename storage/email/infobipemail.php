<?php
define('MULTIPART_BOUNDARY','-----------------------'.md5(time()));
define('EOL',"\r\n");// PHP_EOL cannot be used for emails we need the CRFL '\r\n'

/*
 * Method to convert an associative array of parameters into the HTML body string
*/
function getBody($fields) {
    $content = '';
    foreach ($fields as $FORM_FIELD => $value) {
        $content .= '--' . MULTIPART_BOUNDARY . EOL;
        $content .= 'Content-Disposition: form-data; name="' . $FORM_FIELD . '"' . EOL;
        $content .= EOL . $value . EOL;
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
    'subject' => 'Mail subject text',
    'text' => 'Mail body text lalalal',
    'bulkId' => 'aa'
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