<?php
require_once("./lib/CryptoTradeAPI.php");
require_once("./lib/Wallet.php");
function getRSI($candles, $pos = 0) {
    $avgHarray = [];
    $avgBarray = [];
    $period = 14;
    $pos = $pos - $period;
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


$api = new CryptoTradeAPI();
$start = date("Y-m-d\TH:i:s", (time() - (60 * 60 * 72)));
$candles = $api->getCandlesFrom("LTC-BTC", $start, date("Y-m-d\TH:i:s", time()), 900);
$start2 = date("Y-m-d\TH:i:s", (time() - (60 * 60 * 144)));
$candles = array_merge($candles, $api->getCandlesFrom("LTC-BTC", $start2, $start, 900));
$start3 = date("Y-m-d\TH:i:s", (time() - (60 * 60 * 216)));
$candles = array_merge($candles, $api->getCandlesFrom("LTC-BTC", $start3, $start2, 900));
$start4 = date("Y-m-d\TH:i:s", (time() - (60 * 60 * 288)));
$candles = array_merge($candles, $api->getCandlesFrom("LTC-BTC", $start4, $start3, 900));
$start5 = date("Y-m-d\TH:i:s", (time() - (60 * 60 * 360)));
$candles = array_merge($candles, $api->getCandlesFrom("LTC-BTC", $start5, $start4, 900));
$start6 = date("Y-m-d\TH:i:s", (time() - (60 * 60 * 432)));
$candles = array_merge($candles, $api->getCandlesFrom("LTC-BTC", $start6, $start5, 900));
$start7 = date("Y-m-d\TH:i:s", (time() - (60 * 60 * 504)));
$candles = array_merge($candles, $api->getCandlesFrom("LTC-BTC", $start7, $start6, 900));

$lastPos = count($candles) - 2;
$walletA = new Wallet("LTC", 0);
$walletB = new Wallet("BTC", 0.0024);

$lastFunds = $walletA->getFunds();

$position = "none";


//while ($lastPos >= 14) {
//    $rsi = getRSI($candles, $lastPos);
//    if ($rsi <= 30) {
//        $position = "sell";
//    } elseif ($rsi >= 70) {
//        $position = "buy";
//    }
//    if ($rsi > 30 && $rsi < 70) {
//        if ($position == "sell" && round($walletA->getFunds(), 5) > 0) {
//            $position = "none";
//            $walletB->setFunds($walletA->getFunds() * $candles[$lastPos][3]);
//            $walletA->setFunds(0);
//            echo date("Y-m-d H:i:s", $candles[$lastPos][0]) ." ";
//            echo "{". $rsi ."} ";
//            echo "(price: ". $candles[$lastPos][3].") ";
//            echo $walletB->getFunds()." BTC\n";
////            sleep(1);
//        } elseif ($position == "buy" && round($walletB->getFunds(), 5) > 0) {
//            $position = "none";
//            $walletA->setFunds($walletB->getFunds() / $candles[$lastPos][3]);
//            $walletB->setFunds(0);
//            echo date("Y-m-d H:i:s", $candles[$lastPos][0]) ." ";
//            echo "{". $rsi ."} ";
//            echo "(price: ". $candles[$lastPos][3].") ";
//            echo $walletA->getFunds()." LTC\n";
////            sleep(1);
//        }
//    }
//    $lastPos--;
//}
//if (round($walletA->getFunds(), 5) == 0) {
//    $walletA->setFunds($walletB->getFunds() / $candles[$lastPos][3]);
//    $walletB->setFunds(0);
//}

echo "LTC => ". $walletA->getFunds()."\n";
echo "BTC => ". $walletB->getFunds()."\n";