<?php

namespace r3pt1s\GroupSystem\event;

use pocketmine\event\Event;
use r3pt1s\GroupSystem\group\Group;

class GroupEditEvent extends Event {

    private Group $group;
    private array $oldData;
    private array $newData;

    public function __construct(Group $group, array $oldData, array $newData) {
        $this->group = $group;
        $this->oldData = $oldData;
        $this->newData = $newData;
    }

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