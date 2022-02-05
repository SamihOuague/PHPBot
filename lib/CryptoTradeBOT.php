<?php
require_once("Wallet.php");

class CryptoTradeBOT {
    protected $candles;
    protected $walletA;
    protected $walletB;

    public function __construct() {
        $this->setCandles(json_decode(file_get_contents("dataset.json")));
        $this->walletA = new Wallet("ETH", "1.0");
        $this->walletB = new Wallet("BTC", "0.00000");
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

    public function sell($price) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        if ($walletA->getFunds() > 0)
            $this->setWalletB($walletA->sellAll($price, $walletB));
    }

    public function buy($price) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        if ($walletB->getFunds() > 0)
            $this->setWalletA($walletB->buyAll($price, $walletA));
    }

    public function simulateStrategie($period = 14) {
        $candles = $this->getCandles();
        for($i = ($period + 1); $i < count($candles); $i++) {
            $rsi = $this->getRsi($period, $i);
            if ($rsi > 70 && $this->getWalletA()->getFunds() != 0) {
                $this->sell($candles[$i][4]);
                echo "SELL => ". $this->getWalletB()->getFunds()."\n";
                //usleep(5000 * 100);
            } elseif ($rsi < 30 && $this->getWalletB()->getFunds() != 0) {
                $this->buy($candles[$i][4]);
                echo "BUY => ". $this->getWalletA()->getFunds()."\n";
                //usleep(5000 * 100);
            }
        }
        $this->buy($candles[count($candles) - 1][4]);
        return $this->getWalletA()->getFunds();
    }
}