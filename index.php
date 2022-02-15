<?php
require_once("lib/Binance/BinanceTradeAPI.php");
require_once("lib/CryptoTradeBOT_V3.php");
require_once("lib/Wallet.php");

$api = new BinanceTradeAPI();
$accounts = $api->getAccounts();

$walletA;
$walletB;
for ($i = 0; $i < count($accounts); $i++) {
    if ($accounts["balances"][$i]["asset"] == "BNB") {
        $walletB = new Wallet("BNB", $accounts["balances"][$i]["free"]);
    } elseif ($accounts["balances"][$i]["asset"] == "LTC") {
        $walletA = new Wallet("LTC", $accounts["balances"][$i]["free"]);
    }
}

$candles = array_reverse($api->getCandles("LTCBNB", "1m"));
if (isset($walletA) && isset($walletB)) {
    $bot = new CryptoTradeBOT_V3($walletA, $walletB, $candles);
}
//var_dump(date("Y-m-d H:i:s", $bot->getCandles()[0][0]/1000));

//var_dump($walletA->getFunds());

while (true) {
    $tick = $api->ticker("LTCBNB");
    if (isset($tick) && isset($tick["price"])) {
        if ((time() - ($candles[0][0] / 1000)) > 60) {
            $cand = $api->getCandles("LTCBNB", "1m");
            if (is_array($cand)) {
                $candles = array_reverse($cand);
                $bot->setCandles($candles);
            }
        }
        system("clear");
        echo "Signal => ". $bot->signal ."\n";
        if (round($bot->getWalletB()->getFunds(), 5) > 0) {
            $ltc = round($bot->getWalletB()->getFunds() / $tick["price"], 5);
            echo "LTC potentiel => ". ($ltc - ($ltc * 0.00075)) ."\n";
        } elseif (round($bot->getWalletA()->getFunds(), 5) > 0) {
            $bnb = round($bot->getWalletA()->getFunds() * $tick["price"], 5);
            echo "BNB potentiel => ". ($bnb - ($bnb * 0.00075)) ."\n";
        }
        $bot->makeDecision($tick["price"]);
        echo "BNB => ". round($bot->getWalletB()->getFunds(), 5) ."\n";
        echo "LTC => ". round($bot->getWalletA()->getFunds(), 5) ."\n";
        echo "price => ". $tick["price"]."\n";
        echo "RSI => ". $bot->getRSI()."\n";
        echo "LAST CANDLE => ". date("Y-m-d H:i:s", ($candles[0][0] / 1000));
    }
    sleep(60);
}