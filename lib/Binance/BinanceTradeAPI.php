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

    public function getCandles($symbol, $interval="15m", $start="", $end="") {
        $params = [
            "symbol" => $symbol,
            "interval" => $interval,
        ];
        if ($start != "") {
            $params["startTime"] = $start;
        }
        return $this->createSimpleRequest("/api/v3/klines", $params);
    }

    public function getOrder($symbol, $orderId) {
        return $this->createRequest("/api/v3/order", "GET", ["symbol"=>$symbol, "orderId"=>$orderId]);
    }

    public function createOrder($symbol, $side, $size, $price, $tif="GTC", $type="market") {
        $elt = [
            "symbol" => $symbol,
            "side" => $side,
            "type" => $type,
        ];
        if ($side == "buy") {
            $elt["quoteOrderQty"] = round(($size - 1), 0, PHP_ROUND_HALF_DOWN); 
        } else {
            $elt["quantity"] =  round(($size - 1), 0, PHP_ROUND_HALF_DOWN);
        }
        return $this->createRequest("/api/v3/order", 
                                    "POST", 
                                    $elt,
                                );
    }

    public function ticker($symbol) {
        return $this->createSimpleRequest("/api/v3/ticker/price", ["symbol" => $symbol]);
    }
}