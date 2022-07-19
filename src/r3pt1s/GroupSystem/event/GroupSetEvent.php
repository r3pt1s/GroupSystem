<?php

namespace r3pt1s\GroupSystem\event;

use r3pt1s\GroupSystem\player\PlayerGroup;
use pocketmine\event\Event;

class GroupSetEvent extends Event {

    private string $player;
    private PlayerGroup $group;

    public function __construct(string $player, PlayerGroup $group) {
        $this->player = $player;
        $this->group = $group;
    }

    public function getPlayer(): string {
        return $this->player;
    }

    public function getGroup(): PlayerGroup {
        return $this->group;
    }
}