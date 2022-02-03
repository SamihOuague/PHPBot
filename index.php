<?php
require_once('vendor/autoload.php');
$client = new \GuzzleHttp\Client();

$method = "GET";
$requestPath = "/accounts";
$timestamp = time();

$passphrase = "1mysf361tzq";
$secret = "kADf6WCZ6aGldK8wsOisfCdE1yV315cXY6sB7MjrCe2q6MxCIsGJhJN3ajiozasQkhzk7pksEykoCWrvpgsunA==";
$key = "bfada5e5e8fe74dab2f3154d7b890cd7";
$sign = $timestamp. $method. $requestPath;
echo $sign."\n";
$hashsign = base64_encode(hash_hmac("sha256", $sign, base64_decode($secret), true));
echo $hashsign."\n";

try {
    $response = $client->request('GET', 'https://api-public.sandbox.exchange.coinbase.com/accounts', [
      'headers' => [
        'Accept' => 'application/json',
        'CB-ACCESS-KEY' => $key,
        'CB-ACCESS-SIGN' => $hashsign,
        'CB-ACCESS-TIMESTAMP' => $timestamp,
        'CB-ACCESS-PASSPHRASE' => $passphrase,
      ],
    ]);
    echo $response->getBody();
} catch(Exception $e) {
    echo var_dump($e->getMessage());
}

//secret key: kADf6WCZ6aGldK8wsOisfCdE1yV315cXY6sB7MjrCe2q6MxCIsGJhJN3ajiozasQkhzk7pksEykoCWrvpgsunA==