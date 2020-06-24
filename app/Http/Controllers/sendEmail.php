<?php

namespace App\Http\Controllers;

class sendEmail extends Controller
{
    private $MULTIPART_BOUNDARY = null;
    private $EOL = "\r\n";

    function __construct() {
        $this->MULTIPART_BOUNDARY =  "-----------------------".md5(time());
    }

    private function getBodyPart($FORM_FIELD, $value) {
        if ($FORM_FIELD === 'attachment') {
            $content = 'Content-Disposition: form-data; name="'.$FORM_FIELD.'"; filename="'.basename($value).'"' . $this->EOL;
            $content .= 'Content-Type: '.mime_content_type($value) . $this->EOL;
            $content .= 'Content-Transfer-Encoding: binary' . $this->EOL;
            $content .= $this->EOL . file_get_contents($value) .$this->EOL;
        } else {
            $content = 'Content-Disposition: form-data; name="' . $FORM_FIELD . '"' . $this->EOL;
            $content .= $this->EOL . $value . $this->EOL;
        }

        return $content;
    }

    /*
     * Method to convert an associative array of parameters into the HTML body string
    */
    private function getBody($fields) {
        $content = '';
        foreach ($fields as $FORM_FIELD => $value) {
            $values = is_array($value) ? $value : array($value);
            foreach ($values as $v) {
                $content .= '--' . $this->MULTIPART_BOUNDARY . $this->EOL . $this->getBodyPart($FORM_FIELD, $v);
            }
        }
        return $content . '--' . $this->MULTIPART_BOUNDARY . '--'; // Email body should end with "--"
    }

    /*
     * Method to get the headers for a basic authentication with username and passowrd
    */
    private function getHeader($username, $password){
        // basic Authentication
        $auth = base64_encode("$username:$password");

        // Define the header
        return array("Authorization:Basic TU9VVEVDOjEyMzQ1Njc4", 'Content-Type: multipart/form-data ; boundary=' . $this->MULTIPART_BOUNDARY );
    }

    public function send($to, $subject, $html) {
        // URL to the API that sends the email.
        $url = 'https://api.infobip.com/email/1/send';
 
        // Associate Array of the post parameters to be sent to the API
        $postData = array(
            'from' => 'admision@centromedicoosi.com',
            'to' => $to,
            'subject' => $subject,
            'html' => $html
        );

        // Create the stream context.
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => $this->getHeader('username', 'password'),
                'content' =>  $this->getBody($postData),
            )
        ));

        // Read the response using the Stream Context.
        return $response = file_get_contents($url, false, $context);
    }

    public function sendSms($numero, $mensaje) { 

        // dd("{ \"from\":\"InfoSMS\", \"to\": [\"". $numero ."\"], \"text\": \"" . $mensaje ."\" }");
        $curl = curl_init(); 

        curl_setopt_array($curl, array(
          CURLOPT_URL => "http://api.infobip.com/sms/1/text/single",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "{ \"from\":\"OSIsms\", \"to\": \"". $numero ."\", \"text\": \"" . $mensaje ."\" }",
          CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "authorization: Basic TU9VVEVDOkV4aXRvQDIwMTk=",
            "content-type: application/json"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "cURL Error #:" . $err;
        } else {
          return $response;
        }
    }
}
