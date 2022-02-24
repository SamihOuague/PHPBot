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

    public function getScore($pos, $currentPrice) {
        $ma7 = $this->mobileAverage($pos, 7);
        $ma25 = $this->mobileAverage($pos, 25);
        $ma99 = $this->mobileAverage($pos, 99);
        $rsi = $this->getRSI(15, $pos);
        $score = 0;

        
        if ($currentPrice < $ma7 && $rsi < 30)
            $score++;


        return $score;
    }

    public function makeDecision($currentPrice, $pos = 0) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $limit = $this->avgCandles($pos);
        $stop = $currentPrice - ($currentPrice * 0.03);
        $score = $this->getScore($pos, $currentPrice);
        $ma7 = $this->mobileAverage($pos, 7);
        $rsi = $this->getRSI(15, $pos);
        if ($this->stopLoss < $stop)
            $this->stopLoss = $stop;

        

        if ($this->stopLoss >= $this->getCandle($pos)[3] && $this->getWalletA()->getFunds() > 0) {
            $this->sell($this->stopLoss);
            system("clear");
            $this->winOrLoss($pos);
            echo "USDT => ". round($this->getWalletB()->getFunds(), 2)."\n";
            echo "NOMBRE DE TRADE => ". ($this->wins + $this->losses)."\n";
            echo "TAUX DE REUSSITE => ". round($this->wins / ($this->wins + $this->losses) * 100, 2)."%\n";
            usleep(50000);
        }

        if ($this->takeProfit <= $this->getCandle($pos)[2] && round($this->getWalletA()->getFunds(), 2) > 0) {
            $this->sell($this->takeProfit);
            system("clear");
            $this->winOrLoss($pos);
            echo "USDT => ". round($this->getWalletB()->getFunds(), 2)."\n";
            echo "NOMBRE DE TRADE => ". ($this->wins + $this->losses)."\n";
            echo "TAUX DE REUSSITE => ". round($this->wins / ($this->wins + $this->losses) * 100, 2)."%\n";
            usleep(50000);
        }

        if ($currentPrice < $ma7 && $rsi < 30 && $this->getWalletA()->getFunds() == 0) {
            //var_dump(date("Y-m-d H:i:s", $this->getCandle($pos)[0]/1000));
            $this->buy($currentPrice);
            $this->stopLoss = $currentPrice - ($currentPrice * 0.025);
            $this->takeProfit = $currentPrice + ($currentPrice * 0.045);
        }
    }
}