<?php
require_once("./lib/CryptoTradeAPI.php");
require_once("Wallet.php");

class CryptoTradeBOT_V2 {
    protected $candles;
    protected $walletA;
    protected $walletB;
    protected $api;
    public $signal;
    public $lastFunds;

    public function __construct($walletA, $walletB, $dataset) {
        $this->setCandles($dataset);
        $this->setWalletA($walletA);
        $this->setWalletB($walletB);
        $this->api = new CryptoTradeAPI();
        $this->signal = "none";
        $this->lastFunds = $walletA->getFunds();
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
                    $this->lastFunds = $walletA->getFunds();
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
            $buySize = $walletB->getFunds() / $price;
            $fee = $buySize * $fees;
            $fund = substr((string) ($buySize - $fee), 0, 8);
            $order = $api->takeOrder("LTC-BTC", $fund, "buy", $price);
            var_dump($order);
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

    public function isEngulfing($candles, $pos) {
        $top1 = ($candles[$pos - 1][4] > $candles[$pos - 1][3]) ? $candles[$pos - 1][4] : $candles[$pos - 1][3];
        $top2 = ($candles[$pos][4] > $candles[$pos][3]) ? $candles[$pos][4] : $candles[$pos][3];
        $down1 = ($candles[$pos - 1][4] < $candles[$pos - 1][3]) ? $candles[$pos - 1][4] : $candles[$pos - 1][3];
        $down2 = ($candles[$pos][4] < $candles[$pos][3]) ? $candles[$pos][4] : $candles[$pos][3];
        if ($top1 < $top2 && $down1 < $down2) {
            //var_dump(date("Y-m-d H:i:s", $candles[$pos][0]));
            return true;
        } else {
            return false;
        }
    }

    public function isHammer($candle) {
        if ($candle[4] > $candle[3]) {
            $diffCand = $candle[4] - $candle[3];
            $diffAvg = $candle[2] - $candle[4];
            if ($diffAvg / $diffCand > 1)
                return true;
        } else {
            $diffCand = $candle[3] - $candle[4];
            $diffAvg = $candle[2] - $candle[3];
            $ratio = 0;
            if ($diffCand != 0 && $diffAvg != 0)
                $ratio = $diffAvg / $diffCand;
            if ($ratio > 1)
                return true;
        }
        return false;
    }
    

    public function makeDecision($currentPrice, $perteMax = 3.5, $gainMax = 5.9) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $rsi = $this->getRSI();
        if ($rsi >= 70 && isEngulfing($candles, $lastPos)) {
            $this->signal = "sell";
        } elseif ($rsi <= 30 && isHammer($candles[$lastPos])) {
            $this->signal = "buy";
        }

        if (round($walletB->getFunds(), 5) > 0 && $this->lastFunds != 0) {
            $ltcPot = $walletB->getFunds() / $currentPrice;
            $diff = $ltcPot - $this->lastFunds - ($ltcPot * 0.0035);
            $ratio = (($diff / $this->lastFunds) * 100);
            if ($ratio < -3 || $ratio > 3) {
                $position = "buy";
            }
        }

        if ($this->signal == "sell" && round($walletA->getFunds(), 5) > 0) {
            $this->signal = "none";
            $this->sell($currentPrice);
        } elseif ($this->signal == "buy" && round($walletB->getFunds(), 5) > 0) {
            $this->signal = "none";
            $this->buy($currentPrice);
        }
    }
}