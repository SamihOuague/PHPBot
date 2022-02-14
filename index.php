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
//var_dump($walletB->getFunds());
//var_dump($bot->buy($api->ticker("LTC-BTC")["price"]));
//var_dump($walletA->getFunds());
while (true) {
    $tick = $api->ticker("LTC-BTC");
    $start = date("Y-m-d\TH:i:s", (time() - (60 * 60 * 6)));
    if ((time() - $candles[0][0]) > 900) {
        $candles = $api->getCandlesFrom("LTC-BTC", $start, date("Y-m-d\TH:i:s", time()), 900);
        $bot->setCandles($candles);
    }
    system("clear");
    echo "Signal => ". $bot->signal ."\n";
    if (round($bot->getWalletB()->getFunds(), 5) > 0) {
        $ltc = round($bot->getWalletB()->getFunds() / $tick["price"], 5);
        echo "LTC potentiel => ". ($ltc - ($ltc * 0.0035)) ."\n";
    } elseif (round($bot->getWalletA()->getFunds(), 5) > 0) {
        $btc = round($bot->getWalletA()->getFunds() * $tick["price"], 5);
        echo "BTC potentiel => ". ($btc - ($btc * 0.0035)) ."\n";
    }
    $bot->makeDecision($tick["price"]);
    echo "BTC => ". round($bot->getWalletB()->getFunds(), 5) ."\n";
    echo "LTC => ". round($bot->getWalletA()->getFunds(), 5) ."\n";
    echo "price => ". $tick["price"]."\n";
    echo "RSI => ". $bot->getRSI()."\n";
    sleep(60);
}