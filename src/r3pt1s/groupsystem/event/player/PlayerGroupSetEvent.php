<?php

namespace r3pt1s\groupsystem\event\player;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use r3pt1s\groupsystem\player\PlayerGroup;

/**
 * This event is called when a player gets assigned to another group.
 */
final class PlayerGroupSetEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(
        private readonly string $username,
        private readonly PlayerGroup $group
    ) {}

    public function getUsername(): string {
        return $this->username;
    }

    public function getGroup(): PlayerGroup {
        return $this->group;
    }
}