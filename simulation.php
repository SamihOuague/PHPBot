<?php
require_once("lib/autoload.php");

class Strategy extends Simulation {
    public $isLoss = false;
    public function __construct($candles, $funds = 100) {
        parent::__construct($candles, $funds);
    }

    public function avgCandles($pos) {
        $sum = 0;
        for($i = $pos; $i < ($pos + 7); $i++) {
            $candle = $this->getCandle($i);
            $sum += round(($candle[2] - $candle[3]), 4);
        }
        return round($sum/7, 4);
    }

    public function mobileAverage($pos, $period = 7) {
        $sum = 0;
        for($i = $pos; $i < ($pos + $period); $i++) {
            $candle = $this->getCandle($i);
            $sum += $candle[4];
        }
        return round($sum/$period, 4);
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

    public function makeDecision($currentPrice, $pos = 0) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $stop = $currentPrice - ($currentPrice * 0.01);
        $mA = $this->mobileAverage($pos, 50);
        $rsi = $this->getRSI(9, $pos);
        //if ($this->stopLoss < $stop)
        //    $this->stopLoss = $stop;
        if ($this->getWalletA()->getFunds() == 0) {
            if ($rsi < 12 && $this->priceAction($pos) && $mA > $currentPrice) {
                $this->stopLoss = $currentPrice - ($currentPrice * 0.01);
                $this->takeProfit = $currentPrice + ($currentPrice * 0.02);
                $this->buy($currentPrice);
            }
        }

        if ($this->getWalletA()->getFunds() > 0) {
            if ($currentPrice <= $this->stopLoss) {
                $this->sell($this->stopLoss);
                $this->winOrLoss($pos);
                //usleep(100000);
            } elseif ($currentPrice >= $this->takeProfit) {
                $this->sell($this->takeProfit);
                $this->winOrLoss($pos);
                //usleep(100000);
            }

        }
    }
}