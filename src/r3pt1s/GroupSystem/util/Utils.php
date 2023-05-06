<?php

namespace r3pt1s\GroupSystem\util;

use pocketmine\utils\Config;

class Utils {

    public static function get(string $key): string {
        if (self::getMessagesConfig()->exists($key)) return self::getMessagesConfig()->get($key);
        return $key;
    }

    public static function parse(string $key, array $parameters = []): string {
        $result = str_replace(["{PREFIX}", "{line}"], [self::get("prefix"), "\n"], self::get($key));
        foreach ($parameters as $index => $parameter) {
            $result = str_replace("{%" . $index . "}", $parameter, $result);
        }
        return $result;
    }

    public static function convertStringToDateFormat(string $format, ?\DateTime $time = null, string $type = "add"): ?\DateTime {
        if ($format == "") return null;
        $result = ($time === null ? new \DateTime("now") : $time);
        $parts = str_split($format);
        $timeUnits = ["y" => "year", "m" => "month", "w" => "week", "d" => "day", "h" => "hour", "M" => "minute", "i" => "minute", "s" => "second"];
        $i = -1;
        $changes = false;

        foreach ($parts as $part) {
            ++$i;
            if (!isset($timeUnits[$part])) continue;
            $number = implode("", array_slice($parts, 0, $i));
            if (is_numeric($number)) {
                $result->modify(($type == "add" ? "+" : "-") . intval($number) . $timeUnits[$part]);
                array_splice($parts, 0, $i + 1);
                $i = -1;
                $changes = true;
            }
        }

        if ($changes == false) return null;

        return $result;
    }

    public static function diffString(\DateTime $target, \DateTime $object, bool $asTimeString = false): string {
        $diff = $target->diff($object);
        $result = [];
        if ($diff->y > 0) $result[] = $diff->y . ($asTimeString ? "y" : " " . Utils::parse("raw_year"));
        if ($diff->m > 0) $result[] = $diff->m . ($asTimeString ? "m" : " " . Utils::parse("raw_month"));
        if ($diff->d > 0) $result[] = $diff->d . ($asTimeString ? "d" : " " . Utils::parse("raw_day"));
        if ($diff->h > 0) $result[] = $diff->h . ($asTimeString ? "h" : " " . Utils::parse("raw_hour"));
        if ($diff->i > 0) $result[] = $diff->i . ($asTimeString ? "M" : " " . Utils::parse("raw_minute"));
        if ($diff->s > 0) $result[] = $diff->s . ($asTimeString ? "s" : " " . Utils::parse("raw_second"));
        if (count($result) > 0) {
            return implode(($asTimeString ? "" : ", "), $result);
        } else {
            return $diff->s . ($asTimeString ? "s" : " " . Utils::parse("raw_second"));
        }
    }

    private static function getMessagesConfig(): Config {
        return new Config(Configuration::getInstance()->getMessagesPath() . "messages.yml", 2);
    }
}