<?php
require_once("Binance/BinanceTradeAPI.php");
require_once("Wallet.php");

class CryptoTradeBOT_V3 {
    protected $candles;
    protected $walletA;
    protected $walletB;
    protected $api;
    public $signal;
    public $stopLoss;
    public $takeProfit;
    public $pairs;

    public function __construct($dataset, $pairs = "CHZUSDT") {
        $this->setCandles($dataset);
        $this->api = new BinanceTradeAPI();
        $this->signal = "none";
        $this->stopLoss = round($this->getCandle(0)[4] - ($this->getCandle(0)[4] * 0.01), 4);
        $this->takeProfit = round($this->getCandle(0)[4] + ($this->getCandle(0)[4] * 0.01), 4);
        $this->pairs = $pairs;
        $this->refreshAccounts();
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

    public function refreshAccounts() {
        $api = $this->api;
        $accounts = $api->getAccounts();
        if (isset($accounts["balances"])) {
            foreach ($accounts["balances"] as $key => $value) {
                if ($value["asset"] == "USDT")
                    $this->setWalletB(new Wallet("USDT", $value["free"]));
                elseif ($value["asset"] == "CHZ")
                    $this->setWalletA(new Wallet("CHZ", $value["free"]));
            }
            return 1;
        } else {
            return 0;
        }
    }

    public function sell($price, $fee = 0.00075) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $order = $this->api->createOrder($this->pairs, "sell", round($walletA->getFunds()), $price);
        if (isset($order["orderId"])) {
            $orderBis = $this->api->getOrder($this->pairs, $order["orderId"]);
            while (isset($orderBis["status"]) && $orderBis["status"] != "FILLED") {
                $orderBis = $this->api->getOrder($this->pairs, $order["orderId"]);
                system("clear");
                if (!isset($orderBis) || !isset($orderBis["status"]))
                    return 0;
                echo "ORDER SELL STATUS => ". $orderBis["status"] ."...\n";
                if ($orderBis["status"] == "CANCELED")
                    return 0;
                sleep(1);
            }
            $this->refreshAccounts();
            return $orderBis;
        }
        return 0;
    }

    public function buy($price, $fee = 0.00075) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $order = $this->api->createOrder($this->pairs, "buy", round($walletB->getFunds()), $price);
        if (isset($order["orderId"])) {
            $orderBis = $this->api->getOrder($this->pairs, $order["orderId"]);
            while (isset($orderBis["status"]) && $orderBis["status"] != "FILLED") {
                $orderBis = $this->api->getOrder($this->pairs, $order["orderId"]);
                system("clear");
                if (!isset($orderBis) || !isset($orderBis["status"]))
                    return 0;
                echo "ORDER BUY STATUS => ". $orderBis["status"] ."...\n";
                if ($orderBis["status"] == "CANCELED")
                    return 0;
                sleep(1);
            }
            $this->refreshAccounts();
            return $orderBis;
        }
        return 0;
    }

    public function mobileAverage($period = 7, $pos = 0) {
        $sum = 0;
        for($i = $pos; $i < ($pos + $period); $i++) {
            $candle = $this->getCandle($i);
            $sum += $candle[4];
        }
        return round($sum/$period, 5);
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
        $ma7 = $this->mobileAverage($pos + 5, 7);
        $ma25 = $this->mobileAverage($pos + 5, 25);
        $ma99 = $this->mobileAverage($pos + 5, 99);
        $rsi = $this->getRSI(15, $pos);
        $stop = $currentPrice - ($currentPrice * 0.08);
        if ($this->stopLoss < $stop)
            $this->stopLoss = $stop;

        if (($currentPrice > $ma7 && $ma7 > $ma25 && $ma25 > $ma99) &&
            $currentPrice > $this->mobileAverage($pos, 7) && $currentPrice > $this->mobileAverage($pos, 99)) {
            $this->signal = "buy";
        } else {
            $this->signal = "none";
        }

        if ($this->signal == "buy" && $rsi > 30 && $this->getWalletB()->getFunds() > 10) {
            $this->buy($currentPrice);
            $this->stopLoss = $currentPrice - ($currentPrice * 0.08);
            $this->takeProfit = $currentPrice + ($currentPrice * 0.1);
        }

        if (($this->stopLoss >= $currentPrice || $this->takeProfit <= $currentPrice)) {
            if ($this->getWalletA()->getFunds() > 10) {
                $this->sell($currentPrice);
                $this->signal = "none";
            }
        }
    }
}