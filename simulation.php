<?php
require_once("lib/autoload.php");

class Strategy extends Simulation {
    public $available = true;
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
        return round($sum/$period, 5);
    }

    public function makeDecision($currentPrice, $pos = 0) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $limit = $this->avgCandles($pos);
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