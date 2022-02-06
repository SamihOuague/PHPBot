<?php
require_once("./lib/CryptoTradeAPI.php");

$bot = new CryptoTradeAPI();
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
//
////var_dump($candles);
//$fund = 1000;
//
//for ($i = 0; $i < 730; $i++) {
//    $fund += $fund * 0.01;
//}
//
//echo round($fund, 2)."\n";
//$candles = [];
//for ($i = 1, $j = 2; $j < 31; $i++, $j++) {
//    $day = ($i > 9) ? (string) $i : "0".$i;
//    $start = getTime(["2021", "12", ($i > 9) ? (string) $i : "0".$i], ["00", "00", "00"]);
//    $end = getTime(["2021", "12", ($j > 9) ? (string) $j : "0".$j], ["00", "00", "00"]);
//    $candles = array_merge($candles, $bot->getCandlesFrom("ETH-BTC", $start, $end, 300));
//}
//file_put_contents("dataset.json", json_encode($candles));