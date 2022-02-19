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
    public $takeProfit;
    public $stopLoss;

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
            $rsi = 100 - (100/2);
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
        $order = $this->api->createOrder("CHZUSDT", "sell", round($walletA->getFunds()), $price);
        var_dump($order);
        if (isset($order["orderId"])) {
            $orderBis = $this->api->getOrder("CHZUSDT", $order["orderId"]);
            while (isset($orderBis["status"]) && $orderBis["status"] != "FILLED") {
                $orderBis = $this->api->getOrder("CHZUSDT", $order["orderId"]);
                system("clear");
                if (!isset($orderBis) || !isset($orderBis["status"]))
                    return 0;
                echo "ORDER SELL STATUS => ". $orderBis["status"] ."...\n";
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
        $order = $this->api->createOrder("CHZUSDT", "buy", round($walletB->getFunds()), $price);
        var_dump($order);
        if (isset($order["orderId"])) {
            $orderBis = $this->api->getOrder("CHZUSDT", $order["orderId"]);
            while (isset($orderBis["status"]) && $orderBis["status"] != "FILLED") {
                $orderBis = $this->api->getOrder("CHZUSDT", $order["orderId"]);
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

    public function isHammer($candle) {
        if ($candle[4] > $candle[1]) {
            $diffCand = $candle[4] - $candle[1];
            $diffAvg = $candle[2] - $candle[4];
            if ($diffAvg / $diffCand > 1)
                return true;
        } else {
            $diffCand = $candle[1] - $candle[4];
            $diffAvg = $candle[2] - $candle[1];
            $ratio = 0;
            if ($diffCand != 0 && $diffAvg != 0)
                $ratio = $diffAvg / $diffCand;
            if ($ratio > 1)
                return true;
        }
        return false;
    }

    public function makeDecision($currentPrice, $pos = 0) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $rsi = $this->getRSI(15, $pos);
        $nStopA = $currentPrice - ($currentPrice * 0.008);
        $nLimitB = $currentPrice + ($currentPrice * 0.01);
        if ($this->stopLoss < $nStopA) {
            $this->stopLoss = $nStopA;
        }

        if ($this->takeProfit > $nLimitB) {
            $this->takeProfit = $nLimitB;
        }
        
        if ($this->isHammer($this->getCandle($pos)) && $rsi <= 30) {
            $this->signal = "buy";
        }

        if ($currentPrice <= $this->stopLoss && round($walletA->getFunds(), 2) > 10) {
            $this->signal = "none";
            $this->sell(($currentPrice <= $this->stopLoss) ? $this->stopLoss : $this->takeProfit);
            $this->available = false;
            $this->winOrLoss($walletB->getFunds(), $this->lastFundsBNB, $pos, "USDT");
        }

        //if ($this->signal == "sell" && round($walletA->getFunds(), 2) > 0.05) {
        //    $this->signal = "none";
        //    $this->sell($currentPrice);
        //    $this->winOrLoss($walletB->getFunds(), $this->lastFundsBNB, $pos, "USDT");
        //}
        if ($this->signal == "buy" && $rsi > $this->getRSI(15, $pos + 1) && round($walletB->getFunds(), 2) > 10) {
            $this->signal = "none";
            $this->stopLoss = $currentPrice - ($currentPrice * 0.008);
            $this->buy($currentPrice);
            //$this->available = false;
            //$this->winOrLoss($walletA->getFunds(), $this->lastFundsLTC, $pos, "CHZ");
        }
    }
}