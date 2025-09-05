<?php

namespace r3pt1s\groupsystem\event\player;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use r3pt1s\groupsystem\player\perm\PlayerPermission;

/**
 * This event is called when a permission gets revoked from a player.
 */
final class PlayerPermissionRevokeEvent extends Event implements Cancellable {
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