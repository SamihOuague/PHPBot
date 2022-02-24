<?php
require_once("lib/Binance/BinanceTradeAPI.php");
$api = new BinanceTradeAPI();
$candles = [];
$index = 15;
$test = $index;
$time = 3600;
$start = (time() - ($time * 500)) * 1000;
while ($index > 0) {
    $candles0 = $api->getCandles("CHZUSDT", "1h", $start);
    if (is_array($candles0)) {
        $start = (($start / 1000) - ($time * 500)) * 1000;
        $candles = array_merge($candles, array_reverse($candles0));
    }
    system("clear");
    echo "Download... ". round((($test - $index) / $test)*100)."%\n";
    usleep(500000);
    $index--;
}

file_put_contents("dataset1.json", json_encode($candles));