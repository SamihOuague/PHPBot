<?php
require_once("./lib/CryptoTradeAPI.php");
require_once("Wallet.php");

class CryptoTradeBOT_V2 {
    protected $candles;
    protected $walletA;
    protected $walletB;
    protected $api;
    public $signal;

    public function __construct($walletA, $walletB, $dataset) {
        $this->setCandles($dataset);
        $this->setWalletA($walletA);
        $this->setWalletB($walletB);
        $this->api = new CryptoTradeAPI();
        $this->signal = "none";
    }

    public function getWins() {
        return $this->wins;
    }

    public function getLosses() {
        return $this->losses;
    }

    public function getRSI($period = 14, $pos = 0) {
        $candles = $this->getCandles();
        $avgHarray = [];
        $avgBarray = [];
        for ($i = $pos + $period; $i >= $pos; $i--) {
            $ystrD = (float) $candles[$i + 1][4];
            $crnt = (float) $candles[$i][4];
            $diff = $ystrD - $crnt;
            if ($diff < 0) {
                $avgBarray[] = 0;
                $avgHarray[] = -1 * $diff;
            } else {
                $avgBarray[] = $diff;
                $avgHarray[] = 0;
            }
        }
        $avgB = 0;
        $avgH = 0;
        for ($i = 0; $i < count($avgBarray); $i++) {
            $avgB += $avgBarray[$i];
            $avgH += $avgHarray[$i];
        }
        
        $avgB = ($avgB / count($avgBarray));
        $avgH = ($avgH / count($avgHarray));
        $avgDiff = $avgH - $avgB;
        $rsi = 50;
        if ($avgB != 0 && $avgH != 0)
            $rsi = 100 - ((100/(1 + ($avgH/$avgB))));
        return $rsi;
    }

    public function getCandles() {
        return $this->candles;
    }

    public function setCandles(array $candles) {
        return $this->candles = $candles;
    }

    public function getCandle(int $index) {
        return $this->candles[$index];
    }

    public function getWalletA() {
        return $this->walletA;
    }

    public function getWalletB() {
        return $this->walletB;
    }

    public function setWalletA($wallet) {
        return $this->walletA = $wallet;
    }

    public function setWalletB($wallet) {
        return $this->walletB = $wallet;
    }

    public function sell($price) {
        $walletA = $this->getWalletA();
        $api = $this->api;
        if (round($walletA->getFunds(), 5) > 0) {
            $order = $api->takeOrder("LTC-BTC", $walletA->getFunds(), "sell", $price);
            if (isset($order["id"])) {
                $orderBis = $api->getOrder($order["id"]);
                while(isset($orderBis["status"]) && $orderBis["status"] != "done") {
                    $orderBis = $api->getOrder($order["id"]);
                    var_dump($orderBis);
                    sleep(5);
                }
                if ($order && isset($orderBis["executed_value"])) {
                    $walletA = $this->getWalletA();
                    $walletB = $this->getWalletB();
                    $walletA->setFunds(0);
                    $walletB->setFunds($orderBis["executed_value"] - $orderBis["fill_fees"]);
                    $this->setWalletA($walletA);
                    $this->setWalletB($walletB);
                }
                return $orderBis;
            } else {
                return 0;
            }
        }
        return 0;
    }

    public function buy($price) {
        $api = $this->api;
        $walletB = $this->getWalletB();
        $walletA = $this->getWalletA();
        if (round($walletB->getFunds(), 5) > 0) {
            $fees = $api->getFees()["maker_fee_rate"];
            $buySize = $price * $walletA->getFunds();
            $fee = $buySize * $fees;
            $fund = substr((string) ($buySize - $fee), 0, 8);
            $order = $api->takeOrder("LTC-BTC", $fund, "buy", $price);
            if (isset($order["id"])) {
                $orderBis = $api->getOrder($order["id"]);
                while(isset($orderBis["status"]) && $orderBis["status"] != "done") {
                    $orderBis = $api->getOrder($order["id"]);
                    var_dump($orderBis);
                    sleep(5);
                }
                if ($order && isset($orderBis["filled_size"])) {
                    $walletA = $this->getWalletA();
                    $walletB = $this->getWalletB();
                    $walletA->setFunds(0);
                    $walletB->setFunds($orderBis["filled_size"]);
                    $this->setWalletA($walletA);
                    $this->setWalletB($walletB);
                }
                return $orderBis;
            } else {
                return 0;
            }
        }
        return 0;
    }

    public function makeDecision($currentPrice, $perteMax = 3.5, $gainMax = 5.9) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $rsi = $this->getRSI();
        if ($rsi >= 70 && round($walletB->getFunds(), 5) > 0) {
            $this->signal = "buy";
        } elseif ($rsi <= 30 && round($walletA->getFunds(), 5) > 0) {
            $this->signal = "sell";
        }
        if ($this->signal == "sell" && $rsi > 30) {
            $this->signal = "none";
            $this->sell($currentPrice);
        } elseif ($this->signal == "buy" && $rsi < 70) {
            $this->signal = "none";
            $this->buy($currentPrice);
        }
    }
}