<?php
require_once("lib/Binance/BinanceTradeAPI.php");
$api = new BinanceTradeAPI();
$candles = [];
$index = 50;
$start = (time() - (60 * 500)) * 1000;
while ($index > 0) {
    $candles0 = $api->getCandles("LTCBNB", "1m", $start);
    if (is_array($candles0)) {
        $start = (($start / 1000) - (60 * 500)) * 1000;
        $candles = array_merge($candles, array_reverse($candles0));
    }
    var_dump(count($candles));
    sleep(1);
    $index--;
}

file_put_contents("dataset.json", json_encode($candles));