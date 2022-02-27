<?php
require_once("lib/Binance/BinanceTradeAPI.php");
require_once("lib/CryptoTradeBOT_V3.php");
require_once("lib/Wallet.php");

function mobileAverage($candles, $pos, $period = 7) {
    $sum = 0;
    for($i = $pos; $i < ($pos + $period); $i++) {
        $candle = $candles[$i];
        $sum += $candle[4];
    }
    return round($sum/$period, 3);
}

$api = new BinanceTradeAPI();
$accounts = $api->getAccounts();


$candlesM5 = array_reverse($api->getCandles("CHZUSDT", "1m"));
$candlesH1 = array_reverse($api->getCandles("CHZUSDT", "1h"));
$bot = new CryptoTradeBOT_V3($candlesM5);
while (true) {
    $tick = $api->ticker("CHZUSDT");
    if (isset($tick) && isset($tick["price"])) {
        if ((time() - ($candlesM5[0][0] / 1000)) > 60) {
            $cand = $api->getCandles("CHZUSDT", "1m");
            if (is_array($cand)) {
                $candles = array_reverse($cand);
                $bot->setCandles($candles);
            }
        }
        system("clear");
        $bot->makeDecision($tick["price"]);
        echo "USDT => ". round($bot->getWalletB()->getFunds() + $bot->getWalletA()->getFunds()*$tick["price"] - ($bot->getWalletA()->getFunds()*$tick["price"] * 0.00075), 4) ."\n";
        echo "price => ". $tick["price"] ." USDT\n";
        if ($bot->getWalletB()->getFunds() < 5) {
            echo "TAKE PROFIT => ". $bot->takeProfit ."\n";
            echo "STOP LOSS => ". $bot->stopLoss ."\n";
        }
        echo "RSI => ". round($bot->getRSI()) ."\n";
        echo "RISK => ". $bot->risk ."\n";
        echo "LAST CANDLE H1 => ". date("Y-m-d H:i:s", ($candlesM5[0][0] / 1000));
    }
    sleep(5);
}