<?php

namespace r3pt1s\groupsystem\event;

use r3pt1s\groupsystem\player\PlayerGroup;
use pocketmine\event\Event;

class GroupSetEvent extends Event {

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