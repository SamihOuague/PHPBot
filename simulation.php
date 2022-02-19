<?php
require_once("lib/autoload.php");

class Strategy extends Simulation {
    public $available = true;
    public function __construct($walletA, $walletB, $candles) {
        parent::__construct($walletA, $walletB, $candles, 0.01);
    }

    function isHanged($candle) {
        if ($candle[4] > $candle[1]) {
            $diffCand = $candle[4] - $candle[1];
            $diffAvg = $candle[1] - $candle[3];
            if ($diffAvg / $diffCand > 1)
                return true;
        } else {
            $diffCand = $candle[1] - $candle[4];
            $diffAvg = $candle[4] - $candle[3];
            $ratio = 0;
            if ($diffCand != 0 && $diffAvg != 0)
                $ratio = $diffAvg / $diffCand;
            if ($ratio > 3)
                return true;
        }
        return false;
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

            if ($currentPrice <= $this->stopLoss && round($walletA->getFunds(), 2) > 0.05) {
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
            if ($this->signal == "buy" && ($rsi > $this->getRSI(15, $pos + 1)) && round($walletB->getFunds(), 2) > 0.05) {
                $this->signal = "none";
                $this->stopLoss = $currentPrice - ($currentPrice * 0.008);
                $this->buy($currentPrice);
                //$this->available = false;
                //$this->winOrLoss($walletA->getFunds(), $this->lastFundsLTC, $pos, "CHZ");
            }
        }
    
}