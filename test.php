<?php
require_once("./lib/CryptoTradeBOT.php");
require_once("./lib/CryptoTradeAPI.php");
require_once("./lib/Wallet.php");

$api = new CryptoTradeAPI();
$accounts = $api->getAccounts();
foreach ($accounts as $key => $value) {
    if ($value["currency"] == "BTC") {
        var_dump($value["available"]);
    } elseif ($value["currency"] == "ETH") {
        $this->walletA->setFunds($value["available"]);
    }
}
//$candles = $api->getCandlesFrom("ETH-BTC", $start, date("Y-m-d\TH:i:s", time()));
//$bot = new CryptoTradeBOT("1.0", $candles);
//
//$price = $api->ticker("ETH-BTC")["price"];
//$low = $price;
//$hight = $price;
//$openPrice = $candles[0][3];
//$lastPrice = "";
//
//while(true) {
//    $tick = $api->ticker("ETH-BTC");
//    if (isset($tick) && isset($tick["time"])) {
//        $timestmp = ($candles[0][0] + 60) - strtotime($tick["time"]);
//        if ($tick["price"] > $hight)
//            $hight = $tick["price"];
//        elseif ($tick["price"] < $low)
//            $low = $tick["price"];
//        if ($timestmp < 0) {
//            array_unshift($candles, [
//                ($candles[0][0] + 60),
//                (float) $low,
//                (float) $hight,
//                (float) $openPrice,
//                (float) $tick["price"],
//            ]);
//            $openPrice = $tick["price"];
//            $bot->setCandles($candles);
//            $bot->makeDecision($tick["price"]);
//            echo "One minutes past\n";
//            echo "ETH => ".$bot->getWalletA()->getFunds()."\n";
//            echo "BTC => ".$bot->getWalletB()->getFunds()."\n";
//            echo "RSI => ".$bot->getRSI(14, 0)."\n";
//        } elseif ($tick["price"] != $lastPrice) {
//            $lastPrice = $tick["price"];
//            $bot->makeDecision($tick["price"]);
//            echo "New price => ".$tick["price"]."\n";
//        }
//    } else {
//        var_dump($tick);
//    }
//    sleep(1);
//}