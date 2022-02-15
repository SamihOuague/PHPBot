<?php
require_once('vendor/autoload.php');

class BinanceConnector {
    protected $key;
    protected $passphrase;
    protected $secret;

    const GET = "GET";
    const POST = "POST";

    public function __construct($key, $secret) {
        $this->key = $key;
        $this->secret = $secret;
    }

    public function createRequest($route, $method="GET", $b=[]) {
        $client = new \GuzzleHttp\Client();
        $timestamp = time();
        $sign = "timestamp=". (time() * 1000);
        foreach ($b as $key => $value) {
            $sign .= "&". $key ."=". $value;
        }
        $sign .= "&signature=". hash_hmac("sha256", $sign, $this->secret);
        $route .= "?". $sign;
        try {
            $response = $client->request($method, 'https://api.binance.com'. $route, [
                'headers' => [
                    'X-MBX-APIKEY' => $this->key,
                ],
            ]);
            $body = (string) $response->getBody();
            return json_decode($body, true);
        } catch(Exception $e) {
            return $e->getMessage();
        }
    }

    public function createSimpleRequest($route, $b=[]) {
        $client = new \GuzzleHttp\Client();
        $sign = "";
        foreach ($b as $key => $value) {
            $sign .= ($sign != "") ? "&" : "?";
            $sign .= $key ."=". $value;
        }
        $route .= $sign;
        try {
            $response = $client->request("GET", 'https://api.binance.com'. $route);
            $body = (string) $response->getBody();
            return json_decode($body, true);
        } catch(Exception $e) {
            return $e->getMessage();
        }
    }
}