<?php

namespace r3pt1s\groupsystem\event;

use pocketmine\event\Event;

class PermissionAddEvent extends Event {

    public function __construct(
        private string $username,
        private string $permission
    ) {}

    public function getUsername(): string {
        return $this->username;
    }

    public function getPermission(): string {
        return $this->permission;
    }
}