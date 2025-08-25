<?php

namespace r3pt1s\groupsystem\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use r3pt1s\groupsystem\player\PlayerGroup;
use pocketmine\event\Event;

final class GroupSetEvent extends Event implements Cancellable {
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