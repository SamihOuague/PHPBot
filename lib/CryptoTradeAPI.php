<?php
require_once('vendor/autoload.php');
require_once('CoinbaseConnector.php');
require_once('Config.php');

class CryptoTradeAPI extends CoinbaseConnector {
    protected $profileId;
    protected $accounts;

    public function __construct() {
        parent::__construct(Config::getConfig()["passphrase"], 
                        Config::getConfig()["key"], 
                        Config::getConfig()["secret"]);
        $this->setProfileId();
    }

    public function setProfileId() {
        $this->accounts = $this->getAccounts();
        $this->profileId = $this->accounts[0]["profile_id"];
    }

    public function getProfileId() {
        return $this->profileId;
    }

    public function getAccounts() {
        if (isset($this->accounts) && $this->accounts)
            return $this->accounts;
        else
            return $this->createRequest("/accounts", "GET");
    }

    public function getCurrencies() {
        return $this->createRequest("/currencies", "GET");
    }

    public function getCurrency(string $id) {
        $route = "/currencies"."/".$id;
        return $this->createRequest($route, "GET");
    }

    public function getTradingPair() {
        return $this->createRequest("/products", "GET");
    }

    public function convert($from, $to, $amount) {
        $body = [
            "profile_id" => $this->getProfileId(),
            "from" => $from,
            "to" => $to,
            "amount" => $amount,
        ];
        return $this->createRequest("/conversions", "POST", json_encode($body));
    }

    public function takeOrder($idProd, $funds, $side, $price) {
        $body = [
            "type" => "limit",
            "side" => $side,
            "stp" => "dc",
            "time_in_force" => "GTC",
            "cancel_after" => "min",
            "post_only" => "false",
            "product_id" => $idProd,
            "size" => $funds,
            "price" => $price,
        ];
        return $this->createRequest("/orders", "POST", json_encode($body));
    }

    public function getOrder($idOrder) {
        $route = "/orders"."/".$idOrder;
        return $this->createRequest($route, "GET");
    }

    public function getWallets() {
        return $this->createRequest("/coinbase-accounts", "GET");
    }

    public function getFees() {
        return $this->createRequest("/fees", "GET");
    }

    public function getCandles($idProd, $granu = "60") {
        $route = "/products"."/".$idProd."/candles?granularity=".$granu;
        return $this->createSimpleRequest($route);
    }

    public function getCandlesFrom($idProd, $from, $to, $granu = "60") {
        $route = "/products"."/".$idProd."/candles?granularity=".$granu."&start=".$from."&end=".$to;
        return $this->createSimpleRequest($route);
    }

    public function ticker($idProd) {
        $route = "/products"."/". $idProd ."/ticker";
        return $this->createSimpleRequest($route);
    }
}