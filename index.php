<?php
require_once("./lib/CryptoTradeBOT.php");
require_once("./lib/Wallet.php");

$bot = new CryptoTradeBOT();

echo $bot->simulateStrategie(11)."\n";
