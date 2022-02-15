<?php
require_once("BinanceTradeAPI.php");
require_once("../Wallet.php");

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
    $rsi = 50;
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

function isHanged($candle) {
    if ($candle[4] > $candle[3]) {
        $diffCand = $candle[4] - $candle[3];
        $diffAvg = $candle[3] - $candle[1];
        if ($diffAvg / $diffCand > 1)
            return true;
    } else {
        $diffCand = $candle[3] - $candle[4];
        $diffAvg = $candle[4] - $candle[1];
        $ratio = 0;
        if ($diffCand != 0 && $diffAvg != 0)
            $ratio = $diffAvg / $diffCand;
        if ($ratio > 3)
            return true;
    }
    return false;
}

function isEngulfing($candles, $pos) {
    $top2 = ($candles[$pos + 1][4] > $candles[$pos + 1][3]) ? $candles[$pos + 1][4] : $candles[$pos + 1][3];
    $top = ($candles[$pos][4] > $candles[$pos][3]) ? $candles[$pos][4] : $candles[$pos][3];
    $down2 = ($candles[$pos + 1][4] < $candles[$pos + 1][3]) ? $candles[$pos + 1][4] : $candles[$pos + 1][3];
    $down = ($candles[$pos][4] < $candles[$pos][3]) ? $candles[$pos][4] : $candles[$pos][3];
    if ($top2 > $top && $down2 > $down) {
        //var_dump(date("Y-m-d H:i:s", $candles[$pos][0]));
        return true;
    } else {
        return false;
    }
}

$api = new BinanceTradeAPI();
$candles = array_reverse($api->getCandles("LTCBNB", "15m"));

$walletA = new Wallet("LTC", 0.080);
$walletB = new Wallet("BNB", 0);


$position = "none";

$lastPos = count($candles) - 16;
$lastFundsLTC = $walletA->getFunds();
$lastFundsBTC = $walletA->getFunds() * $candles[$lastPos][3];
$wins = 0;
$losses = 0;
while ($lastPos >= 0) {
    $rsi = getRSI($candles, $lastPos);
    //echo "RSI => ". $rsi ."\n";
    //echo "PRICE ENTER => ". $candles[$lastPos][3] ."\n\n";
    if ($rsi <= 30) {
        $position = "buy";
    } elseif ($rsi >= 70) {
        $position = "sell";
    }
    if (round($walletB->getFunds(), 5) > 0) {
        $ltcPotLow = $walletB->getFunds() / $candles[$lastPos][1];
        $ltcPotHigh = $walletB->getFunds() / $candles[$lastPos][2];
        $diffLow = $ltcPotLow - $lastFundsLTC - ($ltcPotLow * 0.0005);
        $diffHigh = $ltcPotHigh - $lastFundsLTC - ($ltcPotHigh * 0.0005);
        if ($lastFundsLTC > 0) {
            $ratioLow = (($diffLow / $lastFundsLTC) * 100);
            $ratioHigh = (($diffHigh / $lastFundsLTC) * 100);
            if (($ratioLow < -3 || $ratioHigh < -3)) {
                $position = "buy";
            }
        }
    }
    //if (round($walletA->getFunds(), 5) > 0) {
    //    $btcPot = $walletA->getFunds() * $candles[$lastPos][3];
    //    $fees = $btcPot * 0.0035;
    //    $diff = $btcPot - $lastFundsBTC - $fees;
    //    $ratioGain = (($diff / $lastFundsBTC) * 100);
    //    if ($ratioGain < -2) {
    //        $position = "sell";
    //    } elseif ($ratioGain > 5) {
    //        $position = "buy";
    //    }
    //}
    if ($position == "sell" && round($walletA->getFunds(), 5) > 0) {
        $position = "none";
        $nFunds = $walletA->getFunds() * $candles[$lastPos][3];
        $fees = $nFunds * 0.0005;
        $lastFundsLTC = $walletA->getFunds();
        $walletB->setFunds($nFunds - $fees);
        $walletA->setFunds(0);
        usleep(50000);
    } elseif ($position == "buy" && round($walletB->getFunds(), 5) > 0) {
        $position = "none";
        $nFunds = $walletB->getFunds() / $candles[$lastPos][3];
        $fees = $nFunds * 0.0005;
        $lastFundsBTC = $walletB->getFunds();
        $walletA->setFunds($nFunds - $fees);
        $walletB->setFunds(0);
        if ($walletA->getFunds() > $lastFundsLTC) {
            echo "\e[32mWIN => ". ($walletA->getFunds() - $lastFundsLTC) ."\n";
            $wins++;
        } else {
            echo "\e[31mLOSS => ". ($walletA->getFunds() - $lastFundsLTC) ."\n";
            $losses++;
        }
        echo "\e[35mDATE => ". date("Y-m-d H:i:s", ($candles[$lastPos][0] / 1000)) ."\n";
        usleep(50000);
    }
    $lastPos--;
}
if (round($walletA->getFunds(), 5) == 0) {
    $walletA->setFunds($walletB->getFunds() / $candles[$lastPos + 1][3]);
    $walletB->setFunds(0);
}

echo "\e[39mLTC => ". $walletA->getFunds()."\n";
echo "BNB => ". $walletB->getFunds()."\n";
echo "TRADE GAGNANT => ". $wins."\n";
echo "TRADE PERDANT => ". $losses."\n";
echo "\e[34mTAUX DE REUSSITES : ". round(($wins / ($losses + $wins)) * 100, 2) ."%\n";