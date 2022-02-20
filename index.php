<?php
require_once("lib/Binance/BinanceTradeAPI.php");
require_once("lib/CryptoTradeBOT_V3.php");
require_once("lib/Wallet.php");

$api = new BinanceTradeAPI();
$accounts = $api->getAccounts();


$candles = array_reverse($api->getCandles("CHZUSDT", "1m"));

$bot = new CryptoTradeBOT_V3($candles);
while (true) {
    $tick = $api->ticker("CHZUSDT");
    if (isset($tick) && isset($tick["price"])) {
        if ((time() - ($candles[0][0] / 1000)) > 60) {
            $cand = $api->getCandles("CHZUSDT", "1m");
            if (is_array($cand)) {
                $candles = array_reverse($cand);
                $bot->setCandles($candles);
            }
        }
        system("clear");
        echo "Signal => ". $bot->signal ."\n";
        if (round($bot->getWalletB()->getFunds()) > 10) {
            $ltc = round($bot->getWalletB()->getFunds() / $tick["price"], 5);
            echo "CHZ potentiel => ". ($ltc - ($ltc * 0.00075)) ."\n";
        } elseif (round($bot->getWalletA()->getFunds()) > 10) {
            $bnb = round($bot->getWalletA()->getFunds() * $tick["price"], 5);
            echo "USDT potentiel => ". ($bnb - ($bnb * 0.00075)) ."\n";
        }
        $bot->makeDecision($api->ticker("CHZUSDT")["price"]);
        echo "USDT => ". round($bot->getWalletB()->getFunds(), 5) ."\n";
        echo "CHZ => ". round($bot->getWalletA()->getFunds(), 5) ."\n";
        echo "price => ". $tick["price"] ." USDT\n";
        echo "RSI => ". $bot->getRSI() ."\n";
        echo "STOP LOSS => ". $bot->stopLoss ."\n";
        echo "LAST CANDLE => ". date("Y-m-d H:i:s", ($candles[0][0] / 1000));
    }
    sleep(5);
}