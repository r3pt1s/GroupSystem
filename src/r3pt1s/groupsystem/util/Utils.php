<?php

namespace r3pt1s\groupsystem\util;

use DateTime;
use Exception;
use InvalidArgumentException;

final class Utils {

    public const OLD_PLAYER_DATA_KEYS = ["Group", "ExpireAt", "Groups", "Permissions"];
    public const NEW_PLAYER_DATA_KEYS = ["group", "expire", "groups", "permissions"];

    public const OLD_GROUP_DATA_KEYS = ["NameTag", "DisplayName", "ChatFormat", "ColorCode", "Permissions"];
    public const NEW_GROUP_DATA_KEYS = ["name_tag", "display_name", "chat_format", "color_code", "permissions"];

    public static function isTimeString(string $string, ?DateTime &$object = null): bool {
        if (trim($string) == "") return false;
        try {
            $object = new DateTime($string);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    public static function convertStringToDateFormat(string $format, ?DateTime $time = null, string $type = "add"): ?DateTime {
        if ($format == "") return null;
        $result = ($time === null ? new DateTime() : $time);
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

        if (!$changes) return null;

        return $result;
    }

    public static function parsePermissionString(string $permString): array {
        $expireDate = null;
        $granted = true;
        $stringParts = explode("#", $permString);

        if (($grantedPart = strtolower(trim($stringParts[array_key_last($stringParts)]))) == "true" || $grantedPart == "false") {
            $granted = ($grantedPart == "true");
            unset($stringParts[array_key_last($stringParts)]);
            $stringParts = array_values($stringParts);
        }

        $expireDatePart = trim($stringParts[array_key_last($stringParts)]);
        if (self::isTimeString($expireDatePart, $expireDateObject)) {
            $expireDate = $expireDateObject;
            unset($stringParts[array_key_last($stringParts)]);
            $stringParts = array_values($stringParts);
        }

        $permission = implode("#", $stringParts);

        return [$permission, $expireDate, $granted];
    }

    public static function diffString(DateTime $target, DateTime $object, bool $asTimeString = false, int $limit = -1): string {
        $diff = $target->diff($object);
        $result = [];
        if ($diff->y > 0) $result[] = $diff->y . ($asTimeString ? "y" : " " . Message::RAW_YEAR());
        if ($diff->m > 0) $result[] = $diff->m . ($asTimeString ? "m" : " " . Message::RAW_MONTH());
        if ($diff->d > 0) $result[] = $diff->d . ($asTimeString ? "d" : " " . Message::RAW_DAY());
        if ($diff->h > 0) $result[] = $diff->h . ($asTimeString ? "h" : " " . Message::RAW_HOUR());
        if ($diff->i > 0) $result[] = $diff->i . ($asTimeString ? "M" : " " . Message::RAW_MINUTE());
        if ($diff->s > 0) $result[] = $diff->s . ($asTimeString ? "s" : " " . Message::RAW_SECOND());
        if ($limit > 0) $result = array_slice($result, 0, $limit);
        if (count($result) > 0) {
            return implode(($asTimeString ? "" : ", "), $result);
        } else {
            return $diff->s . ($asTimeString ? "s" : " " . Message::RAW_SECOND());
        }
    }

    public static function checkArrayKeysValuesType(array $array, array $keys, array $allowedValueTypes, string $typeSeparator = "|"): void {
        foreach ($keys as $i => $key) {
            if (!array_key_exists($i, $allowedValueTypes))
                throw new InvalidArgumentException("No type definition found for key at index $i ($key).");
            if (!array_key_exists($key, $array))
                throw new InvalidArgumentException("Missing required key '$key' in array.");

            $allowedValueTypesI = explode($typeSeparator, $allowedValueTypes[$i]);
            $actualType = gettype($array[$key]);

            if (!in_array($actualType, $allowedValueTypesI, true))
                throw new InvalidArgumentException(
                    "Invalid type for key '$key': expected " .
                    implode(", ", $allowedValueTypesI) .
                    " but got $actualType."
                );
        }
    }

    public static function renameAndRemove(array $data, array $from, array $to): array {
        foreach ($from as $i => $v) {
            if (!isset($to[$i]) || !isset($data[$v])) continue;
            $data[$to[$i]] = $data[$v];
            unset($data[$v]);
        }
        return $data;
    }

    public static function renewPlayerDataKeys(array $data): array {
        return self::renameAndRemove($data, self::OLD_PLAYER_DATA_KEYS, self::NEW_PLAYER_DATA_KEYS);
    }

    public static function renewGroupDataKeys(array $data): array {
        return self::renameAndRemove($data, self::OLD_GROUP_DATA_KEYS, self::NEW_GROUP_DATA_KEYS);
    }
}