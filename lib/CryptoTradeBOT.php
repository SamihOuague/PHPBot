<?php
require_once("Wallet.php");

class CryptoTradeBOT {
    protected $candles;
    protected $walletA;
    protected $walletB;
    protected $lastFunds;
    protected $wins;
    protected $losses;
    protected $available = true;

    public function __construct($funds, $dataset) {
        $this->setCandles($dataset);
        $this->walletA = new Wallet("ETH", $funds);
        $this->walletB = new Wallet("BTC", "0.00000");
        $this->lastFunds = "1.0";
        $this->wins = 0;
        $this->losses = 0;
    }

    public function getWins() {
        return $this->wins;
    }

    public function getLosses() {
        return $this->losses;
    }

    public function getRSI($period, $pos) {
        $candles = $this->getCandles();
        $avgHarray = [];
        $avgBarray = [];
        for ($i = $pos + $period; $i > $pos; $i--) {
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
        $rsi = 50;
        if ($avgB != 0 && $avgH != 0)
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
        $this->available = true;
        $this->lastFunds = $walletA->getFunds();
        if ($walletA->getFunds() > 0) {
            $this->setWalletB($walletA->sellAll($price, $walletB));
        }
    }

    public function buy($price) {
        $walletA = $this->getWalletA();
        $walletB = $this->getWalletB();
        if ($walletB->getFunds() > 0)
            $this->setWalletA($walletB->buyAll($price, $walletA));
        if ($this->lastFunds < $walletA->getFunds()) {
            echo "\033[32mWIN : +". ($walletA->getFunds() - $this->lastFunds)."\n\033[0m";
            $this->wins++;
        }
        else {
            echo "\033[31mLOSS : -". ($this->lastFunds - $walletA->getFunds())."\n\033[0m";
            $this->losses++;
        }
    }

    public function makeDecision($currentPrice, $perteMax = 3.5, $gainMax = 5.9, $period = 14) {
        $candles = $this->getCandles();
        $buyIt = false;
        $rsi = $this->getRsi($period, 0);
        if ($this->getWalletB()->getFunds() != 0) {
            $btcToETH = ($this->getWalletB()->getFunds() / $currentPrice);
            $decalage = $btcToETH - $this->lastFunds;
            if ($decalage < 0) {
                $decalage *= -1;
                $perte = round(($decalage / $this->lastFunds) * 100, 2);
                if ($perte > $perteMax) {
                    $buyIt = true;
                }
            } elseif ($decalage > 0) {
                $gain = round(($decalage / $this->lastFunds) * 100, 2);
                if ($gain >= $gainMax) {
                    $buyIt = true;
                }
            }
        }
        if ($rsi > 70 || $buyIt) {
            if ($this->getWalletB()->getFunds() != 0 && $this->available) {
                $this->available = false;
                $this->buy($currentPrice);
            }
        } elseif ($rsi < 30) {
            if ($this->getWalletA()->getFunds() != 0) {
                $this->sell($currentPrice);
            }
        }
    }

    public function simulateStrategy($period = 14, $perteMax = 3.5, $gainMax = 5.9) {
        $candles = $this->getCandles();
        for($i = count($candles) - ($period + 1); $i >= 0; $i--) {
            $buyIt = false;
            $rsi = $this->getRsi($period, $i);
            //echo $rsi."\n";
            if ($this->getWalletB()->getFunds() != 0) {
                $btcToETH = ($this->getWalletB()->getFunds() / $candles[$i][4]);
                $decalage = $btcToETH - $this->lastFunds;
                if ($decalage < 0) {
                    $decalage *= -1;
                    $perte = round(($decalage / $this->lastFunds) * 100, 2);
                    if ($perte > $perteMax) {
                        $buyIt = true;
                    }
                } elseif ($decalage > 0) {
                    $gain = round(($decalage / $this->lastFunds) * 100, 2);
                    if ($gain >= $gainMax) {
                        $buyIt = true;
                    }
                }
            }
            if ($rsi > 70 || $buyIt) {
                if ($this->getWalletB()->getFunds() != 0 && $this->available) {
                    //$this->sell($candles[$i][4]);
                    $hour = (int) date("H", $candles[$i][0]);
                    $date = date("Y-m-d H:i:s", $candles[$i][0]);
                    if ($hour >= 0 && $hour <= 23) {
                        echo $date." => ";
                        $this->available = false;
                        $this->buy($candles[$i][4]);
                        usleep(5000 * 100);
                    }
                }
            } elseif ($rsi < 30) {
                if ($this->getWalletA()->getFunds() != 0) {
                    $this->sell($candles[$i][4]);
                }
            }
        }
        echo $date." => ";
        $this->buy($candles[count($candles) - 1][4]);
        return $this->getWalletA()->getFunds();
    }
}