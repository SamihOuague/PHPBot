<?php
require_once("./lib/CryptoTradeBOT_V2.php");
require_once("./lib/CryptoTradeAPI.php");
require_once("./lib/Wallet.php");

$api = new CryptoTradeAPI();
$start = date("Y-m-d\TH:i:s", (time() - (60 * 60 * 6)));
$candles = $api->getCandlesFrom("LTC-BTC", $start, date("Y-m-d\TH:i:s", time()), 900);
$accounts = $api->getAccounts();
$walletA;
$walletB;
foreach ($accounts as $key => $value) {
    if ($value["currency"] == "BTC") {
        $walletB = new Wallet("BTC", $value["available"]);
    } elseif ($value["currency"] == "LTC") {
        $walletA = new Wallet("LTC", $value["available"]);
    }
}

$bot = new CryptoTradeBOT_V2($walletA, $walletB, $candles);
$lastFunds = 0;
while (true) {
    $tick = $api->ticker("LTC-BTC");
    if (((time() - $bot->getCandle(0)[0]) > 900) || time() % 60 == 0) {
        $start = date("Y-m-d\TH:i:s", (time() - (60 * 60 * 6)));
        $candles = $api->getCandlesFrom("LTC-BTC", $start, date("Y-m-d\TH:i:s", time()), 900);
        $bot->setCandles($candles);
        echo $bot->getRSI()."\n";
    }
    $bot->makeDecision($tick["price"]);
    if ($lastFunds != round($bot->getWalletA()->getFunds(), 5)) {
        $lastFunds = round($bot->getWalletA()->getFunds(), 5);
        echo "LTC => ". round($bot->getWalletA()->getFunds(), 5) ."\n";
        echo "BTC => ". round($bot->getWalletB()->getFunds(), 5) ."\n";
    }
    sleep(1);
}