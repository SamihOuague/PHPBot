<?php
class Config {
    public static function getConfig() {
        $config = [
            "passphrase" => "API Passphrase",
            "key" => "API Key",
            "secret" => "API secret Key"
        ];
        return $config;
    }
}