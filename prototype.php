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

function getRSI($candles, $pos = 0, $period = 15) {
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


$api = new BinanceTradeAPI();

$candlesM1 = json_decode(file_get_contents("dataset.json"));
$candlesM30 = array_reverse($api->getCandles("CHZUSDT", "30m"));
sleep(1);
$start = (time() - (3600 * 500)) * 1000;
$candlesM30 = array_merge($candlesM30, array_reverse($api->getCandles("CHZUSDT", "30m", $start)));


$lastPosM1 = count($candlesM1) - 50;
$lastPosM30 = count($candlesM30) - 100;


while ($candlesM1[$lastPosM1][0] < $candlesM30[$lastPosM30][0]) {
    $lastPosM1--;
}


//echo date("Y-m-d H:i:s", $candlesM1[$lastPosM1][0]/1000)."\n";
//echo date("Y-m-d H:i:s", $candlesM30[$lastPosM30][0]/1000)."\n";


$simulationM1 = new Strategy($candlesM1, 10000);

while($lastPosM1 >= 0) {
    if(-1 < $lastPosM30) {
        $simulationM1->makeDecision($candlesM1[$lastPosM1][4], $lastPosM1, getRSI($candlesM30, $lastPosM30), mobileAverage($candlesM30, $lastPosM30, 7));
        if ($lastPosM1 % 30 == 0)
            $lastPosM30--;
    }
    $lastPosM1--;
}
$simulationM1->sell($candlesM1[0][4]);
echo "WIN RATE => ". round($simulationM1->wins / ($simulationM1->wins + $simulationM1->losses) * 100)."%\n";
echo "USDT => ". round($simulationM1->getWalletB()->getFunds(), 2)."$\n";
echo ($simulationM1->wins + $simulationM1->losses)."\n";