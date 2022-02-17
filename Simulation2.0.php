<?php
require_once("lib/Binance/BinanceTradeAPI.php");
require_once("lib/Wallet.php");
//echo "\e[39mLTC => ". round($walletA->getFunds(), 4)."\n";
//echo "BNB => ". round($walletB->getFunds(), 4)."\n";
//echo "TRADE GAGNANT => ". $wins."\n";
//echo "TRADE PERDANT => ". $losses."\n";
//echo "\e[34mTAUX DE REUSSITES : ". round(($wins / ($losses + $wins)) * 100, 2) ."%\n";

class Simulation {
    protected $candles;
    protected $walletA;
    protected $walletB;
    public $signal;
    public $lastFunds;
    public $wins = 0;
    public $losses = 0;

    public function __construct($walletA, $walletB, $dataset) {
        $this->setCandles($dataset);
        $this->setWalletA($walletA);
        $this->setWalletB($walletB);
        $this->signal = "none";
        $this->lastFunds = $walletA->getFunds();
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
            $walletB->setFunds(0);
            $this->setWalletA($walletA);
            $this->setWalletB($walletB);
            return 1;
        }
        return 0;
    }

    public function makeDecision($currentPrice, $pos = 0) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        $rsi = $this->getRSI(15, $pos + 2);
        $lastFunds = $this->lastFunds;
        $candleB = $this->getCandle($pos + 5);
        if ($rsi >= 67) {
            $this->signal = "sell";
        } elseif ($rsi <= 33) {
            $this->signal = "buy";
        }

        if ($this->signal == "sell" && round($walletA->getFunds(), 2) > 0.05 && round(($currentPrice - $candleB[4]) * 10000, 2) > 15) {
            $this->signal = "none";
            $this->lastFunds = $walletA->getFunds();
            $this->sell($currentPrice);
        } elseif ($this->signal == "buy" && round($walletB->getFunds(), 2) > 0.05 && round(($currentPrice - $candleB[4]) * 10000, 2) < -15) {
            $this->signal = "none";
            $this->buy($currentPrice);
        }
    }
}

$candles = json_decode(file_get_contents("dataset.json"));
$walletA = new Wallet("LTC", 10);
$walletB = new Wallet("BNB", 0);
$lastPos = count($candles) - 20;
$simulation = new Simulation($walletA, $walletB, $candles);
while ($lastPos >= 0) {
    $simulation->makeDecision($candles[$lastPos][4], $lastPos);
    $lastPos--;
}

$simulation->buy($candles[$lastPos + 1][4]);

echo $simulation->getWalletA()->getFunds()."\n";
echo $simulation->getWalletB()->getFunds()."\n";