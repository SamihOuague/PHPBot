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
    public $risk = 1;
    public $lastFunds;
    public $wins = 0;
    public $losses = 0;

    public function __construct($dataset, $pairs = "CHZUSDT") {
        $this->setCandles($dataset);
        $this->api = new BinanceTradeAPI();
        $this->signal = "none";
        $this->stopLoss = round($this->getCandle(0)[4] - ($this->getCandle(0)[4] * 0.01), 4);
        $this->takeProfit = round($this->getCandle(0)[4] + ($this->getCandle(0)[4] * 0.01), 4);
        $this->pairs = $pairs;
        $this->refreshAccounts();
        $this->lastFunds = $this->getWalletB()->getFunds();
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
            $this->lastFunds = $this->getWalletB()->getFunds();
            $this->refreshAccounts();
            return $orderBis;
        }
        return 0;
    }

    public function winOrLoss($curr = "USDT") {
        $lastFunds = $this->lastFunds;
        $currentFunds = $this->getWalletB()->getFunds();
        $diff = $currentFunds - $lastFunds;
        if ($lastFunds > 0) {
            if ($diff >= 0) {
                $this->wins++;
                $this->risk = 1;
                echo "\e[32m+". $diff ." ". $curr ."\n\e[39m";
            } else {
                $this->losses++;
                $size = $this->risk * 2;
                if ($size <= 1)
                    $this->risk = $size;
                echo "\e[31m".$diff." ". $curr."\n\e[39m";
            }
        }
    }

    public function priceAction($pos) {
        $candleA = $this->getCandle($pos);
        $candleB = $this->getCandle($pos + 1);
        if (($candleA[1] >= $candleB[1]
            && $candleA[4] <= $candleB[4] || $candleA[1] <= $candleB[4]
            && $candleA[4] >= $candleB[1]) || $this->isHammer($pos)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function buy($price, $fee = 0.00075) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $order = $this->api->createOrder($this->pairs, "buy", round($walletB->getFunds() * $this->risk), $price);
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
            $this->winOrLoss();
            return $orderBis;
        }
        return 0;
    }

    public function mobileAverage($pos = 0, $period = 7) {
        $sum = 0;
        for($i = $pos; $i < ($pos + $period); $i++) {
            $candle = $this->getCandle($i);
            $sum += $candle[4];
        }
        return round($sum/$period, 4);
    }

    public function makeDecision($currentPrice, $pos = 0) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $mA = $this->mobileAverage($pos, 50);
        $rsi = $this->getRSI(15, $pos);
        
        if (round($this->getWalletA()->getFunds()) < 10) {
            if ($rsi < 15 && $this->priceAction($pos) && $mA > $currentPrice) {
                $this->stopLoss = $currentPrice - ($currentPrice * 0.01);
                $this->takeProfit = $currentPrice + ($currentPrice * 0.02);
                $this->buy($currentPrice);
            }
        }

        if ($this->getWalletA()->getFunds() > 10) {
            if ($currentPrice <= $this->stopLoss) {
                $this->sell($currentPrice);
            } elseif ($currentPrice >= $this->takeProfit) {
                $this->sell($currentPrice);
            }

        }
    }
}