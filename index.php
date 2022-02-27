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

function getRSI($candles, $pos = 0, $period = 15) {
    $avgHarray = [];
    $avgBarray = [];
    for ($i = $pos + $period; $i >= $pos; $i--) {
        $ystrD = (float) $candles[$i + 1][4];
        $crnt = (float) $candles[$i][4];
        $diff = $ystrD - $crnt;
        if ($diff < 0) {
            $avgBarray[] = 0;
            $avgHarray[] = -1 * $diff;
        } else {
            $avgBarray[] = $diff;
            $avgHarray[] = 0;
        }
    }
    $avgB = 0;
    $avgH = 0;
    for ($i = 0; $i < count($avgBarray); $i++) {
        $avgB += $avgBarray[$i];
        $avgH += $avgHarray[$i];
    }
    
    $avgB = ($avgB / count($avgBarray));
    $avgH = ($avgH / count($avgHarray));
    $avgDiff = $avgH - $avgB;
    if ($avgB != 0 && $avgH != 0)
        $rsi = 100 - ((100/(1 + ($avgH/$avgB))));
    else
        $rsi = 100 - (100/2);
    return $rsi;
}

$api = new BinanceTradeAPI();
$accounts = $api->getAccounts();


$candlesM1 = array_reverse($api->getCandles("CHZUSDT", "1m"));
$candlesM30 = array_reverse($api->getCandles("CHZUSDT", "30m"));
$bot = new CryptoTradeBOT_V3($candlesM1);
while (true) {
    $tick = $api->ticker("CHZUSDT");
    if (isset($tick) && isset($tick["price"])) {
        if ((time() - ($candlesM1[0][0] / 1000)) > 60) {
            $cand = $api->getCandles("CHZUSDT", "1m");
            if (is_array($cand)) {
                $candlesM1 = array_reverse($cand);
                $bot->setCandles($candlesM1);
            }
        }
        if ((time() - ($candlesM30[0][0] / 1000)) > 1800) {
            $cand = $api->getCandles("CHZUSDT", "30m");
            if (is_array($cand)) {
                $candlesM30 = array_reverse($cand);
            }
        }
        system("clear");
        $bot->makeDecision($tick["price"], getRSI($candlesM30, 0));
        echo "USDT => ". round($bot->getWalletB()->getFunds() + $bot->getWalletA()->getFunds()*$tick["price"] - ($bot->getWalletA()->getFunds()*$tick["price"] * 0.00075), 4) ."\n";
        echo "price => ". $tick["price"] ." USDT\n";
        if ($bot->getWalletB()->getFunds() < 5) {
            echo "TAKE PROFIT => ". $bot->takeProfit ."\n";
            echo "STOP LOSS => ". $bot->stopLoss ."\n";
        }
        echo "RSI M1 => ". round($bot->getRSI()) ."\n";
        echo "RSI M30 => ". round(getRSI($candlesM30)) ."\n";
        echo "LAST CANDLE M1 => ". date("Y-m-d H:i:s", ($candlesM1[0][0] / 1000))."\n";
        echo "LAST CANDLE M30 => ". date("Y-m-d H:i:s", ($candlesM30[0][0] / 1000))."\n";
    }
    sleep(5);
}