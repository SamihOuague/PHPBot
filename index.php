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
        $bot->makeDecision($tick["price"]);
        if ($bot->getWalletB()->getFunds() > 10)
            echo "USDT => ". round($bot->getWalletB()->getFunds(), 4) ."\n";
        else
            echo "USDT => ". round($bot->getWalletA()->getFunds()*$tick["price"] - ($bot->getWalletA()->getFunds()*$tick["price"] * 0.00075), 4) ."\n";
        echo "price => ". $tick["price"] ." USDT\n";
        echo "STOP LOSS => ". $bot->stopLoss ."\n";
        echo "TAKE PROFIT => ". $bot->takeProfit ."\n";
        echo "LAST CANDLE => ". date("Y-m-d H:i:s", ($candles[0][0] / 1000));
    }
    sleep(5);
}