<?php

namespace GroupSystem\event;

use GroupSystem\group\Group;
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