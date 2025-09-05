<?php

namespace r3pt1s\groupsystem\event\player;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use r3pt1s\groupsystem\player\PlayerRemainingGroup;

/**
 * This event is called when a group gets removed from the remaining groups of a player.
 */
final class PlayerGroupRemoveEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(
        private readonly string $username,
        private readonly PlayerRemainingGroup $group
    ) {}

    public function getUsername(): string {
        return $this->username;
    }

    public function getGroup(): PlayerRemainingGroup {
        return $this->group;
    }
}