<?php
//require_once("./lib/CryptoTradeAPI.php");

//$bot = new CryptoTradeAPI();
//$tradpair = $bot->getTradingPair();
//$order = $bot->takeOrder("ETH-BTC", "0.95", "sell");

//$accounts = $bot->getAccounts();
//for ($i = 0; $i < count($accounts); $i++) {
//    if ($accounts[$i]["currency"] == "ETH" || $accounts[$i]["currency"] == "BTC")
//        var_dump($accounts[$i]);
//}

//$order = $bot->takeOrder("ETH-BTC", "0.6", "buy", "0.07");
//$order = $bot->getOrder("e324dd1e-47f8-4f5d-83a7-b7b074a2cc2b");
//function getTime($date, $hour) {
//    $start = $date[0]."-".$date[1]."-".$date[2]."T".$hour[0].":".$hour[1].":".$hour[2];
//    return $start;
//}
//
//$start = getTime(["2022", "02", "04"], ["00", "00", "00"]);
//$end = getTime(["2022", "02", "05"], ["00", "00", "00"]);

//$candles = $bot->getCandlesFrom("ETH-BTC", $start, $end, 300);
//$rsi = $bot->getRSI("ETH-BTC", "60", 14);
//$candles = array_merge($candles, $bot->getCandlesFrom("ETH-BTC", $start, $end, 300));

//echo $start." => ". $end ."\n";

//file_put_contents("dataset.json", json_encode($candles));
//var_dump($candles);
$fund = 5000;

for ($i = 0; $i < 30; $i++) {
    $fund += $fund * 0.10;
}

echo round($fund, 2)."\n";