<?php

namespace r3pt1s\groupsystem\event\group;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use r3pt1s\groupsystem\group\Group;

/**
 * This event is called when a group gets created.
 */
final class GroupCreateEvent extends Event implements Cancellable {
    use CancellableTrait;
    
    public function __construct(private readonly Group $group) {}

    public function getGroup(): Group {
        return $this->group;
    }
}