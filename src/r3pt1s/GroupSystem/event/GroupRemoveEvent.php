<?php

namespace r3pt1s\GroupSystem\event;

use r3pt1s\GroupSystem\group\Group;
use pocketmine\event\Event;

class GroupRemoveEvent extends Event {

    private Group $group;

    public function __construct(Group $group) {
        $this->group = $group;
    }

    public function getGroup(): Group {
        return $this->group;
    }
}