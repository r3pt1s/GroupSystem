<?php

namespace r3pt1s\groupsystem\event;

use pocketmine\event\Event;
use r3pt1s\groupsystem\player\perm\PlayerPermission;

class PermissionAddEvent extends Event {

    public function __construct(
        private readonly string $username,
        private readonly PlayerPermission $permission
    ) {}

    public function getUsername(): string {
        return $this->username;
    }

    public function getPermission(): PlayerPermission {
        return $this->permission;
    }
}