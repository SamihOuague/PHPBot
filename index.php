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


$candlesM5 = array_reverse($api->getCandles("CHZUSDT", "5m"));
$candlesH1 = array_reverse($api->getCandles("CHZUSDT", "1h"));
$bot = new CryptoTradeBOT_V3($candlesM5);
while (true) {
    $tick = $api->ticker("CHZUSDT");
    if (isset($tick) && isset($tick["price"])) {
        if ((time() - ($candlesM5[0][0] / 1000)) > 300) {
            $cand = $api->getCandles("CHZUSDT", "5m");
            if (is_array($cand)) {
                $candles = array_reverse($cand);
                $bot->setCandles($candles);
            }
        }
        if ((time() - ($candlesH1[0][0] / 1000)) > 3600) {
            $cand = $api->getCandles("CHZUSDT", "1h");
            if (is_array($cand)) {
                $candlesH1 = array_reverse($cand);
            }
        }
        if ($bot->getWalletA()->getFunds() > 10 || (mobileAverage($candlesH1, 0) < $candlesH1[0][1]
        && mobileAverage($candlesH1, 1) < $candlesH1[1][1])) {
            system("clear");
            $bot->makeDecision($tick["price"]);
            echo "USDT => ". round($bot->getWalletB()->getFunds() + $bot->getWalletA()->getFunds()*$tick["price"] - ($bot->getWalletA()->getFunds()*$tick["price"] * 0.00075), 4) ."\n";
            echo "price => ". $tick["price"] ." USDT\n";
            echo "STOP LOSS => ". $bot->stopLoss ."\n";
            echo "MA 7 H1 => ". mobileAverage($candlesH1, 0) ."\n";
            echo "LAST CANDLE H1 => ". date("Y-m-d H:i:s", ($candlesH1[0][0] / 1000));
            echo "LAST CANDLE M5 => ". date("Y-m-d H:i:s", ($bot->getCandle(0)[0] / 1000));
        } else {
            system("clear");
            echo "USDT => ". round($bot->getWalletB()->getFunds() + $bot->getWalletA()->getFunds()*$tick["price"] - ($bot->getWalletA()->getFunds()*$tick["price"] * 0.00075), 4) ."\n";
            echo "price => ". $tick["price"] ." USDT\n";
            echo "STOP LOSS => ". $bot->stopLoss ."\n";
            echo "MA 7 => ". mobileAverage($candlesH1, 0) ."\n";
            echo "LAST CANDLE H1 => ". date("Y-m-d H:i:s", ($candlesH1[0][0] / 1000))."\n";
            echo "LAST CANDLE M5 => ". date("Y-m-d H:i:s", ($bot->getCandle(0)[0] / 1000));
        }
    }
    sleep(5);
}