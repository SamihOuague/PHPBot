<?php
require_once("./lib/CryptoTradeBot.php");

$bot = new CryptoTradeBot();
$convert = $bot->convert("USDC", "USD", 130);

var_dump($convert);