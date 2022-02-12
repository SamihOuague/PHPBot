<?php
class Wallet {
    protected $funds;
    protected $currency;

    public function __construct(string $currency, float $funds) {
        $this->setFunds($funds);
        $this->setCurrency($currency);
    }

    public function getFunds() {
        return $this->funds;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function setFunds(float $funds) {
        return $this->funds = $funds;
    }

    public function setCurrency($currency) {
        return $this->currency = $currency;
    }

    public function sellAll($funds, $wallet) {
        $wallet->setFunds($funds);
        $this->setFunds(0);
        return $wallet;
    }

    public function buyAll($funds, $wallet) {
        $wallet->setFunds($funds);
        $this->setFunds(0);
        return $wallet;
    }
}