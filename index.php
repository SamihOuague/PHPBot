<?php
require_once("./lib/CryptoTradeAPI.php");
require_once("./lib/CryptoTradeBOT_V2.php");
require_once("./lib/Wallet.php");

function run($bot, $api) {
    $start = date("Y-m-d\TH:i:s", (time() - (60 * (60 + 15))));
    $candles = $bot->getCandles();

    $price = $api->ticker("ETH-BTC")["price"];
    $low = $price;
    $hight = $price;
    $openPrice = $candles[0][3];
    $lastPrice = "";
    while(true) {
        $tick = $api->ticker("ETH-BTC");
        if (isset($tick) && isset($tick["time"])) {
            $timestmp = ($candles[0][0] + 60) - strtotime($tick["time"]);
            if ($tick["price"] > $hight)
                $hight = $tick["price"];
            elseif ($tick["price"] < $low)
                $low = $tick["price"];
            if ($timestmp < 0) {
                array_unshift($candles, [
                    ($candles[0][0] + 60),
                    (float) $low,
                    (float) $hight,
                    (float) $openPrice,
                    (float) $tick["price"],
                ]);
                $openPrice = $tick["price"];
                $bot->setCandles($candles);
                $bot->makeDecision($tick["price"]);
                $bot->refreshWallets();
                echo "One minutes past\n";
                echo "ETH => ". round($bot->getWalletA()->getFunds(), 6)   ."\n";
                echo "BTC => ". round($bot->getWalletB()->getFunds(), 6)   ."\n";
                echo "RSI => ".$bot->getRSI(14, 0)."\n";
            } elseif ($tick["price"] != $lastPrice) {
                $lastPrice = $tick["price"];
                $bot->makeDecision($tick["price"]);
                echo "New price => ".$tick["price"]."\n";
            }
        } else {
            var_dump($tick);
        }
        sleep(1);
    }
}

$api = new CryptoTradeAPI();
$start = date("Y-m-d\TH:i:s", (time() - (60 * (60 + 15))));
$candles = $api->getCandlesFrom("ETH-BTC", $start, date("Y-m-d\TH:i:s", time()));
$accounts = $api->getAccounts();
$walletA;
$walletB;
foreach ($accounts as $key => $value) {
    if ($value["currency"] == "BTC") {
        $walletB = new Wallet("BTC", $value["available"]);
    } elseif ($value["currency"] == "ETH") {
        $walletA = new Wallet("ETH", $value["available"]);
    }
}

if (isset($walletA) && isset($walletB)) {
    $bot = new CryptoTradeBOT_V2($walletA, $walletB, $candles);
}

run($bot, $api);