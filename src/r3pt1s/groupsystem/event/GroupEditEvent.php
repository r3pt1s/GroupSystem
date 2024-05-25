<?php

namespace r3pt1s\groupsystem\event;

use pocketmine\event\Event;
use r3pt1s\groupsystem\group\Group;

class GroupEditEvent extends Event {

    public function __construct(
        private readonly Group $group,
        private readonly array $oldData,
        private readonly array $newData
    ) {}

    public function getGroup(): Group {
        return $this->group;
    }

    public function getOldData(): array {
        return $this->oldData;
    }

    public function getNewData(): array {
        return $this->newData;
    }
}