<?php

namespace GroupSystem\event;

use GroupSystem\group\Group;
use pocketmine\event\Event;

class GroupSetEvent extends Event {

    private string $player;
    private Group $group;

    public function __construct(string $player, Group $group) {
        $this->player = $player;
        $this->group = $group;
    }

    public function getPlayer(): string {
        return $this->player;
    }

    public function getGroup(): Group {
        return $this->group;
    }
}