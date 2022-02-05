<?php
require_once("./lib/CryptoTradeBOT.php");
require_once("./lib/Wallet.php");

$bot = new CryptoTradeBOT();
$walletETH = new Wallet("ETH", "1.05");
$walletBTC = new Wallet("BTC", "0.0");

$walletBTC = $walletETH->sellAll($bot->getCandle(300)[4], $walletBTC);
$walletETH = $walletBTC->buyAll($bot->getCandle(300)[4], $walletETH);

//$bot->simulateStrategie();