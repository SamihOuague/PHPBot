<?php
require_once('vendor/autoload.php');

class CoinbaseConnector {
    protected $key;
    protected $passphrase;
    protected $secret;

    const GET = "GET";
    const POST = "POST";

    public function __construct($passphrase, $key, $secret) {
        $this->key = $key;
        $this->passphrase = $passphrase;
        $this->secret = $secret;
    }

    public function createRequest($route, $method, $b="") {
        $client = new \GuzzleHttp\Client();
        $timestamp = time();
        $sign = $timestamp. $method . $route. $b;
        $hashsign = base64_encode(hash_hmac("sha256", $sign, base64_decode($this->secret), true));
        try {
            $response = $client->request($method, 'https://api-public.sandbox.exchange.coinbase.com'. $route, [
                'body' => $b,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'CB-ACCESS-KEY' => $this->key,
                    'CB-ACCESS-SIGN' => $hashsign,
                    'CB-ACCESS-TIMESTAMP' => $timestamp,
                    'CB-ACCESS-PASSPHRASE' => $this->passphrase,
                ],
            ]);
            $body = (string) $response->getBody();
            return json_decode($body, true);
        } catch(Exception $e) {
            return $e->getMessage();
        }
    }

    public function createSimpleRequest($route) {
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request("GET", 'https://api.exchange.coinbase.com'. $route, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);
            $body = (string) $response->getBody();
            return json_decode($body, true);
        } catch(Exception $e) {
            return $e->getMessage();
        }
    }
}