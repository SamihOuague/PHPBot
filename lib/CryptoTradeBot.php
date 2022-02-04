<?php
require_once('vendor/autoload.php');
require_once('CoinbaseConnector.php');
require_once('Config.php');

class CryptoTradeBot extends CoinbaseConnector {
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
        return $this->createRequest("/accounts", "GET");
    }

    public function getCurrencies() {
        return $this->createRequest("/currencies", "GET");
    }

    public function getCurrency(string $id) {
        $route = "/currencies"."/".$id;
        return $this->createRequest($route, "GET");
    }

    public function convert($from, $to, $amount) {
        $route = "/conversions";
        $body = [
            "profile_id" => $this->getProfileId(),
            "from" => $from,
            "to" => $to,
            "amount" => $amount,
        ];
        return $this->createRequest($route, "POST", json_encode($body));
    }

    public function getWallets() {
        return $this->createRequest("/coinbase-accounts", "GET");
    }
}