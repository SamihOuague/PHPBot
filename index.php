<?php
require_once("./lib/CryptoTradeBOT.php");
require_once("./lib/CryptoTradeAPI.php");
require_once("./lib/Wallet.php");

$candles = json_decode(file_get_contents("dataset.json"));

$bot = new CryptoTradeBOT("1.0", $candles);
echo $bot->simulateStrategy(14, 2.5, 4.5)." ETH\n";
echo "\033[32m".round(($bot->getWins() / ($bot->getWins() + $bot->getLosses())) * 100)."% DE REUSSITE\n";
echo "WINS => ". $bot->getWins()." LOSSES => ".$bot->getLosses()."\n";