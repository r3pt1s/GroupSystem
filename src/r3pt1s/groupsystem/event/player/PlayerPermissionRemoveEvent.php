<?php

namespace r3pt1s\groupsystem\event\player;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use r3pt1s\groupsystem\player\perm\PlayerPermission;

/**
 * This event is called when a permission gets completely removed from a player.
 * This means the default state of the permission is being used automatically by pocketmine itself and the permission just gets wiped from the group player data.
 */
final class PlayerPermissionRemoveEvent extends Event implements Cancellable {
    use CancellableTrait;

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