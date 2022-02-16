<?php
require_once("lib/Binance/BinanceTradeAPI.php");
require_once("lib/Wallet.php");

function getRSI($candles, $pos = 0) {
    $avgHarray = [];
    $avgBarray = [];
    $period = 14;
    for ($i = $pos + $period; $i > $pos; $i--) {
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
    $rsi = 0;
    if ($avgB != 0 && $avgH != 0)
        $rsi = 100 - ((100/(1 + ($avgH/$avgB))));
    return $rsi;
}

function isHammer($candle) {
    if ($candle[4] > $candle[3]) {
        $diffCand = $candle[4] - $candle[3];
        $diffAvg = $candle[2] - $candle[4];
        if ($diffAvg / $diffCand > 1)
            return true;
    } else {
        $diffCand = $candle[3] - $candle[4];
        $diffAvg = $candle[2] - $candle[3];
        $ratio = 0;
        if ($diffCand != 0 && $diffAvg != 0)
            $ratio = $diffAvg / $diffCand;
        if ($ratio > 1)
            return true;
    }
    return false;
}

function upOrDown($candles, $pos) {
    if ($candles[$pos][3] > $candles[$pos + 1][3] && $candles[$pos + 1][3] > $candles[$pos + 2][3]) {
        return "UP";
    } elseif ($candles[$pos][3] < $candles[$pos + 1][3] && $candles[$pos + 1][3] < $candles[$pos + 2][3]) {
        return "DOWN";
    } else {
        return false;
    }
}

$api = new BinanceTradeAPI();
$candles = json_decode(file_get_contents("dataset.json"));

$walletA = new Wallet("LTC", 1);
$walletB = new Wallet("BNB", 0);


$position = "none";

$lastPos = count($candles) - 16;

$wins = 0;
$losses = 0;
$lastRsi;
$lastFunds = $walletA->getFunds();
while ($lastPos >= 0) {
    $rsi = getRSI($candles, $lastPos);
    $h = (int) date("H", $candles[$lastPos][0]/1000);
    $price = $candles[$lastPos][3];
    if (isset($lastRsi)) {
        $rsiTrend = ($rsi - $lastRsi) > 0;
        if ($rsi <= 30 && isHammer($candles[$lastPos])) {
            $position = "buy";
        } elseif ($rsi >= 70 && isHammer($candles[$lastPos])) {
            $position = "sell";
        }
    }

    if (round($walletB->getFunds(), 5) > 0 && $lastFunds != 0) {
        $ltcPot = $walletB->getFunds() / $price;
        $diff = $ltcPot - $lastFunds - ($ltcPot * 0.00075);
        $ratio = (($diff / $lastFunds) * 100);
        if ($ratio < -1.5 || $ratio > 0.35) {
            $position = "buy";
        }
    }
    
    if (($position == "buy" && upOrDown($candles, $lastPos) == "UP") || ($position == "sell" && upOrDown($candles, $lastPos) == "DOWN")) {
        if ($position == "sell" && round($walletA->getFunds(), 5) > 0) {
            $position = "none";
            $nFunds = $walletA->getFunds() * $price;
            $fees = $nFunds * 0.00075;
            $lastFunds = $walletA->getFunds();
            $walletB->setFunds($nFunds - $fees);
            $walletA->setFunds(0);
            echo "\e[34mDATE => ". date("Y-m-d H:i:s", ($candles[$lastPos][0] / 1000)) ."\n";
            //usleep(50000);
        } elseif ($position == "buy" && round($walletB->getFunds(), 5) > 0) {
            $position = "none";
            $nFunds = $walletB->getFunds() / $price;
            $fees = $nFunds * 0.00075;
            $lastFundsBTC = $walletB->getFunds();
            $walletA->setFunds($nFunds - $fees);
            $walletB->setFunds(0);
            if ($walletA->getFunds() > $lastFunds) {
                echo "\e[32mWIN => ". ($walletA->getFunds() - $lastFunds) ."\n";
                $wins++;
            } else {
                echo "\e[31mLOSS => ". ($walletA->getFunds() - $lastFunds) ."\n";
                $losses++;
            }
            echo "\e[35mDATE => ". date("Y-m-d H:i:s", ($candles[$lastPos][0] / 1000)) ."\n";
            //usleep(50000);
        }
    }
    $lastRsi = $rsi;
    $lastPos--;
}
if (round($walletA->getFunds(), 5) == 0) {
    $walletA->setFunds($walletB->getFunds() / $candles[$lastPos + 1][3]);
    $walletB->setFunds(0);
}

echo "\e[39mLTC => ". round($walletA->getFunds(), 4)."\n";
echo "BNB => ". round($walletB->getFunds(), 4)."\n";
echo "TRADE GAGNANT => ". $wins."\n";
echo "TRADE PERDANT => ". $losses."\n";
echo "\e[34mTAUX DE REUSSITES : ". round(($wins / ($losses + $wins)) * 100, 2) ."%\n";