<?php

namespace r3pt1s\groupsystem\util;

use DateTime;

class Utils {

    public static function convertStringToDateFormat(string $format, ?DateTime $time = null, string $type = "add"): ?DateTime {
        if ($format == "") return null;
        $result = ($time === null ? new DateTime("now") : $time);
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

    public static function diffString(DateTime $target, DateTime $object, bool $asTimeString = false): string {
        $diff = $target->diff($object);
        $result = [];
        if ($diff->y > 0) $result[] = $diff->y . ($asTimeString ? "y" : " " . Message::RAW_YEAR());
        if ($diff->m > 0) $result[] = $diff->m . ($asTimeString ? "m" : " " . Message::RAW_MONTH());
        if ($diff->d > 0) $result[] = $diff->d . ($asTimeString ? "d" : " " . Message::RAW_DAY());
        if ($diff->h > 0) $result[] = $diff->h . ($asTimeString ? "h" : " " . Message::RAW_HOUR());
        if ($diff->i > 0) $result[] = $diff->i . ($asTimeString ? "M" : " " . Message::RAW_MINUTE());
        if ($diff->s > 0) $result[] = $diff->s . ($asTimeString ? "s" : " " . Message::RAW_SECOND());
        if (count($result) > 0) {
            return implode(($asTimeString ? "" : ", "), $result);
        } else {
            return $diff->s . ($asTimeString ? "s" : " " . Message::RAW_SECOND());
        }
    }

    public static function renameAndRemove(array $data, array $from, array $to): array {
        foreach ($from as $i => $v) {
            if (!isset($to[$i])) continue;
            if (!isset($data[$v])) continue;
            $data[$to[$i]] = $data[$v];
            unset($data[$v]);
        }
        return $data;
    }

    public static function renewPlayerDataKeys(array $data): array {
        return self::renameAndRemove($data, [
            "Group", "ExpireAt", "Groups", "Permissions"
        ], [
            "group", "expire", "groups", "permissions"
        ]);
    }

    public static function renewGroupDataKeys(array $data): array {
        return self::renameAndRemove($data, [
            "NameTag", "DisplayName", "ChatFormat", "ColorCode", "Permissions"
        ], [
            "name_tag", "display_name", "chat_format", "color_code", "permissions"
        ]);
    }
}