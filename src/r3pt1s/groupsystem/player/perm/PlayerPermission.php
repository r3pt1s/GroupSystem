<?php

namespace r3pt1s\groupsystem\player\perm;

use DateTime;
use r3pt1s\groupsystem\util\Utils;

class PlayerPermission {

    public function __construct(
        private readonly string $permission,
        private readonly DateTime|null $expireDate = null
    ) {}

    public function hasExpired(): bool {
        if ($this->expireDate instanceof DateTime) return $this->expireDate <= new DateTime("now");
        return false;
    }

    public function getPermission(): string {
        return $this->permission;
    }

    public function getExpireDate(): ?DateTime {
        return $this->expireDate;
    }

    public function toString(): string {
        if ($this->expireDate === null) return $this->permission;
        return $this->permission . "#" . $this->expireDate->format("Y-m-d H:i:s");
    }

    public function __toString(): string {
        return "PlayerPermission(permission=" . $this->permission . ",expire=" . ($this->expireDate?->format("Y-m-d H:i:s") ?? "null") . ")";
    }

    public static function fromString(string $data): ?self {
        $explode = explode("#", $data);
        $last = trim($explode[array_key_last($explode)]);
        $expire = null;
        if (Utils::isTimeString($last, $expireObject)) $expire = $expireObject;
        $permissionString = substr($data, 0, strlen($data) - ($expire instanceof DateTime ? strlen($last) + 1 : 0));
        return new self($permissionString, $expire);
    }
}