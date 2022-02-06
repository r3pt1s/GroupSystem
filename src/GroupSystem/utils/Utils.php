<?php

namespace GroupSystem\utils;

use GroupSystem\GroupSystem;
use pocketmine\utils\Config;

class Utils {

    public static function get(string $key): string {
        if (self::getMessagesConfig()->exists($key)) return self::getMessagesConfig()->get($key);
        return "";
    }

    public static function parse(string $key, array $parameters = []): string {
        $result = str_replace("{PREFIX}", self::get("prefix"), self::get($key));
        foreach ($parameters as $index => $parameter) {
            $result = str_replace("{%" . $index . "}", $parameter, $result);
        }
        return $result;
    }

    private static function getMessagesConfig(): Config {
        return new Config(GroupSystem::getInstance()->getDataFolder() . "messages.json", 1);
    }
}