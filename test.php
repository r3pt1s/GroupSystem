<?php

[$permission] = parsePermissionString("test#true");
var_dump($permission);
function parsePermissionString(string $permString): array {
    $expireDate = null;
    $granted = true;
    $stringParts = explode("#", $permString);

    if (($grantedPart = strtolower(trim($stringParts[array_key_last($stringParts)]))) == "true" || $grantedPart == "false") {
        $granted = ($grantedPart == "true");
        unset($stringParts[array_key_last($stringParts)]);
        $stringParts = array_values($stringParts);
    }

    $expireDatePart = trim($stringParts[array_key_last($stringParts)]);
    if (false) {
        $expireDate = $expireDateObject;
        unset($stringParts[array_key_last($stringParts)]);
        $stringParts = array_values($stringParts);
    }

    $permission = implode("#", $stringParts);

    return [$permission, $expireDate, $granted];
}