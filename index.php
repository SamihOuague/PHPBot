<?php
require_once("./lib/CryptoTradeBOT.php");
require_once("./lib/CryptoTradeAPI.php");
require_once("./lib/Wallet.php");

$candles = json_decode(file_get_contents("dataset.json"));
$api = new CryptoTradeAPI();
$bot = new CryptoTradeBOT("1.0", $candles);


$lastTick = "";
while(true) {
    $tick = $api->ticker("ETH-BTC");
    if (time() % 60 == 0) {
        $start = date("Y-m-d\TH:i:s", (time() - (60 * (60 + 15))));
        $candlesFrom = $api->getCandlesFrom("ETH-BTC", $start, date("Y-m-d\TH:i:s", time()));
        if (count($candlesFrom) == 15) {
            $bot->setCandles($candlesFrom);
            $bot->makeDecision($tick["price"]);
            //var_dump($bot->getCandles());
        }
    }
    if ($lastTick != $tick["price"]) {
        echo $tick["price"]."\n";
        $lastTick = $tick["price"];
        $bot->makeDecision($tick["price"]);
    }
    sleep(1);
}