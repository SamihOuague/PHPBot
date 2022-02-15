<?php
require_once("BinanceConnector.php");
require_once("BinanceConfig.php");

class BinanceTradeAPI extends BinanceConnector {
    public function __construct() {
        $config = BinanceConfig::getConfig();
        parent::__construct($config["key"], $config["secret"]);
    }

    public function getAccounts() {
        return $this->createRequest("/api/v3/account");
    }

    public function getCandles($symbol, $interval="15m") {
        return $this->createSimpleRequest("/api/v3/klines", [
            "symbol" => $symbol,
            "interval" => $interval,
        ]);
    }

    public function getOrder($symbol, $orderId) {
        return $this->createRequest("/api/v3/order", "GET", ["symbol"=>$symbol, "orderId"=>$orderId]);
    }

    public function createOrder($symbol, $side, $size, $price, $tif="GTC", $type="limit") {
        return $this->createRequest("/api/v3/order", 
                                    "POST", 
                                    [
                                        "symbol" => $symbol,
                                        "side" => $side,
                                        "type" => $type,
                                        "quantity" => $size,
                                        "price" => $price,
                                        "timeInForce"=> $tif,
                                    ]
                                );
    }

    public function ticker($symbol) {
        return $this->createSimpleRequest("/api/v3/ticker/price", ["symbol" => $symbol]);
    }
}