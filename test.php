<?php

/**
 * @param string $str is a string containing information about a permission (format: permission#expire_date#granted <- last one is optional, true if non existent)
 * @return array
 */
function newParsePermString(string $str): ?array {
    $expireDate = null;
    $granted = true;
    $stringParts = explode("#", $str);

    if (($grantedPart = strtolower(trim($stringParts[array_key_last($stringParts)]))) == "true" || $grantedPart == "false") {
        $granted = ($grantedPart == "true");
        unset($stringParts[array_key_last($stringParts)]);
        $stringParts = array_values($stringParts);
    }

    $expireDatePart = trim($stringParts[array_key_last($stringParts)]);
    if (isTimeString($expireDatePart, $expireDateObject)) {
        $expireDate = $expireDateObject;
        unset($stringParts[array_key_last($stringParts)]);
        $stringParts = array_values($stringParts);
    }

    $permission = implode("#", $stringParts);

    return [$permission, $expireDate, $granted];
}

function isTimeString(string $string, ?DateTime &$object = null): bool {
    if (trim($string) == "") return false;
    try {
        $object = new DateTime($string);
        return true;
    } catch (Exception) {
        return false;
    }
}

// Examples
list($permission, $expireDate, $granted) = newParsePermString('read.#messages#2025-12-31#true');
var_dump($permission, $expireDate, $granted);

list($permission, $expireDate, $granted) = newParsePermString('delete.posts#false');

var_dump($permission, $expireDate, $granted);

list($permission, $expireDate, $granted) = newParsePermString('edit.profile');
var_dump($permission, $expireDate, $granted);