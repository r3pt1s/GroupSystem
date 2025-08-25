<?php

namespace r3pt1s\groupsystem\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use r3pt1s\groupsystem\group\Group;
use pocketmine\event\Event;

final class GroupRemoveEvent extends Event implements Cancellable {
    use CancellableTrait;
    public function __construct(private readonly Group $group) {}

    public function getGroup(): Group {
        return $this->group;
    }
}