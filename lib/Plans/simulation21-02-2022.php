<?php
require_once("lib/autoload.php");

class Strategy extends Simulation {
    public $available = true;
    public function __construct($walletA, $walletB, $candles) {
        parent::__construct($walletA, $walletB, $candles, 0.01);
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

    public function makeDecision($currentPrice, $avg = 0.03, $pos = 0) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $rsi = $this->getRSI(15, $pos);
        $nStopA = $currentPrice - ($currentPrice * $avg);
        $nLimitB = $currentPrice + ($currentPrice * $avg);
        if ($this->stopLoss < $nStopA) {
            $this->stopLoss = $nStopA;
        }
        if ($this->takeProfit > $nLimitB){
            $this->takeProfit = $nLimitB;
        }

        if ($currentPrice <= $this->stopLoss && round($walletA->getFunds(), 2) > 0.05) {
            $this->signal = "none";
            $this->takeProfit = $currentPrice + ($currentPrice * $avg);
            $this->sell($currentPrice);
            system("clear");
            $this->winOrLoss($walletB->getFunds(), $this->lastFundsBNB, $pos, "USDT");
            echo "USDT => ". round($walletB->getFunds(), 2)."\n";
            echo "NOMBRE DE TRADE => ". ($this->wins + $this->losses)."\n";
            echo "TAUX DE REUSSITE => ". round($this->wins / ($this->wins + $this->losses) * 100, 2)."%\n";
            usleep(50000);
        }
        if ($currentPrice >= $this->takeProfit && round($walletB->getFunds(), 2) > 0.05) {
            $this->signal = "none";
            $this->stopLoss = $currentPrice - ($currentPrice * $avg);
            $this->buy($currentPrice);
        }
    }
}