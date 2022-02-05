<?php

class CryptoTradeBOT {
    protected $candles;
    protected $walletA;
    protected $walletB;

    public function __construct() {
        $this->candles = json_decode(file_get_contents("dataset.json"));
    }

    public function getRSI($period, $pos) {
        $candles = $this->getCandles();
        $avgHarray = [];
        $avgBarray = [];
        for ($i = $pos - $period; $i < $pos; $i++) {
            $ystrD = (float) $candles[$i - 1][4];
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
        $rsi = 100 - ((100/(1 + ($avgH/$avgB))));
        
        return $rsi;
    }

    public function getCandles() {
        return $this->candles;
    }

    public function getCandle(int $index) {
        return $this->candles[$index];
    }

    public function simulateStrategie() {
        $period = 14;
        for($i = ($period + 1); $i < count($this->getCandles()); $i++) {
            $rsi = $this->getRsi($period, $i);
            if ($rsi > 70) {
                echo "Sell : ". $rsi ."\n";
                usleep(5000 * 100);
            } elseif ($rsi < 30) {
                echo "Buy : ". $rsi ."\n";
                usleep(5000 * 100);
            }
        }
    }
}