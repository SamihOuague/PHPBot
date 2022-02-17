<?php
require_once("lib/Binance/BinanceTradeAPI.php");
require_once("lib/Wallet.php");

function getRSI($candles, $pos = 0) {
    $avgHarray = [];
    $avgBarray = [];
    $period = 15;
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
    if ($avgB != 0 && $avgH != 0)
        $rsi = 100 - ((100/(1 + ($avgH/$avgB))));
    else
        $rsi = 100 - (100/2);
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
    if (getRSI($candles, $pos) < getRSI($candles, $pos + 1)
        && getRSI($candles, $pos + 1) > getRSI($candles, $pos + 2)
        && getRSI($candles, $pos + 2) > getRSI($candles, $pos + 3)) {
        return "DOWN";
    } elseif (getRSI($candles, $pos) > getRSI($candles, $pos + 1)
            && getRSI($candles, $pos + 1) < getRSI($candles, $pos + 2)
            && getRSI($candles, $pos + 2) < getRSI($candles, $pos + 3)) {
        return "UP";
    } else {
        return false;
    }
}

$api = new BinanceTradeAPI();
$candles = json_decode(file_get_contents("dataset.json"));
//$candles = $api->getCandles("LTCBNB", "1m");
$walletA = new Wallet("LTC", 1);
$walletB = new Wallet("BNB", 0);


$position = "none";

$lastPos = count($candles) - 20;

$wins = 0;
$losses = 0;
$lastRsi;
$lastFunds = $walletA->getFunds();
$lastFundsBTC = $walletA->getFunds();
$available = true;
while ($lastPos >= 0) {
    $rsi = getRSI($candles, $lastPos + 1);
    $h = (int) date("H", $candles[$lastPos][0]/1000);
    $price = $candles[$lastPos][4];
    $priceB = $candles[$lastPos + 5][4];
    if ($rsi <= 33) {
        $position = "buy";
    } elseif ($rsi >= 67) {
        $position = "sell";
    }

    if ($position == "sell" && round($walletA->getFunds(), 5) > 0 && round(($price - $priceB) * 10000) > 15) {
        $position = "none";
        $nFunds = $walletA->getFunds() * $price;
        $fees = $nFunds * 0.00075;
        $lastFunds = $walletA->getFunds();
        $walletB->setFunds($nFunds - $fees);
        $walletA->setFunds(0);
    } elseif ($position == "buy" && round($walletB->getFunds(), 5) > 0 && round(($price - $priceB) * 10000) < -15) {
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
    
    $lastRsi = $rsi;
    $lastPos--;
}
if (round($walletA->getFunds(), 3) == 0) {
    $walletA->setFunds($walletB->getFunds() / $candles[$lastPos + 1][3]);
    $walletB->setFunds(0);
}

echo "\e[39mLTC => ". round($walletA->getFunds(), 4)."\n";
echo "BNB => ". round($walletB->getFunds(), 4)."\n";
echo "TRADE GAGNANT => ". $wins."\n";
echo "TRADE PERDANT => ". $losses."\n";
echo "\e[34mTAUX DE REUSSITES : ". round(($wins / ($losses + $wins)) * 100, 2) ."%\n";