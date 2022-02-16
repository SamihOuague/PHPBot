<?php
require_once("Binance/BinanceTradeAPI.php");
require_once("Wallet.php");

class CryptoTradeBOT_V3 {
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
        $this->api = new BinanceTradeAPI();
        $this->signal = "none";
        $this->lastFunds = $walletA->getFunds();
    }

    public function getRSI($period = 15, $pos = 0) {
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
        if ($avgB != 0 && $avgH != 0)
            $rsi = 100 - ((100/(1 + ($avgH/$avgB))));
        else
            $rsi = 100 - (100/1);
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

    public function sell($price, $fee = 0.00075) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $order = $this->api->createOrder("LTCBNB", "sell", substr($walletA->getFunds(), 0, 5), $price);
        if (isset($order["orderId"])) {
            $orderBis = $this->api->getOrder("LTCBNB", $order["orderId"]);
            while (isset($orderBis["status"]) && $orderBis["status"] != "FILLED") {
                $orderBis = $this->api->getOrder("LTCBNB", $order["orderId"]);
                system("clear");
                if (!isset($orderBis) || !isset($orderBis["status"]))
                    return 0;
                echo "ORDER BUY STATUS => ". $orderBis["status"] ."...\n";
                if ($orderBis["status"] == "CANCELED")
                    return 0;
                sleep(5);
            }
            $size = $walletA->getFunds() * $price;
            $fees = $size * $fee;
            $walletB->setFunds($size - $fees);
            $walletA->setFunds(0);
            $this->setWalletA($walletA);
            $this->setWalletB($walletB);
            return $orderBis;
        }
        return 0;
    }

    public function buy($price, $fee = 0.00075) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $order = $this->api->createOrder("LTCBNB", "buy", substr($walletB->getFunds()  / $price, 0, 5), $price);
        if (isset($order["orderId"])) {
            $orderBis = $this->api->getOrder("LTCBNB", $order["orderId"]);
            while (isset($orderBis["status"]) && $orderBis["status"] != "FILLED") {
                $orderBis = $this->api->getOrder("LTCBNB", $order["orderId"]);
                system("clear");
                if (!isset($orderBis) || !isset($orderBis["status"]))
                    return 0;
                echo "ORDER BUY STATUS => ". $orderBis["status"] ."...\n";
                if ($orderBis["status"] == "CANCELED")
                    return 0;
                sleep(5);
            }
            $size = $walletB->getFunds() / $price;
            $fees = $size * $fee;
            $walletA->setFunds($size - $fees);
            $walletB->setFunds(0);
            $this->setWalletA($walletA);
            $this->setWalletB($walletB);
            return $orderBis;
        }
        return 0;
    }

    public function upOrDown($pos) {
        if ($this->getRSI(15, $pos) > $this->getRSI(15, $pos + 1) && $this->getRSI(15, $pos + 1) > $this->getRSI(15, $pos + 2)) {
            return "UP";
        } elseif ($this->getRSI(15, $pos) < $this->getRSI(15, $pos + 1) && $this->getRSI(15, $pos + 1) < $this->getRSI(15, $pos + 2)) {
            return "DOWN";
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

    public function makeDecision($currentPrice) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $rsi = $this->getRSI(15, 1);
        $lastFunds = $this->lastFunds;
        $candleA = $this->getCandle(0);
        $candleB = $this->getCandle(5);
        if ($rsi >= 67) {
            $this->signal = "sell";
        } elseif ($rsi <= 33) {
            $this->signal = "buy";
        }
        
        if (round($walletB->getFunds(), 2) > 0 && $lastFunds != 0) {
            $ltcPot = $walletB->getFunds() / $currentPrice;
            $diff = $ltcPot - $lastFunds - ($ltcPot * 0.00075);
            $ratio = (($diff / $lastFunds) * 100);
            if ($ratio > 3.6) {
                $this->signal = "close";
            } elseif ($ratio < -4) {
                $this->signal = "close";
            }
        }

        if ($this->signal == "sell" && round($walletA->getFunds(), 2) > 0.05 && round(($candleA[4] - $candleB[4]) * 10000, 2) > 15) {
            $this->signal = "none";
            $this->lastFunds = $walletA->getFunds();
            $this->sell($currentPrice);
        } elseif ($this->signal == "buy" && round($walletB->getFunds(), 2) > 0.05 && round(($candleA[4] - $candleB[4]) * 10000, 2) < -15) {
            $this->signal = "none";
            $this->buy($currentPrice);
        } elseif ($this->signal == "close" && round($walletB->getFunds(), 2) > 0.05) {
            $this->signal = "none";
            $this->buy($currentPrice);
        }
    }
}