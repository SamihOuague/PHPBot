<?php
require_once("autoload.php");

class Simulation {
    protected $candles;
    protected $walletA;
    protected $walletB;
    public $signal;
    public $lastFunds;
    public $stopLoss;
    public $takeProfit;
    public $lastLoss;
    public $wins = 0;
    public $losses = 0;

    public function __construct($dataset, $funds = 100, $sltp = 0.01) {
        $this->setCandles($dataset);
        $this->setWalletA(new Wallet("CHZ", 0));
        $this->setWalletB(new Wallet("USDT", $funds));
        $this->lastFunds = $this->getWalletB()->getFunds();
        $this->available = true;
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
        if (round($walletA->getFunds(), 2) > 0) {
            $size = $walletA->getFunds() * $price;
            $fees = $size * $fee;
            $walletB->setFunds($size - $fees);
            $walletA->setFunds(0);
            $this->setWalletA($walletA);
            $this->setWalletB($walletB);
            return 1;
        }
        return 0;
    }

    public function buy($price, $fee = 0.00075) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        if (round($walletB->getFunds(), 2) > 0) {
            $size = $walletB->getFunds() / $price;
            $fees = $size * $fee;
            $walletA->setFunds($size - $fees);
            $this->lastFunds = $walletB->getFunds();
            $walletB->setFunds(0);
            $this->setWalletA($walletA);
            $this->setWalletB($walletB);
            return 1;
        }
        return 0;
    }

    public function getRatio($candleA, $candleB) {
        return round((($candleA - $candleB) / $candleB) * 100, 2);
    }

    public function isHammer($pos) {
        $candle = $this->getCandle($pos);
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

    public function winOrLoss($pos = 0, $curr = "USDT") {
        $lastFunds = $this->lastFunds;
        $currentFunds = $this->getWalletB()->getFunds();
        $diff = $currentFunds - $lastFunds;
        if ($lastFunds > 0) {
            echo date("Y-m-d H:i:s", $this->getCandle($pos)[0]/1000)."\n";
            
            if ($diff >= 0) {
                $this->wins++;
                echo "\e[32m+". $diff ." ". $curr ."\n\e[39m";
            } else {
                $this->losses++;
                $this->lastLoss = $this->getCandle($pos)[0]/1000;
                echo "\e[31m".$diff." ". $curr."\n\e[39m";
            }
        }
    }
}