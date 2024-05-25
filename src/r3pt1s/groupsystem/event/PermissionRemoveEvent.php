<?php

namespace r3pt1s\groupsystem\event;

use pocketmine\event\Event;

class PermissionRemoveEvent extends Event {

    public function __construct(
        private readonly string $username,
        private readonly string $permission
    ) {}

    public function getUsername(): string {
        return $this->username;
    }

    public function getPermission(): string {
        return $this->permission;
    }
}