<?php

namespace r3pt1s\groupsystem\player\perm;

use DateTime;
use r3pt1s\groupsystem\util\Utils;

final class PlayerPermission {

    public function __construct(
        private readonly string $permission,
        private readonly DateTime|null $expireDate = null,
        private readonly bool $granted = true
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

    public function isGranted(): bool {
        return $this->granted;
    }

    public function isRevoked(): bool {
        return !$this->granted;
    }

    public function write(): string {
        if ($this->expireDate === null) return $this->permission;
        return $this->permission . "#" . $this->expireDate->format("Y-m-d H:i:s") . ($this->granted ? "" : "#false");
    }

    public function __toString(): string {
        return "PlayerPermission(permission=" . $this->permission . ",expire=" . ($this->expireDate?->format("Y-m-d H:i:s") ?? "null") . ",granted=" . ($this->granted ? "true" : "false") . ")";
    }

    public static function read(string $data): ?self {
        [$permission, $expireDate, $granted] = Utils::parsePermissionString($data);
        return new self($permission, $expireDate, $granted);
    }
}