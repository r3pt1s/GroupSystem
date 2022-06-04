<?php

namespace r3pt1s\GroupSystem\event;

use pocketmine\event\Event;

class PermissionRemoveEvent extends Event {

    private string $player;
    private string $permission;

    public function __construct(string $player, string $permission) {
        $this->player = $player;
        $this->permission = $permission;
    }

    public function getPlayer(): string {
        return $this->player;
    }

    public function getPermission(): string {
        return $this->permission;
    }
}