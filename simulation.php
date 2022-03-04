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
        $candleA = $this->getCandle($pos + 1);
        $candleB = $this->getCandle($pos + 2);
        if (($candleA[1] >= $candleB[1]
            && $candleA[4] <= $candleB[4] || $candleA[1] <= $candleB[4]
            && $candleA[4] >= $candleB[1]) || $this->isHammer($pos)) {
            return true;
        } else {
            return false;
        }
    }

    public function makeDecision($currentPrice, $pos = 0, $rsiM30 = 50, $maM30 = 0) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $stop = $currentPrice - ($currentPrice * 0.015);
        $rsi = $this->getRSI(14, $pos);
        //if ($stop > $this->stopLoss) {
        //    $this->stopLoss = $stop;
        //}
        if ($this->getWalletA()->getFunds() == 0 && $rsiM30 >= 60) {
            if ($rsi < 27 && $this->priceAction($pos) && $maM30 >= $currentPrice) {
                $this->stopLoss = $currentPrice - ($currentPrice * 0.01);
                $this->takeProfit = $currentPrice + ($currentPrice * 0.02);
                $this->buy($currentPrice);
            }
        }

        if ($this->getWalletA()->getFunds() > 0) {
            if ($currentPrice <= $this->stopLoss) {
                $this->sell($this->stopLoss);
                //system("clear");
                $this->winOrLoss($pos, $rsiM30);
                //echo "WIN RATE => ". round($this->wins / ($this->wins + $this->losses) * 100)."%\n";
                //echo "USDT => ". round($this->getWalletB()->getFunds(), 2)."$\n";
                //echo ($this->wins + $this->losses)."\n";
                //usleep(100000);
            } elseif ($currentPrice >= $this->takeProfit) {
                $this->sell($this->takeProfit);
                //system("clear");
                $this->winOrLoss($pos, $rsiM30);
                //echo "WIN RATE => ". round($this->wins / ($this->wins + $this->losses) * 100)."%\n";
                //echo "USDT => ". round($this->getWalletB()->getFunds(), 2)."$\n";
                //echo ($this->wins + $this->losses)."\n";
                //usleep(100000);
            }
        }
    }
}