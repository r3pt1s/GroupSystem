<?php

namespace r3pt1s\groupsystem\event;

use r3pt1s\groupsystem\group\Group;
use pocketmine\event\Event;

class GroupCreateEvent extends Event {

    public function __construct(private readonly Group $group) {}

    public function getGroup(): Group {
        return $this->group;
    }
}