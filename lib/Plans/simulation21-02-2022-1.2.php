<?php
require_once("lib/autoload.php");

class Strategy extends Simulation {
    public $available = true;
    public function __construct($walletA, $walletB, $candles) {
        parent::__construct($walletA, $walletB, $candles, 0.01);
    }

    public function avgCandles($pos) {
        $sum = 0;
        for($i = $pos; $i < ($pos + 5); $i++) {
            $candle = $this->getCandle($i);
            $sum += round((($candle[2] - $candle[3]) / $candle[1])*100, 2);
        }
        return round($sum/5, 2);
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
        $limit = ($this->avgCandles($pos)/10)/2;
        $rsi = $this->getRSI(15, $pos);
        $rsi2 = $this->getRSI(14, $pos);
        $stop = $currentPrice - ($currentPrice * $limit);
        $candle = $this->getCandle($pos);
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        if ($this->stopLoss < $stop)
            $this->stopLoss = $stop;
            
        if ($currentPrice <= $this->stopLoss && $walletA->getFunds() > 0.05) {
            $this->sell($currentPrice);
            $this->signal = "none";
            system("clear");
            $this->winOrLoss($pos);
            echo "USDT => ". round($this->getWalletB()->getFunds(), 2)."\n";
            echo "NOMBRE DE TRADE => ". ($this->wins + $this->losses)."\n";
            echo "TAUX DE REUSSITE => ". round($this->wins / ($this->wins + $this->losses) * 100, 2)."%\n";
            //usleep(500000);
        }

        if (($rsi < 30 && $rsi2 < 30) && $this->isHammer($candle) && $this->avgCandles($pos) > 1 && $walletB->getFunds() > 0.05) {
            $this->signal = "buy";
        }

        if ($this->signal == "buy" && $rsi > $this->getRSI(15, $pos + 1)) {
            $this->signal = "none";
            $this->stopLoss = $stop;
            $this->buy($currentPrice);
        }
    }
}