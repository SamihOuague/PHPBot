<?php
require_once("lib/Binance/BinanceTradeAPI.php");
require_once("simulation.php");

function mobileAverage($candles, $pos, $period = 7) {
    $sum = 0;
    for($i = $pos; $i < ($pos + $period); $i++) {
        $candle = $candles[$i];
        $sum += $candle[4];
    }
    return round($sum/$period, 3);
}


$api = new BinanceTradeAPI();
$candlesH1 = json_decode(file_get_contents("dataset1.json"));//array_reverse($api->getCandles("CHZUSDT", "1h"));
$candlesM5 = json_decode(file_get_contents("dataset.json"));

$lastPosM5 = count($candlesM5) - 100;
$lastPosH1 = count($candlesH1) - 1;
while ($candlesH1[$lastPosH1][0] < $candlesM5[$lastPosM5][0]) {
    $lastPosH1--;
}
$lastPosH1++;
$lastPosM5++;
$simulationM5 = new Strategy($candlesM5, 1000);
$simulationH1 = new Strategy($candlesH1, 1000);
while($lastPosM5 >= 0) {
    if (mobileAverage($candlesH1, $lastPosH1) < $candlesH1[$lastPosH1][1]
        && mobileAverage($candlesH1, $lastPosH1 + 1) < $candlesH1[$lastPosH1 + 1][1]) {
        $currentPrice = $candlesM5[$lastPosM5][4];
        $simulationM5->makeDecision($currentPrice, $lastPosM5);
    }
    if ($lastPosM5 % 12 == 0) {
        $lastPosH1--;
    }
    $lastPosM5--;
}