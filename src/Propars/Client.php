<?php

define('API_ROOT', 'https://api.propars.net/api/v1/');



class Client
{

    private $token;

    function __construct($token)
    {
        $this->token = $token;
    }

    static function connect($username, $password)
    {
        $token = Client::get_token($username, $password)['token'];
        return new Client($token);
    }

    public static function get_token($username, $password){
        $data = array('username' => $username, 'password' => $password);
        $nullClient = new Client(null);
        return $nullClient->call_api($endpoint='api-token-auth/', $method='POST', $data);
    }

    public function call_api(&$endpoint, &$method = 'GET', &$data = null)
    {
        if(substr($endpoint, 0, strlen(API_ROOT)) !== API_ROOT){
            $endpoint = API_ROOT.$endpoint;
        }
        print_r($endpoint."\n");

        $crl = curl_init();

        $headr = array();
        $headr[] = 'Content-type: application/json';
        if ($this->token) {
            $headr[] = 'Authorization: Token ' . $this->token;
        }

        curl_setopt($crl, CURLOPT_URL, $endpoint);
        curl_setopt($crl, CURLOPT_HTTPHEADER, $headr);
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
        if (strtoupper($method) === 'POST') {
            curl_setopt($crl, CURLOPT_POST, true);
            curl_setopt($crl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        $rest = curl_exec($crl);

        $httpcode = curl_getinfo($crl, CURLINFO_HTTP_CODE);
        curl_close($crl);

        if ($httpcode >= 400) {
            var_dump($rest);
            die();
        }
        return json_decode($rest, true);
    }
}





?>