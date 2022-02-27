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

$candlesM1 = json_decode(file_get_contents("dataset.json"));
$candlesM5 = json_decode(file_get_contents("dataset1.json"));
$candlesM15 = json_decode(file_get_contents("dataset2.json"));
$candlesM30 = array_reverse($api->getCandles("CHZUSDT", "30m"));

$lastPosM1 = count($candlesM1) - 50000;
$lastPosM5 = count($candlesM5) - 1;
$lastPosM15 = count($candlesM15) - 1;
$lastPosM30 = count($candlesM30) - 1;


//while ($candlesM1[$lastPosM1][0] < $candlesM30[$lastPosM30][0]) {
//    $lastPosM1--;
//}
//while ($candlesM5[$lastPosM5][0] < $candlesM30[$lastPosM30][0]) {
//    $lastPosM5--;
//}
//while ($candlesM15[$lastPosM15][0] < $candlesM30[$lastPosM30][0]) {
//    $lastPosM15--;
//}

//echo date("Y-m-d H:i:s", $candlesM1[$lastPosM1][0]/1000)."\n";
//echo date("Y-m-d H:i:s", $candlesM5[$lastPosM5][0]/1000)."\n";
//echo date("Y-m-d H:i:s", $candlesM15[$lastPosM15][0]/1000)."\n";
//echo date("Y-m-d H:i:s", $candlesM30[$lastPosM30][0]/1000)."\n";
//$simulationM15 = new Strategy($candlesM15, 20);
$simulationM1 = new Strategy($candlesM1, 100);

while($lastPosM1 >= 0) {
    $simulationM1->makeDecision($candlesM1[$lastPosM1][4], $lastPosM1);
    $lastPosM1--;   
}

$simulationM1->sell($candlesM1[0][4]);
echo "WIN RATE => ". round($simulationM1->wins / ($simulationM1->wins + $simulationM1->losses) * 100)."%\n";
echo "USDT => ". round($simulationM1->getWalletB()->getFunds(), 2)."$\n";