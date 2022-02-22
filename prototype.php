<?php
require_once("lib/Binance/BinanceTradeAPI.php");
$api = new BinanceTradeAPI();
$candlesM15 = array_reverse($api->getCandles("CHZUSDT", "15m"));
$candlesM1 = array_reverse($api->getCandles("CHZUSDT", "1m"));

$lastPosM15 = count($candlesM15) - 1;
$lastPosM1 = count($candlesM1) - 1;


$lowM1 = $candlesM1[$lastPosM1];
$highM1 = $candlesM1[$lastPosM1];
while($lastPosM1 >= 0) {
    if ($candlesM1[$lastPosM1][2] > $highM1[2]) {
        $highM1 = $candlesM1[$lastPosM1];
    }
    if ($candlesM1[$lastPosM1][3] < $lowM1[3]) {
        $lowM1 = $candlesM1[$lastPosM1];
    }
    $lastPosM1--;
}

$index = $lowM1[4];
while ($index <= $highM1[2]) {
    $lastPosM1 = count($candlesM1) - 339;
    $index += 0.0001;
    $touch = 0;
    while ($lastPosM1 >= 0) {
        if ($candlesM1[$lastPosM1][4] < $index && $candlesM1[$lastPosM1][4] > ($index - 0.0002)) {
            $touch++;
        }
        $lastPosM1--;
    }
    if ($touch >= 1) {
        system("clear");
        echo $touch ." - ". $index ."\n";
        return 0;
    }
    
}

//echo $candlesM1[0][0]."\n";