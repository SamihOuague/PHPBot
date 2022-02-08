<?php
require_once("./lib/CryptoTradeAPI.php");
require_once("Wallet.php");

class CryptoTradeBOT_V2 {
    protected $candles;
    protected $walletA;
    protected $walletB;
    protected $lastFunds;
    protected $wins;
    protected $losses;
    protected $available = true;
    protected $api;

    public function __construct($walletA, $walletB, $dataset) {
        $this->wins = 0;
        $this->losses = 0;
        $this->setCandles($dataset);
        $this->setWalletA($walletA);
        $this->setWalletB($walletB);
        $this->lastFunds = $walletA->getFunds();
        $this->api = new CryptoTradeAPI();
    }

    public function getWins() {
        return $this->wins;
    }

    public function getLosses() {
        return $this->losses;
    }

    public function getRSI($period, $pos) {
        $candles = $this->getCandles();
        $avgHarray = [];
        $avgBarray = [];
        for ($i = $pos + $period; $i > $pos; $i--) {
            $ystrD = (float) $candles[$i - 1][4];
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
        $walletB = $this->getWalletB();
        $api = $this->api;
        $this->available = true;
        $this->lastFunds = $walletA->getFunds();
        if (round($walletA->getFunds(), 6) > 0) {
            $order = $api->takeOrder("ETH-BTC", $walletA->getFunds(), "sell", $price);
            $this->setWalletB($walletA->sellAll($price, $walletB));
        }
    }

    public function buy($price) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $api = $this->api;
        if (round($walletB->getFunds(), 6) > 0) {
            $fees = $api->getFees()["maker_fee_rate"];
            $btcprice = ($walletB->getFunds() / $price) - (($walletB->getFunds() / $price) * $fees);
            $btctoetc = substr((string) $btcprice, 0, 8);
            $order = $api->takeOrder("ETH-BTC", $btctoetc, "buy", $price);
            $this->setWalletA($walletB->buyAll($price, $walletA));
        }
        if ($this->lastFunds < $walletA->getFunds()) {
            echo "\033[32mWIN : +". ($walletA->getFunds() - $this->lastFunds)."\n\033[0m";
            $this->wins++;
        }
        else {
            echo "\033[31mLOSS : -". ($this->lastFunds - $walletA->getFunds())."\n\033[0m";
            $this->losses++;
        }
    }

    public function makeDecision($currentPrice, $perteMax = 3.5, $gainMax = 5.9, $period = 14) {
        $candles = $this->getCandles();
        $buyIt = false;
        $rsi = $this->getRsi($period, 0);
        if (round($this->getWalletB()->getFunds(), 6) != 0) {
            $btcToETH = ($this->getWalletB()->getFunds() / $currentPrice);
            $decalage = $btcToETH - $this->lastFunds;
            if ($decalage < 0) {
                $decalage *= -1;
                $perte = round(($decalage / $this->lastFunds) * 100, 2);
                if ($perte > $perteMax) {
                    $buyIt = true;
                }
            } elseif ($decalage > 0) {
                $gain = round(($decalage / $this->lastFunds) * 100, 2);
                if ($gain >= $gainMax) {
                    $buyIt = true;
                }
            }
        }
        if ($rsi > 70 || $buyIt) {
            if (round($this->getWalletB()->getFunds(), 6) != 0 && $this->available) {
                $this->available = false;
                $this->buy($currentPrice);
            }
        } elseif ($rsi < 60) {
            if (round($this->getWalletA()->getFunds(), 6) != 0) {
                $this->sell($currentPrice);
            }
        }
    }
    
    public function refreshWallets() {
        $accounts = $this->api->getAccounts();
        $response = [];
        foreach ($accounts as $key => $value) {
            if ($value["currency"] == "BTC") {
                $wallet = $this->getWalletB();
                $wallet->setFunds($value["available"]);
                $this->setWalletB($wallet);
            } elseif ($value["currency"] == "ETH") {
                $wallet = $this->getWalletA();
                $wallet->setFunds($value["available"]);
                $this->setWalletA($wallet);
            }
        }
    }
}